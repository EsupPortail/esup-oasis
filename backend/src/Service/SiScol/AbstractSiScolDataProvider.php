<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Service\SiScol;

use App\Entity\Formation;
use App\Entity\Utilisateur;
use DateTimeInterface;
use Monolog\Level;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AutoconfigureTag('oasis.siscol_provider')]
abstract class AbstractSiScolDataProvider
{
    public function __construct(
        private readonly ?CacheInterface $cache = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly int $dureeValiditeCache = 3600,
    ) {}

    /**
     * Retourne l'identifiant du provider du SiScol
     *
     * @return string
     */
    abstract public function getProviderId(): string;

    /**
     * Tableau listant les formations auxquelles est inscrit l'étudiant sur l'intervalle de temps donné
     * [[codeFormation, libFormation, codeComposante, libComposante, debut, fin], ...]
     *
     * @param Utilisateur        $etudiant
     * @param DateTimeInterface  $debut
     * @param ?DateTimeInterface $fin
     * @return array
     * @throws BackendUnavailableException
     */
    abstract public function getInscriptions(
        Utilisateur $etudiant,
        DateTimeInterface $debut,
        ?DateTimeInterface $fin,
    ): array;

    /**
     * @param Formation $incomplete
     * @return array
     * @throws BackendUnavailableException
     */
    abstract public function getFormation(Formation $incomplete): array;

    /**
     * Appelle l'implémentation sous-jacente et gère la mise en cache si disponible
     *
     * @return array<string, string>
     * @throws InvalidArgumentException
     */
    public function getInfosComplementaires(Utilisateur $etudiant): array
    {
        if (null === $etudiant->getNumeroEtudiant()) {
            return [];
        }

        return $this->getInfosComplementairesMultiple([$etudiant])[$etudiant->getNumeroEtudiant()] ?? [];
    }

    /**
     * @param iterable<Utilisateur> $etudiants
     * @return array<string, array<string, string>> un tableau d'infos clé/valeur indexé par le numéro étudiant
     * @throws InvalidArgumentException
     */
    public function getInfosComplementairesMultiple(iterable $etudiants): array
    {
        if (null === $this->cache) {
            $this->log(Level::Info, 'Pas de cache défini par ' . get_class($this));
            return $this->infosComplementaires($etudiants);
        }

        $manquants = [];
        $existants = [];
        foreach ($etudiants as $etudiant) {
            if (null === $etudiant->getNumeroEtudiant()) {
                continue;
            }
            $infos = $this->getCachedInfosOrNull($etudiant);
            if (null === $infos) {
                $manquants[] = $etudiant;
                continue;
            }
            $existants[$etudiant->getNumeroEtudiant()] = $infos;
        }

        if (empty($manquants)) {
            return $existants;
        }

        //on ajoute les manquants dans le cache individuellement
        $infosManquants = $this->infosComplementaires($manquants);

        foreach ($manquants as $manquant) {
            $cacheKey = $this->getCacheKey($manquant);
            $existants[$manquant->getNumeroEtudiant()] = $this->cache->get($cacheKey, function (ItemInterface $item) use (
                $manquant,
                $infosManquants,
            ) {
                $resultat = $infosManquants[$manquant->getNumeroEtudiant()] ?? null;
                if ($resultat !== null) {
                    $item->expiresAfter($this->dureeValiditeCache);
                } else {
                    // 5 minutes - si le SI d'origine est momentanément indisponible on veut réessayer plus rapidement
                    $resultat = [];
                    $item->expiresAfter(300);
                }

                return $resultat;
            });
        }

        return $existants;
    }

    private function getCachedInfosOrNull(Utilisateur $etudiant): ?array
    {
        $cacheMiss = '__cache_miss__';

        $value = $this->cache->get($this->getCacheKey($etudiant), function (ItemInterface $item, bool &$save) use (
            $cacheMiss,
        ): string {
            $save = false;
            $item->expiresAfter(0);

            return $cacheMiss;
        });

        return $value === $cacheMiss ? null : $value;
    }

    private function getCacheKey(Utilisateur $etudiant): string
    {
        return 'infos_complementaires_' . $etudiant->getUid();
    }

    private function log($level, string|Stringable $message, array $context = [])
    {
        if (null !== $this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * @return array<string, array<string, string>> un tableau d'infos clé/valeur indexé par le numéro étudiant
     */
    protected function infosComplementaires(iterable $etudiants): array
    {
        return [];
    }

    public function listeInfosComplementairesDisponibles(): array
    {
        return [];
    }
}
