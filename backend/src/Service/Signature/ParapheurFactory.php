<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\Signature;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ParapheurFactory
{
    /**
     * @param iterable<AbstractParapheur> $parapheurs
     */
    public function __construct(
        #[Autowire('%env(default::PARAPHEUR)%')]
        private ?string $parapheur,
        #[AutowireIterator('oasis.parapheur')]
        private iterable $parapheurs,
    ) {}

    /**
     * Création du parapheur en fonction de la variable d'environnement PARAPHEUR (vide : aucun)
     */
    public function create(): AbstractParapheur
    {
        $id = trim((string) $this->parapheur) ?: ParapheurDesactive::ID;

        foreach ($this->parapheurs as $parapheur) {
            if (strcasecmp($parapheur->getProviderId(), $id) === 0) {
                return $parapheur;
            }
        }

        throw new InvalidArgumentException(sprintf('Le parapheur "%s" n\'existe pas.', $id));
    }
}
