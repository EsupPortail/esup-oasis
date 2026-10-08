<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests\State;

use App\ApiResource\DecisionAmenagementExamens as DecisionResource;
use App\Entity\DecisionAmenagementExamens;
use App\State\EntityToResourceTransformer;
use PHPUnit\Framework\TestCase;

class EntityToResourceTransformerTest extends TestCase
{
    public function testDecisionResourceIsBuiltWithTheIriConverterAsSecondArgument(): void
    {
        // construction utilisée à la purge du cache HTTP : la ressource ne doit pas exiger d'autre argument
        $resource = EntityToResourceTransformer::entityToResource(
            new DecisionResource(),
            (new DecisionAmenagementExamens())->setEtat(DecisionAmenagementExamens::ETAT_VALIDE),
        );

        $this->assertInstanceOf(DecisionResource::class, $resource);
        $this->assertSame(DecisionAmenagementExamens::ETAT_VALIDE, $resource->etat);
        $this->assertFalse($resource->dateAvisMedecinRequise);
    }
}
