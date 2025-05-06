<?php

/*
 * Copyright (c) 2024. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Util;

use App\Entity\ProfilBeneficiaire;
use App\Repository\ProfilBeneficiaireRepository;
use Faker\Generator;
use Faker\Provider\Base as BaseProvider;

class ProfilBeneficiaireFakerProvider extends BaseProvider
{

    public function __construct(Generator                                     $generator,
                                private readonly ProfilBeneficiaireRepository $profilBeneficiaireRepository)
    {
        parent::__construct($generator);
    }

    public function profilExistant(int $id): ?ProfilBeneficiaire
    {
        return $this->profilBeneficiaireRepository->find($id);
    }

}