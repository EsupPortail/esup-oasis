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
use App\Service\Signature\AbstractParapheur;
use App\Service\Signature\ParapheurDesactive;
use App\Service\Signature\ParapheurFactice;
use App\Service\Signature\SignatureElectronique;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

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

    public function testDeposerHandsPdfToParapheurAndReturnsItsDocument(): void
    {
        $parapheur = new ParapheurFactice();

        $documentId = $this->signature($parapheur)->deposer($this->decision(), '%PDF-test', 'circuit-ufr1');

        $this->assertSame('%PDF-test', $parapheur->telecharger($documentId));
        $this->assertSame('circuit-ufr1', $parapheur->documents()[$documentId]['circuit']);
    }

    public function testParapheurReceivesAddressOnlyWhenItSendsTheDocument(): void
    {
        $parapheur = new ParapheurFactice();

        $parOasis = $this->signature($parapheur)->deposer($this->decision(), '%PDF-test', 'circuit-ufr1');
        $parLeParapheur = $this->signature($parapheur, parapheurEnvoieDocument: true)->deposer($this->decision(), '%PDF-test', 'circuit-ufr1');

        $this->assertNull($parapheur->documents()[$parOasis]['destinataire']);
        $this->assertSame(self::EMAIL, $parapheur->documents()[$parLeParapheur]['destinataire']);
    }

    public function testParapheurReceivesLibelleOfEtablissement(): void
    {
        $parapheur = new ParapheurFactice();

        $parDefaut = $this->signature($parapheur)->deposer($this->decision(), '%PDF-test', 'circuit-ufr1');
        $personnalisee = $this->signature($parapheur, libelleDecision: "plan d'accompagnement")->deposer($this->decision(), '%PDF-test', 'circuit-ufr1');

        $this->assertSame("Décision d'aménagements - Camille Aubert", $parapheur->documents()[$parDefaut]['libelle']);
        $this->assertSame("Plan d'accompagnement - Camille Aubert", $parapheur->documents()[$personnalisee]['libelle']);
    }

    public function testDeposerRequiresBeneficiaireEmail(): void
    {
        $parapheur = new ParapheurFactice();

        try {
            $this->signature($parapheur)->deposer($this->decision(email: null), '%PDF-test', 'circuit-ufr1');
            $this->fail('Dépôt accepté sans adresse e-mail');
        } catch (RuntimeException) {
            // sans adresse, l'étudiant ne recevrait jamais le document signé : rien n'est déposé
            $this->assertSame([], $parapheur->documents());
        }
    }

    public function testDepositedDecisionIsEnCours(): void
    {
        $signature = $this->signature(new ParapheurFactice());
        $decision = $this->decision()->setEtat(DecisionAmenagementExamens::ETAT_EN_SIGNATURE);

        // en signature mais sans document : le dépôt n'est pas encore fait
        $this->assertFalse($signature->estEnCours($decision));
        $decision->setIdDocumentParapheur('document-depose');
        $this->assertTrue($signature->estEnCours($decision));
    }

    private function signature(
        AbstractParapheur $parapheur,
        bool $parapheurEnvoieDocument = false,
        ?string $libelleDecision = null,
    ): SignatureElectronique {
        return new SignatureElectronique($parapheur, new NullLogger(), $parapheurEnvoieDocument, $libelleDecision);
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
