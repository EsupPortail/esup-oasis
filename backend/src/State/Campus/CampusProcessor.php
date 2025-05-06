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

namespace App\State\Campus;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Campus;
use App\State\MappedEntityProcessor;
use Override;

readonly class CampusProcessor implements ProcessorInterface
{
    function __construct(private MappedEntityProcessor $processor)
    {
    }

    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        return $this->processor->process(
            data: $data,
            operation: $operation,
            entityClass: Campus::class,
            uriVariables: $uriVariables,
            context: [...$context, 'map_groups' => [\App\ApiResource\Campus::GROUP_IN]]);
    }
}