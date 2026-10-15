<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\Entity\DecisionAmenagementExamens;
use App\Entity\Formation;
use App\Entity\Inscription;
use App\Message\SignatureDecisionDemandeeMessage;
use App\MessageHandler\SignatureDecisionDemandeeMessageHandler;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Serializer\DecisionAmenagementEditionNormalizer;
use App\Serializer\Encoder\PdfEncoder;
use App\Service\Signature\AbstractParapheur;
use App\Service\Signature\ParapheurException;
use App\Service\Signature\ParapheurFactice;
use App\Service\Signature\SignatureElectronique;
use App\Service\Signature\SuiviSignature;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class SignatureDecisionDemandeeMessageHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DecisionAmenagementExamens $decision;
    private string $etatInitial;
    private Formation $formation;
    private Inscription $inscription;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();

        $decision = $this->em->getRepository(DecisionAmenagementExamens::class)->findOneBy([]);
        if (null === $decision) {
            $this->markTestSkipped('No decision found in DB');
        }
        $this->decision = $decision;
        $this->etatInitial = $decision->getEtat();

        // demandée par le gestionnaire : en signature, pas encore déposée, dans une composante reliée à un circuit
        $this->formation = $this->em->getRepository(Formation::class)->findOneBy(['codeExterne' => 'CODE_F_1']);
        $this->inscription = new Inscription()
            ->setFormation($this->formation)
            ->setDebut(new DateTime('-1 month'))
            ->setFin(new DateTime('+1 month'))
            ->setPrincipale(true);
        $decision->getBeneficiaire()->addInscription($this->inscription);
        $this->formation->getComposante()->setCircuitSignature('circuit-test');
        $decision->setEtat(DecisionAmenagementExamens::ETAT_EN_SIGNATURE);
        $this->em->persist($this->inscription);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        // les autres tests attendent l'envoi par e-mail
        $this->formation->getComposante()->setCircuitSignature(null);
        $this->em->remove($this->inscription);
        $this->decision
            ->setEtat($this->etatInitial)
            ->setEtatSignature(null)
            ->setIdDocumentParapheur(null)
            ->setUidDemandeurSignature(null);
        $this->em->flush();

        parent::tearDown();
    }

    public function testDecisionIsDepositedInCircuitOfComposante(): void
    {
        $parapheur = self::getContainer()->get(ParapheurFactice::class);

        $this->handler()->__invoke(new SignatureDecisionDemandeeMessage($this->decision->getId(), 'gestionnaire'));

        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $this->decision->getEtat());
        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE, $this->decision->getEtatSignature());
        $this->assertSame('gestionnaire', $this->decision->getUidDemandeurSignature());
        $documentId = $this->decision->getIdDocumentParapheur();
        $this->assertSame('circuit-test', $parapheur->documents()[$documentId]['circuit']);
        // par défaut, OASIS enverra la décision signée : le parapheur n'a pas l'adresse, et rien ne part encore
        $this->assertNull($parapheur->documents()[$documentId]['destinataire']);
        $this->assertEmailCount(0);
    }

    public function testReplayedMessageDoesNotDepositTwice(): void
    {
        $this->handler()->__invoke(new SignatureDecisionDemandeeMessage($this->decision->getId(), 'gestionnaire'));
        $documentId = $this->decision->getIdDocumentParapheur();

        $this->handler()->__invoke(new SignatureDecisionDemandeeMessage($this->decision->getId(), 'gestionnaire'));

        $this->assertSame($documentId, $this->decision->getIdDocumentParapheur());
        $this->assertCount(1, self::getContainer()->get(ParapheurFactice::class)->documents());
    }

    public function testFailedDepositIsRetriedAnHourLater(): void
    {
        $container = self::getContainer();
        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        $transport->reset();

        // parapheur injoignable : le dépôt échoue à chaque appel
        $parapheur = new class extends AbstractParapheur {
            public function getProviderId(): string
            {
                return 'injoignable';
            }

            public function deposer(string $pdf, string $circuit, string $libelle, ?string $destinataire): string
            {
                throw new ParapheurException('Parapheur injoignable');
            }

            public function suivre(string $documentId): SuiviSignature
            {
                throw new ParapheurException('Parapheur injoignable');
            }

            public function telecharger(string $documentId): string
            {
                throw new ParapheurException('Parapheur injoignable');
            }
        };
        $bus = $container->get(MessageBusInterface::class);
        $handler = new SignatureDecisionDemandeeMessageHandler(
            $container->get(DecisionAmenagementExamensRepository::class),
            $container->get(DecisionAmenagementEditionNormalizer::class),
            $container->get(PdfEncoder::class),
            new SignatureElectronique($parapheur, $container->get(DecisionAmenagementExamensRepository::class), new NullLogger(), $bus),
            new NullLogger(),
            $bus,
        );

        try {
            $handler->__invoke(new SignatureDecisionDemandeeMessage($this->decision->getId(), 'gestionnaire'));

            // la demande repart dans la file, différée d'une heure, au lieu d'être rejouée aussitôt
            $envoyes = $transport->getSent();
            $this->assertCount(1, $envoyes);
            $this->assertInstanceOf(SignatureDecisionDemandeeMessage::class, $envoyes[0]->getMessage());
            $this->assertSame(3600000, $envoyes[0]->last(DelayStamp::class)?->getDelay());
            // toujours verrouillée, en attente du dépôt
            $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $this->decision->getEtat());
            $this->assertNull($this->decision->getIdDocumentParapheur());
        } finally {
            $transport->reset();
        }
    }

    public function testDecisionReturnsToValideWhenCircuitIsRemovedBeforeDeposit(): void
    {
        $this->formation->getComposante()->setCircuitSignature(null);
        $this->em->flush();

        $this->handler()->__invoke(new SignatureDecisionDemandeeMessage($this->decision->getId(), 'gestionnaire'));

        // l'administrateur l'enverra par e-mail
        $this->assertSame(DecisionAmenagementExamens::ETAT_VALIDE, $this->decision->getEtat());
        $this->assertNull($this->decision->getIdDocumentParapheur());
    }

    private function handler(): SignatureDecisionDemandeeMessageHandler
    {
        return self::getContainer()->get(SignatureDecisionDemandeeMessageHandler::class);
    }
}
