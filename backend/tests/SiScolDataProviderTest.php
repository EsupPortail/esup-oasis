<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Tests;

use App\Entity\Formation;
use App\Entity\Utilisateur;
use App\Service\SiScol\AbstractSiScolDataProvider;
use App\Service\SiScol\ApogeeProvider;
use App\Service\SiScol\FakeSiScolDataProvider;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class SiScolDataProviderTest extends TestCase
{
    private function createAnonymousProvider(ArrayAdapter $cache): AbstractSiScolDataProvider
    {
        return new class($cache) extends AbstractSiScolDataProvider {
            public int $callCount = 0;
            public array $lastRequested = [];

            public function getProviderId(): string
            {
                return 'test';
            }

            public function getInscriptions(
                Utilisateur $etudiant,
                DateTimeInterface $debut,
                ?DateTimeInterface $fin,
            ): array {
                return [];
            }

            public function getFormation(Formation $incomplete): array
            {
                return [];
            }

            protected function infosComplementaires(iterable $etudiants): array
            {
                $this->callCount++;
                $this->lastRequested = [];
                $result = [];
                foreach ($etudiants as $etudiant) {
                    $this->lastRequested[] = $etudiant->getNumeroEtudiant();
                    if ($etudiant->getNumeroEtudiant() !== 9999) {
                        $result[$etudiant->getNumeroEtudiant()] = [
                            'cle' => 'valeur_' . $etudiant->getNumeroEtudiant(),
                        ];
                    }
                }
                return $result;
            }
        };
    }

    public function testCacheHitAndMiss(): void
    {
        $cache = new ArrayAdapter();
        $provider = $this->createAnonymousProvider($cache);

        $etudiant1 = new Utilisateur();
        $etudiant1->setUid('etu1');
        $etudiant1->setNumeroEtudiant(1001);

        // 1er appel : cache miss, appel à la méthode sous-jacente
        $infos = $provider->getInfosComplementaires($etudiant1);
        $this->assertSame(['cle' => 'valeur_1001'], $infos);
        $this->assertSame(1, $provider->callCount);
        $this->assertSame([1001], $provider->lastRequested);

        // 2ème appel : cache hit, aucun nouvel appel à la méthode sous-jacente
        $infosSecond = $provider->getInfosComplementaires($etudiant1);
        $this->assertSame(['cle' => 'valeur_1001'], $infosSecond);
        $this->assertSame(1, $provider->callCount);
    }

    public function testMultipleStudentsWithPartialCache(): void
    {
        $cache = new ArrayAdapter();
        $provider = $this->createAnonymousProvider($cache);

        $etudiant1 = new Utilisateur();
        $etudiant1->setUid('etu1');
        $etudiant1->setNumeroEtudiant(1001);

        $etudiant2 = new Utilisateur();
        $etudiant2->setUid('etu2');
        $etudiant2->setNumeroEtudiant(1002);

        // Pré-mise en cache pour etudiant1
        $provider->getInfosComplementaires($etudiant1);
        $this->assertSame(1, $provider->callCount);

        // Appel multiple avec etudiant1 et etudiant2 : seul etudiant2 doit être manquant
        $results = $provider->getInfosComplementairesMultiple([$etudiant1, $etudiant2]);
        $this->assertSame(2, $provider->callCount);
        $this->assertSame([1002], $provider->lastRequested);

        $this->assertArrayHasKey(1001, $results);
        $this->assertArrayHasKey(1002, $results);
        $this->assertSame(['cle' => 'valeur_1001'], $results[1001]);
        $this->assertSame(['cle' => 'valeur_1002'], $results[1002]);
    }

    public function testStudentWithoutNumeroEtudiantIsSkipped(): void
    {
        $cache = new ArrayAdapter();
        $provider = $this->createAnonymousProvider($cache);

        $etudiantSansNum = new Utilisateur();
        $etudiantSansNum->setUid('sans_num');
        $etudiantSansNum->setNumeroEtudiant(null);

        $infos = $provider->getInfosComplementaires($etudiantSansNum);
        $this->assertSame([], $infos);
        $this->assertSame(0, $provider->callCount);

        $results = $provider->getInfosComplementairesMultiple([$etudiantSansNum]);
        $this->assertSame([], $results);
        $this->assertSame(0, $provider->callCount);
    }

    public function testEmptyResultIsCachedProperly(): void
    {
        $cache = new ArrayAdapter();
        $provider = $this->createAnonymousProvider($cache);

        $etudiantEmpty = new Utilisateur();
        $etudiantEmpty->setUid('etu_empty');
        $etudiantEmpty->setNumeroEtudiant(9999);

        // 1er appel : renvoie []
        $infos = $provider->getInfosComplementaires($etudiantEmpty);
        $this->assertSame([], $infos);
        $this->assertSame(1, $provider->callCount);

        // 2ème appel : doit être trouvé dans le cache et ne pas ré-invoquer la méthode sous-jacente
        $infosSecond = $provider->getInfosComplementaires($etudiantEmpty);
        $this->assertSame([], $infosSecond);
        $this->assertSame(1, $provider->callCount);
    }

    public function testFakeSiScolDataProvider(): void
    {
        $cache = new ArrayAdapter();
        $fakeProvider = new FakeSiScolDataProvider($cache);

        $etudiant = new Utilisateur();
        $etudiant->setUid('etu_fake');
        $etudiant->setNumeroEtudiant(12345);

        $infos = $fakeProvider->getInfosComplementaires($etudiant);
        $this->assertSame(['someKey' => 'someValue'], $infos);

        $etudiantSansNum = new Utilisateur();
        $etudiantSansNum->setUid('etu_fake_sans_num');
        $etudiantSansNum->setNumeroEtudiant(null);

        $this->assertSame([], $fakeProvider->getInfosComplementaires($etudiantSansNum));
    }

    public function testApogeeProviderReturnsEmptyArrayByDefault(): void
    {
        if (!function_exists('oci_connect')) {
            $this->markTestSkipped();
        }
        $cache = new ArrayAdapter();
        $apogeeProvider = new ApogeeProvider(
            'user',
            'pass',
            'dbname',
            new NullLogger(),
            'SELECT 1',
            'SELECT 1',
            $cache,
        );

        $etudiant = new Utilisateur();
        $etudiant->setUid('etu_apogee');
        $etudiant->setNumeroEtudiant(12345);

        $infos = $apogeeProvider->getInfosComplementaires($etudiant);
        $this->assertSame([], $infos);
    }
}
