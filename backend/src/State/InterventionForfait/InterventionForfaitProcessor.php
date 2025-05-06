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

namespace App\State\InterventionForfait;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\InterventionForfait;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\InterventionForfaitRepository;
use App\Repository\PeriodeRHRepository;
use App\Repository\TypeEvenementRepository;
use App\Repository\UtilisateurRepository;
use App\Service\ErreurLdapException;
use App\State\MajBeneficiairesTrait;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class InterventionForfaitProcessor implements ProcessorInterface
{
    use ClockAwareTrait;
    use MajBeneficiairesTrait;

    public function __construct(private readonly InterventionForfaitRepository $repository,
                                private readonly PeriodeRHRepository           $periodeRHRepository,
                                private readonly TypeEvenementRepository       $typeEvenementRepository,
                                private readonly UtilisateurManager            $utilisateurManager,
                                private readonly UtilisateurRepository         $utilisateurRepository,
                                private readonly TransformerService            $transformerService,
                                private readonly MessageBusInterface           $messageBus,
                                private readonly Security                      $security)
    {
    }

    /**
     * @param InterventionForfait $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return InterventionForfait|null
     * @throws ErreurLdapException
     * @throws ExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?InterventionForfait
    {
        if (null !== $data->id) {
            $entity = $this->repository->find($data->id);
            $entity->setDateModification($this->now());
            $entity->setUtilisateurModification($this->security->getUser());
        } else {
            $entity = new \App\Entity\InterventionForfait();
            $entity->setDateCreation($this->now());
            $entity->setUtilisateurCreation($this->security->getUser());
        }

        if ($operation instanceof Delete) {
            $this->repository->remove($entity, true);
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data));
            return null; //ou data?
        }

        $entity->setPeriode($this->periodeRHRepository->find($data->periode->id));
        $entity->setType($this->typeEvenementRepository->find($data->type->id));
        $entity->setIntervenant(($this->utilisateurManager->parUid($data->intervenant->uid))->getIntervenant());
        $entity->setHeures($data->heures);

        $this->majBeneficiaires($data->beneficiaires ?? [], $entity);

        $this->repository->save($entity, true);

        $resource = $this->transformerService->transform($entity, InterventionForfait::class);
        if (null !== $data->id) {
            $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
        } else {
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));
        }
        return $resource;
    }
}