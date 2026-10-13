<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\Entity\DecisionAmenagementExamens;
use App\Entity\PieceJointeBeneficiaire;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Service\Decision\ArchivageDecision;
use App\Service\MailService;
use App\Service\Signature\ParapheurFactice;
use App\Service\Signature\SuiviSignatureService;
use App\State\Utilisateur\UtilisateurManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviSignatureServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ParapheurFactice $parapheur;
    private SuiviSignatureService $suivi;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->parapheur = $container->get(ParapheurFactice::class);
        $this->suivi = $container->get(SuiviSignatureService::class);
    }

    public function testSignedDecisionIsEditedAndDated(): void
    {
        [$decision, $documentId] = $this->decisionEnSignature();
        $this->parapheur->marquerSignee($documentId);

        $rapport = $this->suivi->traiterLot();

        self::assertSame(1, $rapport->signees);
        self::assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE, $decision->getEtatSignature());
        self::assertSame(DecisionAmenagementExamens::ETAT_EDITE, $decision->getEtat());
        self::assertNotNull($decision->getDateSignature());
        self::assertEqualsWithDelta(time(), $decision->getDateSignature()->getTimestamp(), 60);
    }

    public function testSignedDecisionIsSentByOasisByDefault(): void
    {
        [, $documentId] = $this->decisionEnSignature();
        $this->parapheur->marquerSignee($documentId);

        $this->suivi->traiterLot();

        // même e-mail que l'envoi sans parapheur, avec le document signé en pièce jointe
        self::assertEmailCount(1);
        self::assertEmailAttachmentCount(self::getMailerMessage(), 1);
    }

    public function testParapheurSendsSignedDecisionItself(): void
    {
        [$decision, $documentId] = $this->decisionEnSignature();
        $this->parapheur->marquerSignee($documentId);
        $container = self::getContainer();
        $suivi = new SuiviSignatureService(
            $container->get(DecisionAmenagementExamensRepository::class),
            $this->parapheur,
            $container->get(ArchivageDecision::class),
            $container->get(UtilisateurManager::class),
            new NullLogger(),
            $container->get(MailService::class),
            parapheurEnvoieDocument: true,
        );

        $suivi->traiterLot();

        self::assertSame(DecisionAmenagementExamens::ETAT_EDITE, $decision->getEtat());
        self::assertEmailCount(0);
    }

    public function testSignedDecisionStaysEditedWhenEmailFails(): void
    {
        [$decision, $documentId] = $this->decisionEnSignature();
        $this->parapheur->marquerSignee($documentId);
        $container = self::getContainer();
        $suivi = new SuiviSignatureService(
            $container->get(DecisionAmenagementExamensRepository::class),
            $this->parapheur,
            $container->get(ArchivageDecision::class),
            $container->get(UtilisateurManager::class),
            new NullLogger(),
            // MailService est readonly, donc non doublable : une sous-classe sans ses dépendances
            new ReflectionClass(MailServiceEnEchec::class)->newInstanceWithoutConstructor(),
        );

        $rapport = $suivi->traiterLot();

        // signée et au dossier : l'e-mail manqué ne rouvre pas la décision et n'interrompt pas le lot
        self::assertSame(1, $rapport->signees);
        self::assertSame(DecisionAmenagementExamens::ETAT_EDITE, $decision->getEtat());
        self::assertNotNull($decision->getFichier());
    }

    public function testDecisionInProgressStaysEnSignature(): void
    {
        [$decision] = $this->decisionEnSignature();

        $rapport = $this->suivi->traiterLot();

        self::assertSame(0, $rapport->signees);
        self::assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE, $decision->getEtatSignature());
        self::assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $decision->getEtat());
        self::assertNull($decision->getDateSignature());
        self::assertNotNull($decision->getDerniereVerificationSignature());
    }

    public function testRefusedDecisionIsRefusee(): void
    {
        [$decision, $documentId] = $this->decisionEnSignature();
        $this->parapheur->marquerRefusee($documentId);

        $rapport = $this->suivi->traiterLot();

        self::assertSame(1, $rapport->refusees);
        self::assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE, $decision->getEtatSignature());
        self::assertSame(DecisionAmenagementExamens::ETAT_REFUSEE, $decision->getEtat());
        self::assertNull($decision->getDateSignature());
    }

    public function testRefusedDocumentIsFiledForHistory(): void
    {
        [$decision, $documentId] = $this->decisionEnSignature();
        $fichierDecision = $decision->getFichier();
        $this->parapheur->marquerRefusee($documentId);

        $this->suivi->traiterLot();

        $copie = $this->em->getRepository(PieceJointeBeneficiaire::class)->findOneBy(
            ['beneficiaire' => $decision->getBeneficiaire()],
            ['id' => 'DESC'],
        );
        // date lue sur la copie : le libellé est daté au dépôt, pas au moment de l'assertion
        self::assertSame(
            "Décision d'aménagements refusée au " . $copie->getDateDepot()->format('d/m/Y'),
            $copie->getLibelle(),
        );
        // la copie refusée n'est pas le document de la décision
        self::assertSame($fichierDecision, $decision->getFichier());
    }

    public function testRefusedDecisionWithoutRequesterIsRetried(): void
    {
        [$decision, $documentId] = $this->decisionEnSignature();
        $decision->setUidDemandeurSignature(null);
        $this->em->flush();
        $this->parapheur->marquerRefusee($documentId);

        $rapport = $this->suivi->traiterLot();

        // sans copie au dossier, la décision ne sort pas de la signature
        self::assertSame(1, $rapport->erreurs);
        self::assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $decision->getEtat());
    }

    public function testUnknownDocumentStopsFollowUp(): void
    {
        [$decision] = $this->decisionEnSignature();
        $decision->setIdDocumentParapheur(uniqid('document-inconnu-'));
        $this->em->flush();

        $rapport = $this->suivi->traiterLot();

        self::assertSame(1, $rapport->closes);
        self::assertSame(DecisionAmenagementExamens::ETAT_REFUSEE, $decision->getEtat());
        self::assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_ERREUR, $decision->getEtatSignature());
        self::assertSame(0, $this->suivi->traiterLot()->examinees);
    }

    public function testDecisionBackInValidationIsNotFollowed(): void
    {
        [$decision] = $this->decisionEnSignature();
        $decision->setEtat(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);
        $this->em->flush();

        self::assertSame(0, $this->suivi->traiterLot()->examinees);
    }

    /**
     * @return array{0: DecisionAmenagementExamens, 1: string}
     */
    private function decisionEnSignature(): array
    {
        $decision = $this->em->getRepository(DecisionAmenagementExamens::class)->findOneBy([]);
        if (null === $decision) {
            self::markTestSkipped('Aucune décision dans les fixtures');
        }

        $documentId = $this->parapheur->deposer('%PDF-1.4 décision de test', 'circuit-test', 'Décision de test', 'beneficiaire@univ.example');
        $decision
            ->setEtat(DecisionAmenagementExamens::ETAT_EN_SIGNATURE)
            ->setEtatSignature(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE)
            ->setIdDocumentParapheur($documentId)
            ->setUidDemandeurSignature('admin')
            ->setDateSignature(null)
            ->setDerniereVerificationSignature(null);
        $this->em->flush();

        return [$decision, $documentId];
    }
}

/** Échoue à l'envoi, comme sur une adresse invalide. */
readonly class MailServiceEnEchec extends MailService
{
    public function envoyerDecision(DecisionAmenagementExamens $decision, string $pdf): void
    {
        throw new RuntimeException('adresse invalide');
    }
}
