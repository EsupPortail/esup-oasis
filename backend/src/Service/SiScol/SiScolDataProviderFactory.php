<?php
/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Kevin Leveillard <kevin.leveillard@agroparistech.fr>
 *
 */

namespace App\Service\SiScol;

use InvalidArgumentException;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SiScolDataProviderFactory
{

    public function __construct(
        #[Autowire(env: 'resolve:SI_SCOL')]
        private string $siScol,
    ) {}

    /**
     * Création du provider en fonction de la variable d'environnement SI_SCOL
     *
     * @author leveillard
     */
    public function create(): AbstractSiScolDataProvider
    {
        $className = sprintf(
            'App\\Service\\Serialize\\%sProvider',
            ucfirst(strtolower($this->siScol))
        );

        if (!class_exists($className)) {
            throw new InvalidArgumentException(
                sprintf('Le provider "%s" n\'existe pas.', $this->siScol)
            );
        }

        $provider = new $className();

        if (!$provider instanceof AbstractSiScolDataProvider) {
            throw new LogicException(
                sprintf(
                    'La classe "%s" doit hériter de AbstractSiScolDataProvider.',
                    $className
                )
            );
        }
        return $provider;
    }
}
