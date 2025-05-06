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

namespace App\Filter;

use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\PropertyInfo\Type;

class DemandeFormatFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'format_simple' => [
                'property' => 'format simplifié',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'is_collection' => false,
                'openapi' => new Parameter(
                    name: 'format',
                    in: 'query',
                    description: 'format simplifié',
                ),
            ],
        ];
    }
}