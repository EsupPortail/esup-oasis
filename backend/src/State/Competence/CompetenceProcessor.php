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

namespace App\State\Competence;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Competence;
use App\State\MappedEntityProcessor;

readonly class CompetenceProcessor implements ProcessorInterface
{
    function __construct(private MappedEntityProcessor $processor)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        return $this->processor->process(
            data: $data,
            operation: $operation,
            entityClass: Competence::class,
            uriVariables: $uriVariables,
            context: $context);
    }
}