<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

declare(strict_types=1);

namespace App\Tests\State\Amenagement;

use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Composante as ApiComposante;
use App\ApiResource\Formation as ApiFormation;
use App\ApiResource\Inscription as ApiInscription;
use App\ApiResource\Utilisateur as ApiUtilisateur;
use App\Entity\Composante;
use App\Entity\Utilisateur;
use App\State\Amenagement\AmenagementsParUtilisateurProvider;
use App\State\Utilisateur\UtilisateurProvider;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

final class AmenagementsParUtilisateurProviderTest extends TestCase
{
    private function createInscription(int $composanteId, string $debut): ApiInscription
    {
        $composante = new ApiComposante();
        $composante->id = $composanteId;

        $formation = new ApiFormation();
        $formation->composante = $composante;

        $inscription = new ApiInscription();
        $inscription->formation = $formation;
        $inscription->debut = new DateTime($debut);

        return $inscription;
    }

    private function createReferentUser(array $composanteIds): Utilisateur
    {
        $user = new Utilisateur();
        $composantes = [];
        foreach ($composanteIds as $id) {
            $cmp = new Composante();
            $prop = new ReflectionProperty(Composante::class, 'id');
            $prop->setValue($cmp, $id);
            $composantes[] = $cmp;
        }
        $propUser = new ReflectionProperty(Utilisateur::class, 'composantes');
        $propUser->setValue($user, new ArrayCollection($composantes));

        return $user;
    }

    public function testReferentComposanteGetsInscriptionOfTheirComposanteEvenIfNotMostRecent(): void
    {
        $security = $this->createMock(Security::class);
        $referent = $this->createReferentUser([1]);

        $security->method('isGranted')->willReturnCallback(
            static fn(string $role): bool => $role === Utilisateur::ROLE_REFERENT_COMPOSANTE,
        );
        $security->method('getUser')->willReturn($referent);

        $provider = new AmenagementsParUtilisateurProvider(
            $security,
            $this->createMock(ProviderInterface::class),
            $this->createMock(UtilisateurProvider::class),
        );

        $inscriptionCmp1 = $this->createInscription(1, '2026-09-01');
        $inscriptionCmp2Recent = $this->createInscription(2, '2026-09-15');

        $resource = new ApiUtilisateur();
        $resource->inscriptions = [$inscriptionCmp1, $inscriptionCmp2Recent];
        $resource->amenagements = [];

        $transformed = $provider->transform($resource);

        static::assertCount(1, $transformed->inscriptions);
        static::assertSame(1, $transformed->inscriptions[0]->formation->composante->id);
        static::assertSame('2026-09-01', $transformed->inscriptions[0]->debut->format('Y-m-d'));
    }

    public function testReferentComposanteGetsAllInscriptionsWithinTheirComposante(): void
    {
        $security = $this->createMock(Security::class);
        $referent = $this->createReferentUser([1]);

        $security->method('isGranted')->willReturnCallback(
            static fn(string $role): bool => $role === Utilisateur::ROLE_REFERENT_COMPOSANTE,
        );
        $security->method('getUser')->willReturn($referent);

        $provider = new AmenagementsParUtilisateurProvider(
            $security,
            $this->createMock(ProviderInterface::class),
            $this->createMock(UtilisateurProvider::class),
        );

        $inscriptionOld = $this->createInscription(1, '2025-09-01');
        $inscriptionRecent = $this->createInscription(1, '2026-09-01');
        $inscriptionOtherCmp = $this->createInscription(2, '2026-10-01');

        $resource = new ApiUtilisateur();
        $resource->inscriptions = [$inscriptionOld, $inscriptionRecent, $inscriptionOtherCmp];
        $resource->amenagements = [];

        $transformed = $provider->transform($resource);

        static::assertCount(2, $transformed->inscriptions);
        static::assertSame(1, $transformed->inscriptions[0]->formation->composante->id);
        static::assertSame('2025-09-01', $transformed->inscriptions[0]->debut->format('Y-m-d'));
        static::assertSame(1, $transformed->inscriptions[1]->formation->composante->id);
        static::assertSame('2026-09-01', $transformed->inscriptions[1]->debut->format('Y-m-d'));
    }

    public function testReferentComposanteWithNoMatchingInscriptionGetsEmptyArray(): void
    {
        $security = $this->createMock(Security::class);
        $referent = $this->createReferentUser([3]);

        $security->method('isGranted')->willReturnCallback(
            static fn(string $role): bool => $role === Utilisateur::ROLE_REFERENT_COMPOSANTE,
        );
        $security->method('getUser')->willReturn($referent);

        $provider = new AmenagementsParUtilisateurProvider(
            $security,
            $this->createMock(ProviderInterface::class),
            $this->createMock(UtilisateurProvider::class),
        );

        $inscriptionCmp1 = $this->createInscription(1, '2026-09-01');
        $inscriptionCmp2 = $this->createInscription(2, '2026-09-15');

        $resource = new ApiUtilisateur();
        $resource->inscriptions = [$inscriptionCmp1, $inscriptionCmp2];
        $resource->amenagements = [];

        $transformed = $provider->transform($resource);

        static::assertEmpty($transformed->inscriptions);
    }

    public function testNonReferentGetsAllInscriptions(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(false);

        $provider = new AmenagementsParUtilisateurProvider(
            $security,
            $this->createMock(ProviderInterface::class),
            $this->createMock(UtilisateurProvider::class),
        );

        $inscriptionCmp1 = $this->createInscription(1, '2026-09-01');
        $inscriptionCmp2Recent = $this->createInscription(2, '2026-09-15');

        $resource = new ApiUtilisateur();
        $resource->inscriptions = [$inscriptionCmp1, $inscriptionCmp2Recent];
        $resource->amenagements = [];

        $transformed = $provider->transform($resource);

        static::assertCount(2, $transformed->inscriptions);
        static::assertSame($inscriptionCmp1, $transformed->inscriptions[0]);
        static::assertSame($inscriptionCmp2Recent, $transformed->inscriptions[1]);
    }

    public function testReferentComposanteWhoIsAlsoPlanificateurGetsAllInscriptions(): void
    {
        $security = $this->createMock(Security::class);
        $referent = $this->createReferentUser([1]);

        $security->method('isGranted')->willReturnCallback(
            static fn(string $role): bool => match ($role) {
                Utilisateur::ROLE_REFERENT_COMPOSANTE => true,
                Utilisateur::ROLE_PLANIFICATEUR => true,
                default => false,
            },
        );
        $security->method('getUser')->willReturn($referent);

        $provider = new AmenagementsParUtilisateurProvider(
            $security,
            $this->createMock(ProviderInterface::class),
            $this->createMock(UtilisateurProvider::class),
        );

        $inscriptionCmp1 = $this->createInscription(1, '2026-09-01');
        $inscriptionCmp2Recent = $this->createInscription(2, '2026-09-15');

        $resource = new ApiUtilisateur();
        $resource->inscriptions = [$inscriptionCmp1, $inscriptionCmp2Recent];
        $resource->amenagements = [];

        $transformed = $provider->transform($resource);

        static::assertCount(2, $transformed->inscriptions);
        static::assertSame($inscriptionCmp1, $transformed->inscriptions[0]);
        static::assertSame($inscriptionCmp2Recent, $transformed->inscriptions[1]);
    }

    public function testUserWithNoInscriptionsReturnsEmptyArray(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(false);

        $provider = new AmenagementsParUtilisateurProvider(
            $security,
            $this->createMock(ProviderInterface::class),
            $this->createMock(UtilisateurProvider::class),
        );

        $resource = new ApiUtilisateur();
        $resource->inscriptions = [];
        $resource->amenagements = [];

        $transformed = $provider->transform($resource);

        static::assertEmpty($transformed->inscriptions);
    }
}
