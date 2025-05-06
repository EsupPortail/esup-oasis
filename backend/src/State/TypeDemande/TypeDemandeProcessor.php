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

namespace App\State\TypeDemande;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ProfilBeneficiaire;
use App\ApiResource\TypeDemande;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\ProfilBeneficiaireRepository;
use App\Repository\TypeDemandeRepository;
use App\State\TransformerService;
use Exception;
use Override;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class TypeDemandeProcessor implements ProcessorInterface
{
    function __construct(private TypeDemandeRepository        $typeDemandeRepository,
                         private ProfilBeneficiaireRepository $profilBeneficiaireRepository,
                         private TransformerService           $transformerService,
                         private MessageBusInterface          $messageBus)
    {
    }

    /**
     * @param TypeDemande $data
     * @throws Exception
     */
    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TypeDemande
    {
        if (null === $data->id) {
            $entity = new \App\Entity\TypeDemande();
        } else {
            $entity = $this->typeDemandeRepository->find($data->id);
        }

        $entity->setLibelle($data->libelle);
        $entity->setActif($data->actif);
        $entity->setVisibiliteLimitee($data->visibiliteLimitee);
        $entity->setAccompagnementOptionnel($data->accompagnementOptionnel);

        $profilIds = array_map(fn(ProfilBeneficiaire $profil) => $profil->id, $data->profilsCibles ?? []);
        $supprimes = array_filter($entity->getProfilsAssocies()->toArray(), fn(\App\Entity\ProfilBeneficiaire $profil) => !in_array($profil->getId(), $profilIds));
        foreach ($supprimes as $supprime) {
            $entity->removeProfilAssocie($supprime);
        }
        foreach ($profilIds as $profilId) {
            $entity->addProfilAssocie($this->profilBeneficiaireRepository->find($profilId));
        }

        $this->typeDemandeRepository->save($entity, true);

        $resource = $this->transformerService->transform($entity, TypeDemande::class);
        if (null !== $data->id) {
            $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
        } else {
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));
        }

        return $resource;
    }
}