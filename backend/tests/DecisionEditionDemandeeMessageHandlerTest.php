<?php

namespace App\Tests;

use App\Entity\DecisionAmenagementExamens;
use App\Entity\Formation;
use App\Entity\Inscription;
use App\Message\DecisionEditionDemandeeMessage;
use App\MessageHandler\DecisionEditionDemandeeMessageHandler;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Serializer\DecisionAmenagementEditionNormalizer;
use App\Serializer\Encoder\PdfEncoder;
use App\Service\Decision\ArchivageDecision;
use App\Service\MailService;
use App\Service\Signature\AbstractParapheur;
use App\Service\Signature\ParapheurException;
use App\Service\Signature\ParapheurFactice;
use App\Service\Signature\SignatureElectronique;
use App\Service\Signature\SuiviSignature;
use App\State\Utilisateur\UtilisateurManager;
use DateTime;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class DecisionEditionDemandeeMessageHandlerTest extends KernelTestCase
{
    public function testInvoke(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $handler = $container->get(DecisionEditionDemandeeMessageHandler::class);
        $em = $container->get('doctrine')->getManager();

        // On cherche une décision existante pour le test
        $decision = $em->getRepository(DecisionAmenagementExamens::class)->findOneBy([]);

        if (null === $decision) {
            $this->markTestSkipped('No decision found in DB');
        }

        $message = new DecisionEditionDemandeeMessage($decision->getId(), 'admin');

        // On appelle le handler
        // Attention: cela va envoyer un mail et générer un PDF si les services ne sont pas mockés.
        // En environnement de test, le mailer est normalement mocké par Symfony.
        $handler->__invoke($message);

        $this->assertEquals(DecisionAmenagementExamens::ETAT_EDITE, $decision->getEtat());
    }

    public function testInvokeDepositsInParapheurWhenComposanteHasCircuit(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $handler = $container->get(DecisionEditionDemandeeMessageHandler::class);
        $parapheur = $container->get(ParapheurFactice::class);
        $em = $container->get('doctrine')->getManager();

        $decision = $em->getRepository(DecisionAmenagementExamens::class)->findOneBy([]);
        if (null === $decision) {
            $this->markTestSkipped('No decision found in DB');
        }
        $etatInitial = $decision->getEtat();

        // inscription en cours dans une composante reliée à un circuit
        $formation = $em->getRepository(Formation::class)->findOneBy(['codeExterne' => 'CODE_F_1']);
        $inscription = new Inscription()
            ->setFormation($formation)
            ->setDebut(new DateTime('-1 month'))
            ->setFin(new DateTime('+1 month'));
        $decision->getBeneficiaire()->addInscription($inscription);
        $formation->getComposante()->setCircuitSignature('circuit-test');
        $decision->setEtat(DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE);
        $em->persist($inscription);
        $em->flush();

        try {
            $handler->__invoke(new DecisionEditionDemandeeMessage($decision->getId(), 'admin'));

            $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $decision->getEtat());
            $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE, $decision->getEtatSignature());
            $this->assertSame('admin', $decision->getUidDemandeurSignature());
            $documentId = $decision->getIdDocumentParapheur();
            $this->assertSame(
                DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE,
                $parapheur->suivre($documentId)->etat,
            );
            // par défaut, OASIS enverra la décision signée : le parapheur n'a pas l'adresse, et rien ne part encore
            $this->assertNull($parapheur->documents()[$documentId]['destinataire']);
            $this->assertEmailCount(0);

            // message rejoué : le document n'est pas déposé une seconde fois
            $handler->__invoke(new DecisionEditionDemandeeMessage($decision->getId(), 'admin'));

            $this->assertSame($documentId, $decision->getIdDocumentParapheur());
        } finally {
            // les autres tests attendent l'envoi par e-mail
            $formation->getComposante()->setCircuitSignature(null);
            $em->remove($inscription);
            $decision
                ->setEtat($etatInitial)
                ->setEtatSignature(null)
                ->setIdDocumentParapheur(null)
                ->setUidDemandeurSignature(null);
            $em->flush();
        }
    }

    public function testFailedDepositIsRetriedAnHourLater(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();
        $bus = $container->get(MessageBusInterface::class);

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
        $handler = new DecisionEditionDemandeeMessageHandler(
            $container->get(DecisionAmenagementExamensRepository::class),
            $container->get(DecisionAmenagementEditionNormalizer::class),
            $container->get(UtilisateurManager::class),
            $container->get(PdfEncoder::class),
            $container->get(MailService::class),
            new NullLogger(),
            $bus,
            $container->get(ArchivageDecision::class),
            new SignatureElectronique($parapheur, $container->get(DecisionAmenagementExamensRepository::class), new NullLogger()),
        );

        $decision = $em->getRepository(DecisionAmenagementExamens::class)->findOneBy([]);
        if (null === $decision) {
            $this->markTestSkipped('No decision found in DB');
        }
        $etatInitial = $decision->getEtat();

        $formation = $em->getRepository(Formation::class)->findOneBy(['codeExterne' => 'CODE_F_1']);
        $inscription = new Inscription()
            ->setFormation($formation)
            ->setDebut(new DateTime('-1 month'))
            ->setFin(new DateTime('+1 month'));
        $decision->getBeneficiaire()->addInscription($inscription);
        $formation->getComposante()->setCircuitSignature('circuit-test');
        $decision->setEtat(DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE);
        $em->persist($inscription);
        $em->flush();

        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        $transport->reset();

        try {
            $handler->__invoke(new DecisionEditionDemandeeMessage($decision->getId(), 'admin'));

            // la demande repart dans la file, différée d'une heure, au lieu d'être rejouée aussitôt
            $envoyes = $transport->getSent();
            $this->assertCount(1, $envoyes);
            $this->assertInstanceOf(DecisionEditionDemandeeMessage::class, $envoyes[0]->getMessage());
            $this->assertSame(3600000, $envoyes[0]->last(DelayStamp::class)?->getDelay());
            $this->assertSame(DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE, $decision->getEtat());
            $this->assertNull($decision->getIdDocumentParapheur());
        } finally {
            $transport->reset();
            $formation->getComposante()->setCircuitSignature(null);
            $em->remove($inscription);
            $decision
                ->setEtat($etatInitial)
                ->setEtatSignature(null)
                ->setIdDocumentParapheur(null)
                ->setUidDemandeurSignature(null);
            $em->flush();
        }
    }
}
