<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

declare(strict_types=1);

namespace App\Tests\Entity;

use App\ApiResource\Inscription as ApiInscription;
use App\Entity\Inscription;
use PHPUnit\Framework\TestCase;

final class InscriptionTest extends TestCase
{
    public function testEntityPrincipaleDefaultsToNull(): void
    {
        $inscription = new Inscription();
        static::assertNull($inscription->isPrincipale());
    }

    public function testEntitySetPrincipale(): void
    {
        $inscription = new Inscription();

        $fluent = $inscription->setPrincipale(true);
        static::assertSame($inscription, $fluent);
        static::assertTrue($inscription->isPrincipale());

        $inscription->setPrincipale(false);
        static::assertFalse($inscription->isPrincipale());
    }

    public function testApiResourceDelegatesPrincipaleToEntity(): void
    {
        $entity = new Inscription();
        $entity->setPrincipale(true);
        $resource = new ApiInscription($entity);
        static::assertTrue($resource->principale);

        $entityFalse = new Inscription();
        $entityFalse->setPrincipale(false);
        $resourceFalse = new ApiInscription($entityFalse);
        static::assertFalse($resourceFalse->principale);
    }

    public function testApiResourcePrincipaleSetter(): void
    {
        $resource = new ApiInscription();
        $resource->principale = true;
        static::assertTrue($resource->principale);

        $resource->principale = false;
        static::assertFalse($resource->principale);
    }
}
