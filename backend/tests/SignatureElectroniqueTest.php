<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\Entity\Composante;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\Formation;
use App\Entity\Inscription;
use App\Entity\Utilisateur;
use App\Message\SignatureDecisionDemandeeMessage;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Service\Signature\AbstractParapheur;
use App\Service\Signature\ParapheurDesactive;
use App\Service\Signature\ParapheurFactice;
use App\Service\Signature\SignatureElectronique;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class SignatureElectroniqueTest extends TestCase
{
    private const string EMAIL = 'camille.aubert@univ.example';

    public function testNoCircuitWithoutParapheur(): void
    {
        $signature = $this->signature(new ParapheurDesactive());

        $this->assertNull($signature->circuitPour($this->decision()));
    }

    public function testCircuitOfComposanteWithParapheur(): void
    {
        $signature = $this->signature(new ParapheurFactice());

        $this->assertSame('circuit-ufr1', $signature->circuitPour($this->decision()));
    }

    public function testComposanteWithoutCircuitGetsNoCircuit(): void
    {
        $signature = $this->signature(new ParapheurFactice());

        $this->assertNull($signature->circuitPour($this->decision(['UFR9' => null])));
    }

    public function testComposantesSharingCircuitGetThatCircuit(): void
    {
        $signature = $this->signature(new ParapheurFactice());
        $decision = $this->decision(['UFR1' => 'circuit-commun', 'UFR2' => 'circuit-commun']);

        $this->assertSame('circuit-commun', $signature->circuitPour($decision));
    }

    public function testDistinctCircuitsGiveNoCircuit(): void
    {
        $signature = $this->signature(new ParapheurFactice());
        $decision = $this->decision(['UFR1' => 'circuit-ufr1', 'UFR2' => 'circuit-ufr2']);

        $this->assertNull($signature->circuitPour($decision));
    }

    public function testSecondaryInscriptionDoesNotChooseCircuit(): void
    {
        $signature = $this->signature(new ParapheurFactice());
        // principale dans une composante sans circuit, secondaire dans une composante qui en a un
        $decision = $this->decision(['UFR1' => null, 'UFR2' => 'circuit-ufr2'], secondaires: ['UFR2']);

        $this->assertNull($signature->circuitPour($decision));
        $this->assertNull($signature->motifSansCircuit($decision));
    }

    public function testCircuitIsThatOfPrincipalInscription(): void
    {
        $signature = $this->signature(new ParapheurFactice());
        $decision = $this->decision(['UFR1' => 'circuit-ufr1', 'UFR2' => 'circuit-ufr2'], secondaires: ['UFR2']);

        // une inscription secondaire à un autre circuit ne bloque plus l'envoi
        $this->assertSame('circuit-ufr1', $signature->circuitPour($decision));
        $this->assertNull($signature->motifSansCircuit($decision));
    }

    public function testEndedInscriptionIsIgnored(): void
    {
        $signature = $this->signature(new ParapheurFactice());

        $this->assertNull($signature->circuitPour($this->decision(enCours: false)));
    }

    public function testNoCurrentInscriptionBlocksSignature(): void
    {
        $signature = $this->signature(new ParapheurFactice());

        $this->assertStringContainsString('Aucune inscription en cours', $signature->motifSansCircuit($this->decision(enCours: false)));
    }

    public function testDistinctCircuitsBlockSignature(): void
    {
        $signature = $this->signature(new ParapheurFactice());
        $decision = $this->decision(['UFR1' => 'circuit-ufr1', 'UFR2' => 'circuit-ufr2']);

        $this->assertStringContainsString('plusieurs composantes', $signature->motifSansCircuit($decision));
    }

    public function testComposanteWithoutCircuitKeepsEmail(): void
    {
        $signature = $this->signature(new ParapheurFactice());

        // activation progressive : la composante sans circuit n'est pas un blocage
        $this->assertNull($signature->motifSansCircuit($this->decision(['UFR9' => null])));
    }

    public function testNothingBlocksWithoutParapheur(): void
    {
        $signature = $this->signature(new ParapheurDesactive());

        $this->assertNull($signature->motifSansCircuit($this->decision(enCours: false)));
    }

    public function testDeposerStartsSignatureAndClearsPreviousSignature(): void
    {
        $parapheur = new ParapheurFactice();
        $decision = $this->decision()
            ->setEtatSignature(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE)
            ->setDateSignature(new DateTime('2026-01-15'))
            ->setDerniereVerificationSignature(new DateTime('2026-01-15'));

        $this->signature($parapheur)->deposer($decision, '%PDF-test', 'circuit-ufr1', 'gestionnaire');

        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $decision->getEtat());
        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE, $decision->getEtatSignature());
        $this->assertSame('gestionnaire', $decision->getUidDemandeurSignature());
        $this->assertNull($decision->getDateSignature());
        $this->assertNull($decision->getDerniereVerificationSignature());
        $this->assertSame('%PDF-test', $parapheur->telecharger($decision->getIdDocumentParapheur()));
    }

    public function testParapheurReceivesAddressOnlyWhenItSendsTheDocument(): void
    {
        $parapheur = new ParapheurFactice();
        $parOasis = $this->decision();
        $parLeParapheur = $this->decision();

        $this->signature($parapheur)->deposer($parOasis, '%PDF-test', 'circuit-ufr1', 'gestionnaire');
        $this->signature($parapheur, parapheurEnvoieDocument: true)->deposer($parLeParapheur, '%PDF-test', 'circuit-ufr1', 'gestionnaire');

        $this->assertNull($parapheur->documents()[$parOasis->getIdDocumentParapheur()]['destinataire']);
        $this->assertSame(self::EMAIL, $parapheur->documents()[$parLeParapheur->getIdDocumentParapheur()]['destinataire']);
    }

    public function testParapheurReceivesLibelleOfEtablissement(): void
    {
        $parapheur = new ParapheurFactice();
        $parDefaut = $this->decision();
        $personnalisee = $this->decision();

        $this->signature($parapheur)->deposer($parDefaut, '%PDF-test', 'circuit-ufr1', 'gestionnaire');
        $this->signature($parapheur, libelleDecision: "plan d'accompagnement")->deposer($personnalisee, '%PDF-test', 'circuit-ufr1', 'gestionnaire');

        $this->assertSame("Décision d'aménagements - Camille Aubert", $parapheur->documents()[$parDefaut->getIdDocumentParapheur()]['libelle']);
        $this->assertSame("Plan d'accompagnement - Camille Aubert", $parapheur->documents()[$personnalisee->getIdDocumentParapheur()]['libelle']);
    }

    public function testDeposerRequiresBeneficiaireEmail(): void
    {
        $parapheur = new ParapheurFactice();

        try {
            $this->signature($parapheur)->deposer($this->decision(email: null), '%PDF-test', 'circuit-ufr1', 'gestionnaire');
            $this->fail('Dépôt accepté sans adresse e-mail');
        } catch (RuntimeException) {
            // sans adresse, l'étudiant ne recevrait jamais le document signé : rien n'est déposé
            $this->assertSame([], $parapheur->documents());
        }
    }

    public function testDecisionOfComposanteWithCircuitGoesToSignature(): void
    {
        $decision = $this->decision();
        new ReflectionProperty(DecisionAmenagementExamens::class, 'id')->setValue($decision, 42);
        $decision->setEtat(DecisionAmenagementExamens::ETAT_VALIDE)
            ->setEtatSignature(DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')
            ->with(self::callback(fn(SignatureDecisionDemandeeMessage $message) => 42 === $message->getIdDecision()
                && 'gestionnaire' === $message->getUidDemandeur()))
            ->willReturnCallback(fn(object $message) => new Envelope($message));

        $this->assertTrue($this->signature(new ParapheurFactice(), bus: $bus)->demanderSignature($decision, 'gestionnaire'));

        // verrouillée dès la demande ; sans document ni état du parapheur, elle est en cours de dépôt
        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $decision->getEtat());
        $this->assertNull($decision->getEtatSignature());
        $this->assertNull($decision->getIdDocumentParapheur());
    }

    public function testDecisionOfComposanteWithoutCircuitKeepsCurrentWorkflow(): void
    {
        $decision = $this->decision(['UFR2' => null])->setEtat(DecisionAmenagementExamens::ETAT_VALIDE);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $this->assertFalse($this->signature(new ParapheurFactice(), bus: $bus)->demanderSignature($decision, 'gestionnaire'));

        $this->assertSame(DecisionAmenagementExamens::ETAT_VALIDE, $decision->getEtat());
    }

    public function testDepositedDecisionIsEnCours(): void
    {
        $signature = $this->signature(new ParapheurFactice());
        $decision = $this->decision();

        $this->assertFalse($signature->estEnCours($decision));
        $signature->deposer($decision, '%PDF-test', 'circuit-ufr1', 'gestionnaire');
        $this->assertTrue($signature->estEnCours($decision));
    }

    private function signature(
        AbstractParapheur $parapheur,
        bool $parapheurEnvoieDocument = false,
        ?string $libelleDecision = null,
        ?MessageBusInterface $bus = null,
    ): SignatureElectronique {
        return new SignatureElectronique(
            $parapheur,
            $this->createMock(DecisionAmenagementExamensRepository::class),
            new NullLogger(),
            $bus ?? $this->createMock(MessageBusInterface::class),
            $parapheurEnvoieDocument,
            $libelleDecision,
        );
    }

    /**
     * @param array<string, ?string> $circuits circuit de signature par code composante
     * @param list<string> $secondaires codes des composantes inscrites à titre secondaire
     */
    private function decision(
        array $circuits = ['UFR1' => 'circuit-ufr1'],
        bool $enCours = true,
        ?string $email = self::EMAIL,
        array $secondaires = [],
    ): DecisionAmenagementExamens {
        $beneficiaire = new Utilisateur();
        $beneficiaire->setPrenom('Camille');
        $beneficiaire->setNom('Aubert');
        if (null !== $email) {
            $beneficiaire->setEmail($email);
        }

        foreach ($circuits as $code => $circuit) {
            $composante = new Composante();
            $composante->setCodeExterne($code)->setCircuitSignature($circuit);

            $formation = new Formation();
            $formation->setComposante($composante);

            $inscription = new Inscription();
            $inscription->setFormation($formation);
            $inscription->setDebut(new DateTime($enCours ? '-1 month' : '-13 months'));
            $inscription->setFin(new DateTime($enCours ? '+1 month' : '-1 month'));
            $inscription->setPrincipale(!in_array($code, $secondaires, true));

            $beneficiaire->addInscription($inscription);
        }

        $decision = new DecisionAmenagementExamens()
            ->setBeneficiaire($beneficiaire)
            ->setDebut(new DateTime('-1 month'))
            ->setFin(new DateTime('+11 months'));
        $beneficiaire->addDecisionsAmenagementExamen($decision);

        return $decision;
    }
}
