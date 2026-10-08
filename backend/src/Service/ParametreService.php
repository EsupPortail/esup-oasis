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

namespace App\Service;

use App\Entity\Fichier;
use App\Entity\Parametre;
use App\Entity\ValeurParametre;
use App\Repository\ParametreRepository;
use App\Service\SiScol\AbstractSiScolDataProvider;
use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class ParametreService
{
    public function __construct(
        private ParametreRepository $parametreRepository,
        private array $appEnv,
        private AbstractSiScolDataProvider $siScolDataProvider,
        private CacheInterface $cache,
    ) {}

    public function valeur(string $cle, bool $multiple = false): string|array|Fichier|null
    {
        //on cherche d'abord dans l'env, puis en base
        if (array_key_exists($cle, $this->appEnv)) {
            return $this->appEnv[$cle];
        }

        $param = $this->parametreRepository->findOneBy([
            'cle' => $cle,
        ]);

        if ($multiple) {
            return array_map(
                fn(ValeurParametre $dest) => $dest->getValeur(),
                $param->getValeurCourante(multiple: true),
            );
        }

        return $param?->isFichier()
            ? $param?->getValeurCourante()?->getFichier()
            : $param?->getValeurCourante()?->getValeur();
    }

    public function getAppEnv(): array
    {
        return $this->appEnv;
    }

    public function getConstantParams(): array
    {
        // ça ne doit pas bouger, on met en cache pour éviter de requêter en permanence
        $listeInfos = $this->cache->get('liste_infos_complementaires', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->siScolDataProvider->listeInfosComplementairesDisponibles();
        });

        $paramInfos = new Parametre();
        $paramInfos->setCle(Parametre::CONST_INFOS_COMPLEMENTAIRES);
        foreach ($listeInfos as $i => $info) {
            $valeur = new ValeurParametre((int) $i);
            $valeur->setDebut(new DateTimeImmutable('1970-01-01'));
            $valeur->setValeur($info);
            $paramInfos->addValeursParametre($valeur);
        }

        return [
            Parametre::CONST_INFOS_COMPLEMENTAIRES => $paramInfos,
        ];
    }
}
