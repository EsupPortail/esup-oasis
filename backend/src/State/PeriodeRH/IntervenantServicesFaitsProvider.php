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

namespace App\State\PeriodeRH;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\PeriodeRH;
use App\Filter\PeriodeIntervenantFilter;
use App\Repository\PeriodeRHRepository;
use Exception;

readonly class IntervenantServicesFaitsProvider implements ProviderInterface
{

    function __construct(private ServicesFaitsProvider $servicesFaitsProvider,
                         private PeriodeProvider       $periodeProvider,
                         private PeriodeRHRepository   $periodeRHRepository)
    {

    }

    /**
     * @throws Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof Get) {
            return $this->servicesFaitsProvider->provide($operation, $uriVariables, $context);
        }
        //GetCollection -> toutes les périodes pour un intervenant
        // On appelle le PeriodeProvider pour avoir les filtres!
        $periodeContext = $context;
        $periodeContext['filters'][PeriodeIntervenantFilter::PROPERTY] = $uriVariables['uid'];
        if ($context['filters']['order'] ?? false) {
            $periodeContext['filters']['order'] = $context['filters']['order'];
        }

        $periodes = $this->periodeProvider->provide(
            operation: $operation->withClass(PeriodeRH::class),
            uriVariables: [],
            context: $periodeContext);

        //on a les périodes filtrées...
        $result = [];
        foreach ($periodes as $periode) {
            if (!$periode->envoyee) {
                continue;
            }
            $periodeEntity = $this->periodeRHRepository->find($periode->id);
            $servicesFaits = $this->servicesFaitsProvider->init($periodeEntity, $uriVariables['uid']);
            $result[] = $this->servicesFaitsProvider->traiterEvenements(
                evenements: $this->servicesFaitsProvider->getEvenements($periodeEntity, $uriVariables['uid']),
                servicesFaits: $servicesFaits
            );
        }

        return $result;
    }
}