<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests;

use App\Entity\Amenagement;
use App\Entity\AvisEse;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\Formation;
use App\Entity\Inscription;
use App\Entity\TypeAmenagement;
use App\Entity\Utilisateur;
use App\Message\SignatureDecisionDemandeeMessage;
use App\Service\Signature\ParapheurFactice;
use DateTime;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * Parcours de la décision avec un parapheur : la demande du gestionnaire part au parapheur, puis la
 * décision et ce qu'elle reprend ne changent plus jusqu'à la fin du circuit.
 */
class SignatureDecisionTest extends ApiTestCaseCustom
{
    private const string DECISION = '/utilisateurs/beneficiaire-decision/decisions/2025';

    private ?int $inscription = null;
    private ?int $amenagement = null;
    private ?int $avisEse = null;
    private ?int $decisionEnCours = null;
    /** @var array{int, bool}|null type d'aménagement inclus dans la décision par le test, et sa valeur d'origine */
    private ?array $typeDecision = null;
    private bool $inscriptionsTerminees = false;

    protected function tearDown(): void
    {
        // les fixtures servent aux autres classes de test : décision validée, sans circuit ni inscription ajoutée
        $manager = static::getContainer()->get('doctrine')->getManager();
        $decision = $this->decision();
        $decision->setEtat(DecisionAmenagementExamens::ETAT_VALIDE);
        $decision->setEtatSignature(null)->setIdDocumentParapheur(null)
            ->setUidDemandeurSignature(null)->setDateSignature(null)->setDerniereVerificationSignature(null)
            ->setFichier(null);
        $manager->getRepository(Formation::class)->findOneBy(['codeExterne' => 'CODE_F_1'])
            ->getComposante()->setCircuitSignature(null);
        if (null !== $this->inscription) {
            $manager->remove($manager->find(Inscription::class, $this->inscription));
        }
        if ($this->inscriptionsTerminees) {
            foreach ($manager->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire-decision'])->getInscriptions() as $inscription) {
                $inscription->setFin(new DateTime('next year'));
            }
        }
        if (null !== $this->amenagement) {
            $manager->remove($manager->find(Amenagement::class, $this->amenagement));
        }
        // supprimé par le test s'il n'a pas été refusé
        $avis = null === $this->avisEse ? null : $manager->find(AvisEse::class, $this->avisEse);
        if (null !== $avis) {
            $manager->remove($avis);
        }
        if (null !== $this->typeDecision) {
            [$id, $decisionAvant] = $this->typeDecision;
            $manager->find(TypeAmenagement::class, $id)->setDecision($decisionAvant);
        }
        if (null !== $this->decisionEnCours) {
            $manager->remove($manager->find(DecisionAmenagementExamens::class, $this->decisionEnCours));
        }
        $manager->flush();

        parent::tearDown();
    }

    public function testGestionnaireRequestGoesToParapheurWhenComposanteHasCircuit(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->inscrireDansUneComposanteAvecCircuit();
        $this->etatDecision(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['etat' => DecisionAmenagementExamens::ETAT_VALIDE],
        ]);

        $this->assertResponseIsSuccessful();
        // le circuit remplace l'envoi par l'administrateur : verrouillée dès la demande, le dépôt suit en asynchrone
        $this->assertJsonContains(['etat' => DecisionAmenagementExamens::ETAT_EN_SIGNATURE]);
        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $this->decision()->getEtat());
        $this->assertNull($this->decision()->getIdDocumentParapheur());
        $this->assertDepotDemande();
    }

    public function testAdministrateurSendGoesToParapheurWhenComposanteHasCircuit(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $this->inscrireDansUneComposanteAvecCircuit();
        $this->etatDecision(DecisionAmenagementExamens::ETAT_VALIDE);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['etat' => DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE],
        ]);

        $this->assertResponseIsSuccessful();
        // pas d'envoi par e-mail sans signature : la décision part au parapheur comme à la demande du gestionnaire
        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $this->decision()->getEtat());
        $this->assertDepotDemande();
    }

    public function testGestionnaireRequestAwaitsAdministrateurWithoutCircuit(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->etatDecision(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['etat' => DecisionAmenagementExamens::ETAT_VALIDE],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(DecisionAmenagementExamens::ETAT_VALIDE, $this->decision()->getEtat());
    }

    public function testRequestIsRefusedWithoutCurrentInscriptionWhenParapheurIsConfigured(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->terminerLesInscriptions();
        $this->etatDecision(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['etat' => DecisionAmenagementExamens::ETAT_VALIDE],
        ]);

        // sans inscription en cours, la composante et son circuit sont inconnus : pas d'envoi sans signature
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'etat']]]);
        $this->assertSame(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS, $this->decision()->getEtat());
    }

    public function testDecisionEnSignatureCannotBeModified(): void
    {
        $client = $this->createClientWithCredentials('admin');
        $this->etatDecision(DecisionAmenagementExamens::ETAT_EN_SIGNATURE);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['etat' => DecisionAmenagementExamens::ETAT_EDITION_DEMANDEE],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $this->decision()->getEtat());
    }

    public function testRefusedDecisionMustBeResumedBeforeNewRequest(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->inscrireDansUneComposanteAvecCircuit();
        $this->etatDecision(DecisionAmenagementExamens::ETAT_REFUSEE);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['etat' => DecisionAmenagementExamens::ETAT_VALIDE],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(DecisionAmenagementExamens::ETAT_REFUSEE, $this->decision()->getEtat());
    }

    public function testGestionnaireResumesRefusedDecisionThenRequestsItAgain(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->inscrireDansUneComposanteAvecCircuit();
        $this->etatDecision(DecisionAmenagementExamens::ETAT_REFUSEE);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['etat' => DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['etat' => DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS]);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['etat' => DecisionAmenagementExamens::ETAT_VALIDE],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $this->decision()->getEtat());
    }

    public function testResumeIsRefusedOutsideRefusal(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->etatDecision(DecisionAmenagementExamens::ETAT_EN_SIGNATURE);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['etat' => DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $this->decision()->getEtat());
    }

    public function testAmenagementInDecisionIsLockedUntilRefusedDecisionIsResumed(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->decisionEnCours(DecisionAmenagementExamens::ETAT_REFUSEE);

        $client->request('PATCH', '/utilisateurs/beneficiaire-decision/amenagements/' . $this->amenagementDecision(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['commentaire' => 'Salle isolée'],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAmenagementInDecisionIsLockedWhileEnSignature(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->decisionEnCours(DecisionAmenagementExamens::ETAT_EN_SIGNATURE);

        $client->request('PATCH', '/utilisateurs/beneficiaire-decision/amenagements/' . $this->amenagementDecision(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['commentaire' => 'Salle isolée'],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAmenagementInDecisionCannotBeDeletedWhileEnSignature(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->decisionEnCours(DecisionAmenagementExamens::ETAT_EN_SIGNATURE);

        $client->request('DELETE', '/utilisateurs/beneficiaire-decision/amenagements/' . $this->amenagementDecision());

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAvisEseCannotBeDeletedWhileDecisionEnSignature(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->decisionEnCours(DecisionAmenagementExamens::ETAT_EN_SIGNATURE);

        $client->request('DELETE', '/utilisateurs/beneficiaire-decision/avis_ese/' . $this->avisEse());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('en cours de signature électronique', $client->getResponse()->getContent(false));
    }

    public function testAmenagementOutsideDecisionStaysEditableWhileEnSignature(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->decisionEnCours(DecisionAmenagementExamens::ETAT_EN_SIGNATURE);
        $id = $this->amenagementDecision();
        // le type n'est plus repris dans la décision : le document signé ne le cite pas
        $this->typeHorsDecision();

        $client->request('PATCH', '/utilisateurs/beneficiaire-decision/amenagements/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['commentaire' => 'hors décision'],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testAmenagementWithoutTypeIsRefusedByValidation(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');

        // la contrainte du verrou ne doit pas masquer le NotNull du type
        $client->request('POST', '/utilisateurs/beneficiaire-decision/amenagements', [
            'json' => ['commentaire' => 'sans type', 'semestre1' => true],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAvisEseIsLockedWhileDecisionEnSignature(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->decisionEnCours(DecisionAmenagementExamens::ETAT_EN_SIGNATURE);

        $client->request('POST', '/utilisateurs/beneficiaire-decision/avis_ese', [
            'json' => [
                'libelle' => 'Avis pendant la signature',
                'debut' => new DateTime()->format('Y-m-d'),
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGestionnaireChecksSignedDecisionWithoutWaitingForSchedule(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $documentId = $this->enSignatureDansLeParapheur();
        static::getContainer()->get(ParapheurFactice::class)->marquerSignee($documentId);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['verifierSignature' => true],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['etat' => DecisionAmenagementExamens::ETAT_EDITE]);
        $decision = $this->decision();
        $this->assertSame(DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE, $decision->getEtatSignature());
        $this->assertNotNull($decision->getFichier());
    }

    public function testGestionnaireChecksRefusedDecision(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $documentId = $this->enSignatureDansLeParapheur();
        static::getContainer()->get(ParapheurFactice::class)->marquerRefusee($documentId);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['verifierSignature' => true],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['etat' => DecisionAmenagementExamens::ETAT_REFUSEE]);
    }

    public function testCheckKeepsDecisionEnSignatureUntilCircuitEnds(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->enSignatureDansLeParapheur();

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['verifierSignature' => true],
        ]);

        $this->assertResponseIsSuccessful();
        $decision = $this->decision();
        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $decision->getEtat());
        // la fiche affiche l'heure de cette vérification
        $this->assertNotNull($decision->getDerniereVerificationSignature());
    }

    public function testCheckIsRefusedOutsideSignature(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->etatDecision(DecisionAmenagementExamens::ETAT_VALIDE);

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['verifierSignature' => true],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCheckCannotChangeEtat(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $this->enSignatureDansLeParapheur();

        $client->request('PATCH', self::DECISION, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['verifierSignature' => true, 'etat' => DecisionAmenagementExamens::ETAT_VALIDE],
        ]);

        // refusée avant toute interrogation du parapheur
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $this->decision()->getEtat());
        $this->assertNull($this->decision()->getDerniereVerificationSignature());
    }

    /** Un seul message en file : le dépôt de la décision dans le parapheur, au nom du demandeur. */
    private function assertDepotDemande(): void
    {
        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.async');
        $depots = array_filter(
            $transport->getSent(),
            fn(Envelope $envelope) => $envelope->getMessage() instanceof SignatureDecisionDemandeeMessage,
        );
        $this->assertCount(1, $depots);
        $this->assertSame($this->decision()->getId(), current($depots)->getMessage()->getIdDecision());
    }

    /** La décision 2025 visée par l'URI : le bénéficiaire en a aussi une pour l'année en cours. */
    private function decision(): DecisionAmenagementExamens
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $manager->clear();
        $beneficiaire = $manager->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire-decision']);

        return $manager->getRepository(DecisionAmenagementExamens::class)->findOneBy([
            'beneficiaire' => $beneficiaire,
            'debut' => new DateTime('2025-09-01'),
        ]);
    }

    /** Après la création du client : la requête passe par la connexion de son noyau. */
    private function etatDecision(string $etat): void
    {
        $decision = $this->decision();
        $decision->setEtat($etat);
        if (DecisionAmenagementExamens::ETAT_EN_SIGNATURE === $etat) {
            $decision->setIdDocumentParapheur(uniqid('factice-', true));
        }
        static::getContainer()->get('doctrine')->getManager()->flush();
    }

    /** Décision dont la période n'est pas terminée, la seule qui verrouille ce qu'elle reprend. */
    private function decisionEnCours(string $etat): void
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $decision = new DecisionAmenagementExamens()
            ->setBeneficiaire($manager->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire-decision']))
            ->setDebut(new DateTime('-1 month'))
            ->setFin(new DateTime('+11 months'))
            ->setDateModification(new DateTime())
            ->setEtat($etat);
        $manager->persist($decision);
        $manager->flush();
        $this->decisionEnCours = $decision->getId();
    }

    /** Décision déposée dans le parapheur factice, avec le gestionnaire comme demandeur. */
    private function enSignatureDansLeParapheur(): string
    {
        $documentId = static::getContainer()->get(ParapheurFactice::class)
            ->deposer('%PDF-1.4 décision', 'circuit-test', 'Décision d\'aménagements', 'beneficiaire-decision@app.fr');
        $decision = $this->decision();
        $decision->setEtat(DecisionAmenagementExamens::ETAT_EN_SIGNATURE)
            ->setEtatSignature(DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE)
            ->setIdDocumentParapheur($documentId)
            ->setUidDemandeurSignature('gestionnaire');
        static::getContainer()->get('doctrine')->getManager()->flush();

        return $documentId;
    }

    /** Plus d'inscription en cours : la composante du bénéficiaire devient inconnue. */
    private function terminerLesInscriptions(): void
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $beneficiaire = $manager->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire-decision']);
        foreach ($beneficiaire->getInscriptions() as $inscription) {
            $inscription->setFin(new DateTime('-1 day'));
        }
        $manager->flush();
        $this->inscriptionsTerminees = true;
    }

    private function inscrireDansUneComposanteAvecCircuit(?string $circuit = 'circuit-test'): void
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $formation = $manager->getRepository(Formation::class)->findOneBy(['codeExterne' => 'CODE_F_1']);
        $formation->getComposante()->setCircuitSignature($circuit);

        $inscription = new Inscription()
            ->setFormation($formation)
            ->setDebut(new DateTime('-1 month'))
            ->setFin(new DateTime('+1 month'))
            ->setPrincipale(true);
        $manager->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire-decision'])
            ->addInscription($inscription);
        $manager->persist($inscription);
        $manager->flush();
        $this->inscription = $inscription->getId();
    }

    /** Un aménagement d'examens du bénéficiaire, d'un type inclus dans la décision. */
    private function amenagementDecision(): int
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $utilisateur = $manager->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire-decision']);
        $type = $manager->getRepository(TypeAmenagement::class)->findOneBy(['examens' => true]);
        $this->typeDecision = [$type->getId(), (bool) $type->isDecision()];
        $type->setDecision(true);

        // créé ici : les autres classes de test modifient les aménagements des fixtures
        $amenagement = new Amenagement();
        $amenagement->setType($type);
        $amenagement->setDebut(new DateTime('-1 month'));
        $amenagement->setFin(new DateTime('+1 month'));
        $amenagement->setSemestre1(true);
        $amenagement->setSemestre2(true);
        $amenagement->addBeneficiaire($utilisateur->getBeneficiaires()->first());
        $manager->persist($amenagement);
        $manager->flush();
        $this->amenagement = $amenagement->getId();

        return $this->amenagement;
    }

    /** Retire de la décision le type de l'aménagement créé par amenagementDecision(). */
    private function typeHorsDecision(): void
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $manager->find(TypeAmenagement::class, $this->typeDecision[0])->setDecision(false);
        $manager->flush();
    }

    /** Un avis de santé du bénéficiaire, en cours. */
    private function avisEse(): int
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $avis = new AvisEse();
        $avis->setUtilisateur($manager->getRepository(Utilisateur::class)->findOneBy(['uid' => 'beneficiaire-decision']));
        $avis->setLibelle('Avis de test');
        $avis->setDebut(new DateTime('-1 month'));
        $manager->persist($avis);
        $manager->flush();
        $this->avisEse = $avis->getId();

        return $this->avisEse;
    }
}
