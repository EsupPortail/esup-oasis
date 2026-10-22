<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

declare(strict_types=1);

namespace App\Tests\State\DecisionAmenagementExamens;

use App\Entity\Amenagement;
use App\Entity\AvisEse;
use App\Entity\Beneficiaire;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\TypeAmenagement;
use App\Entity\Utilisateur;
use App\Repository\DecisionAmenagementExamensRepository;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use stdClass;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Filet de sécurité sur le fix isActif (granularité jour) : la décision
 * suit la durée réelle de l'aménagement. Le dernier jour, inclusif,
 * l'aménagement reste actif donc la décision doit être conservée ; le
 * lendemain il s'éteint donc la décision supprimable est retirée.
 */
final class DecisionAmenagementManagerTest extends TestCase
{
    private const string START = '2025-09-01';
    private const string END = '2026-08-31';

    private function amenagementDecision(string $now): Amenagement
    {
        $type = new TypeAmenagement()->setDecision(true);

        $amenagement = new Amenagement();
        $amenagement->setType($type);
        $amenagement->setDebut(new DateTimeImmutable(self::START));
        $amenagement->setFin(new DateTimeImmutable(self::END));
        $amenagement->setClock(new MockClock(new DateTimeImmutable($now)));
        // Un id non nul : la déduplication de Utilisateur::getAmenagementsActifs
        // indexe par id et déclencherait sinon une déprécation sur clé nulle.
        new ReflectionProperty(Amenagement::class, 'id')->setValue($amenagement, 1);

        return $amenagement;
    }

    /**
     * @param Amenagement[] $amenagements
     */
    private function beneficiaire(array $amenagements, ?DecisionAmenagementExamens $decision): Utilisateur
    {
        $benef = new Beneficiaire();
        foreach ($amenagements as $amenagement) {
            $benef->addAmenagement($amenagement);
        }

        $utilisateur = $this->createPartialMock(Utilisateur::class, [
            'getBeneficiairesActifs',
            'getEtatAvisEse',
            'getDecisionAmenagementExamens',
        ]);
        $utilisateur->method('getBeneficiairesActifs')->willReturn([$benef]);
        // Pas d'avis ESE en cours : l'existence de la décision ne dépend que de
        // l'aménagement, c'est ce qu'on veut isoler.
        $utilisateur->method('getEtatAvisEse')->willReturn(AvisEse::ETAT_AUCUN);
        $utilisateur->method('getDecisionAmenagementExamens')->willReturn($decision);

        return $utilisateur;
    }

    private function manager(string $now, DecisionAmenagementExamensRepository $repository): DecisionAmenagementManager
    {
        // UtilisateurManager est readonly (non doublable) et inutilisé par
        // majEtatDecision ; on instancie sans constructeur et on n'injecte que
        // les collaborateurs réellement sollicités par ce chemin.
        $reflection = new ReflectionClass(DecisionAmenagementManager::class);
        /** @var DecisionAmenagementManager $manager */
        $manager = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('decisionAmenagementExamensRepository')->setValue($manager, $repository);
        $reflection->getProperty('messageBus')->setValue($manager, $this->messageBus());
        $manager->setClock(new MockClock(new DateTimeImmutable($now)));

        return $manager;
    }

    private function messageBus(): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        return $bus;
    }

    public function testKeepsExamDecisionThroughAccommodationLastDay(): void
    {
        $decision = new DecisionAmenagementExamens()->setEtat(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);

        $repository = $this->createMock(DecisionAmenagementExamensRepository::class);
        $repository->expects(self::never())->method('remove');
        $repository->expects(self::once())->method('save');

        $now = self::END . ' 11:00:00';
        $beneficiaire = $this->beneficiaire([$this->amenagementDecision($now)], $decision);

        $this->manager($now, $repository)->majEtatDecision(
            $beneficiaire,
            new DateTimeImmutable(self::START),
            new DateTimeImmutable(self::END),
        );
    }

    public function testRemovesExamDecisionDayAfterAccommodationEnds(): void
    {
        $decision = new DecisionAmenagementExamens()->setEtat(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);

        $repository = $this->createMock(DecisionAmenagementExamensRepository::class);
        $repository->expects(self::once())->method('remove');
        $repository->expects(self::never())->method('save');

        $now = '2026-09-01 00:00:01';
        $beneficiaire = $this->beneficiaire([$this->amenagementDecision($now)], $decision);

        $this->manager($now, $repository)->majEtatDecision(
            $beneficiaire,
            new DateTimeImmutable(self::START),
            new DateTimeImmutable(self::END),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function instantsProvider(): array
    {
        return [
            'aménagement en cours' => [self::END . ' 11:00:00'],
            'aménagement terminé' => ['2026-09-01 00:00:01'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function etatsVerrouillesProvider(): array
    {
        $etats = [];
        foreach (self::instantsProvider() as $instant => [$now]) {
            $etats["en signature, $instant"] = [DecisionAmenagementExamens::ETAT_EN_SIGNATURE, $now];
            $etats["refusée, $instant"] = [DecisionAmenagementExamens::ETAT_REFUSEE, $now];
        }

        return $etats;
    }

    #[DataProvider('etatsVerrouillesProvider')]
    public function testLeavesLockedDecisionUntouched(string $etat, string $now): void
    {
        // en signature ou refusée, la décision attend le parapheur ou sa reprise : ni remise en validation, ni suppression
        $decision = new DecisionAmenagementExamens()->setEtat($etat);

        $repository = $this->createMock(DecisionAmenagementExamensRepository::class);
        $repository->expects(self::never())->method('remove');
        $repository->expects(self::never())->method('save');

        $beneficiaire = $this->beneficiaire([$this->amenagementDecision($now)], $decision);

        $this->manager($now, $repository)->majEtatDecision(
            $beneficiaire,
            new DateTimeImmutable(self::START),
            new DateTimeImmutable(self::END),
        );

        self::assertSame($etat, $decision->getEtat());
    }

    public function testChangesAreRefusedWhileDecisionIsEnSignature(): void
    {
        $motif = $this->verrou()->motifVerrou($this->beneficiaireAvecDecision(DecisionAmenagementExamens::ETAT_EN_SIGNATURE));

        self::assertStringContainsString('en cours de signature', (string) $motif);
    }

    public function testChangesAreRefusedUntilRefusedDecisionIsResumed(): void
    {
        $motif = $this->verrou()->motifVerrou($this->beneficiaireAvecDecision(DecisionAmenagementExamens::ETAT_REFUSEE));

        self::assertStringContainsString('reprenez-la', (string) $motif);
    }

    public function testChangesAreAllowedOnceDecisionIsResumed(): void
    {
        self::assertNull($this->verrou()->motifVerrou(
            $this->beneficiaireAvecDecision(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS),
        ));
    }

    public function testRefusedDecisionOfEndedYearDoesNotLockChanges(): void
    {
        // refusée l'an dernier et jamais reprise : elle ne bloque pas l'année en cours
        self::assertNull($this->verrou()->motifVerrou(
            $this->beneficiaireAvecDecision(DecisionAmenagementExamens::ETAT_REFUSEE, '2024-09-01', '2025-08-31'),
        ));
    }

    public function testDecisionOfComingYearStillLocksChanges(): void
    {
        self::assertNotNull($this->verrou()->motifVerrou(
            $this->beneficiaireAvecDecision(DecisionAmenagementExamens::ETAT_EN_SIGNATURE, '2026-09-01', '2027-08-31'),
        ));
    }

    // le verrou ne lit que les décisions du bénéficiaire et la date du jour, ici au cours de l'année 2025-2026
    private function verrou(): DecisionAmenagementManager
    {
        return $this->manager('2026-03-02 10:00:00', $this->createMock(DecisionAmenagementExamensRepository::class));
    }

    private function beneficiaireAvecDecision(string $etat, string $debut = self::START, string $fin = self::END): Utilisateur
    {
        $beneficiaire = new Utilisateur();
        $beneficiaire->addDecisionsAmenagementExamen(new DecisionAmenagementExamens()
            ->setEtat($etat)
            ->setDebut(new DateTimeImmutable($debut))
            ->setFin(new DateTimeImmutable($fin)));

        return $beneficiaire;
    }
}
