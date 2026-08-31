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

use App\Entity\AvisEse;
use App\Entity\Amenagement;
use App\Entity\Beneficiaire;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\TypeAmenagement;
use App\Entity\Utilisateur;
use App\Repository\DecisionAmenagementExamensRepository;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use DateTimeImmutable;
use ReflectionClass;
use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Filet de sécurité sur le fix isActif (granularité jour) : la décision
 * d'examen suit la durée réelle de l'aménagement. Le dernier jour, inclusif,
 * l'aménagement reste actif donc la décision doit être conservée ; le
 * lendemain il s'éteint donc la décision supprimable est retirée.
 */
final class DecisionAmenagementManagerTest extends TestCase
{
    private const string START = '2025-09-01';
    private const string END = '2026-08-31';

    private function examAmenagement(string $now): Amenagement
    {
        $type = (new TypeAmenagement())->setExamens(true);

        $amenagement = new Amenagement();
        $amenagement->setType($type);
        $amenagement->setDebut(new DateTimeImmutable(self::START));
        $amenagement->setFin(new DateTimeImmutable(self::END));
        $amenagement->setClock(new MockClock(new DateTimeImmutable($now)));
        // Un id non nul : la déduplication de Utilisateur::getAmenagementsActifs
        // indexe par id et déclencherait sinon une déprécation sur clé nulle.
        (new ReflectionProperty(Amenagement::class, 'id'))->setValue($amenagement, 1);

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

        $utilisateur = $this->createPartialMock(
            Utilisateur::class,
            ['getBeneficiairesActifs', 'getEtatAvisEse', 'getDecisionAmenagementExamens'],
        );
        $utilisateur->method('getBeneficiairesActifs')->willReturn([$benef]);
        // Pas d'avis ESE en cours : l'existence de la décision ne dépend que de
        // l'aménagement d'examen, c'est ce qu'on veut isoler.
        $utilisateur->method('getEtatAvisEse')->willReturn(AvisEse::ETAT_AUCUN);
        $utilisateur->method('getDecisionAmenagementExamens')->willReturn($decision);

        return $utilisateur;
    }

    private function manager(
        string $now,
        DecisionAmenagementExamensRepository $repository,
    ): DecisionAmenagementManager {
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
        $bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        return $bus;
    }

    public function testKeepsExamDecisionThroughAccommodationLastDay(): void
    {
        $decision = (new DecisionAmenagementExamens())
            ->setEtat(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);

        $repository = $this->createMock(DecisionAmenagementExamensRepository::class);
        $repository->expects(self::never())->method('remove');
        $repository->expects(self::once())->method('save');

        $now = self::END . ' 11:00:00';
        $beneficiaire = $this->beneficiaire([$this->examAmenagement($now)], $decision);

        $this->manager($now, $repository)->majEtatDecision(
            $beneficiaire,
            new DateTimeImmutable(self::START),
            new DateTimeImmutable(self::END),
        );
    }

    public function testRemovesExamDecisionDayAfterAccommodationEnds(): void
    {
        $decision = (new DecisionAmenagementExamens())
            ->setEtat(DecisionAmenagementExamens::ETAT_ATTENTE_VALIDATION_CAS);

        $repository = $this->createMock(DecisionAmenagementExamensRepository::class);
        $repository->expects(self::once())->method('remove');
        $repository->expects(self::never())->method('save');

        $now = '2026-09-01 00:00:01';
        $beneficiaire = $this->beneficiaire([$this->examAmenagement($now)], $decision);

        $this->manager($now, $repository)->majEtatDecision(
            $beneficiaire,
            new DateTimeImmutable(self::START),
            new DateTimeImmutable(self::END),
        );
    }
}
