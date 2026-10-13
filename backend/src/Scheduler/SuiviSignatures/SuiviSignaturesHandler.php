<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Scheduler\SuiviSignatures;

use App\Service\Signature\SuiviSignatureService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SuiviSignaturesHandler
{
    public function __construct(
        private SuiviSignatureService $suiviSignatureService,
    ) {}

    public function __invoke(SuiviSignaturesMessage $message): void
    {
        $this->suiviSignatureService->traiterLot();
    }
}
