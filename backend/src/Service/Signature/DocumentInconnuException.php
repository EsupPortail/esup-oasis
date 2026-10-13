<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

namespace App\Service\Signature;

/**
 * Le document demandé n'est pas connu du parapheur.
 */
class DocumentInconnuException extends ParapheurException
{
    public function __construct(string $documentId)
    {
        parent::__construct(sprintf('Document inconnu du parapheur : "%s".', $documentId));
    }
}
