<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\State\Amenagement;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Amenagement;
use App\Message\AmenagementModifieMessage;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\AmenagementRepository;
use App\Repository\TypeAmenagementRepository;
use App\Repository\TypeSuiviAmenagementRepository;
use App\Service\ErreurLdapException;
use App\State\Utilisateur\UtilisateurManager;
use App\Util\AnneeUniversitaireAwareTrait;
use Override;
use Symfony\Component\Messenger\MessageBusInterface;

class AmenagementProcessor implements ProcessorInterface
{
    use AnneeUniversitaireAwareTrait;

    public function __construct(
        private readonly AmenagementRepository $amenagementRepository,
        private readonly TypeAmenagementRepository $typeAmenagementRepository,
        private readonly TypeSuiviAmenagementRepository $typeSuiviAmenagementRepository,
        private readonly UtilisateurManager $utilisateurManager,
        private readonly MessageBusInterface $messageBus,
    ) {}

    /**
     * @param Amenagement $data
     */
    #[Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (null !== $data->id) {
            $entity = $this->amenagementRepository->find($data->id);
        } else {
            $entity = new \App\Entity\Amenagement();
        }

        //DELETE
        if ($operation instanceof Delete) {
            $this->messageBus->dispatch(new AmenagementModifieMessage($entity));
            $this->amenagementRepository->remove($entity, true);
            //liste des aménagements par utilisateurs impactée!
            $this->messageBus->dispatch(new RessourceModifieeMessage($data->beneficiaire));
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data->beneficiaire));
            return null;
        }

        //POST/PATCH
        /**
         * debut et fin sont calculés si vides
         */
        $debut = match ($data->debut) {
            null => match ($data->semestre1) {
                true => $this->getDebutSemestre1(),
                false => $this->getDebutSemestre2(),
            },
            default => $data->debut,
        };
        $fin = match ($data->debut) {
            null => match ($data->semestre2) {
                true => $this->getFinSemestre2(),
                false => $this->getFinSemestre1(),
            },
            default => $data->fin,
        };
        if (null === $data->fin && $data->typeAmenagement->examens) {
            //on force la fin d'année pour les aménagements d'examens
            $fin = $this->getFinSemestre2();
        }

        $entity->setDebut($debut);
        $entity->setFin($fin);
        $entity->setCommentaire($data->commentaire);
        $entity->setSemestre1($data->semestre1);
        $entity->setSemestre2($data->semestre2);
        $entity->setType($this->typeAmenagementRepository->find($data->typeAmenagement->id));
        $entity->setSuivi(match ($data->suivi) {
            null => null,
            default => $this->typeSuiviAmenagementRepository->find($data->suivi->id),
        });

        /**
         * Ajout/modification des bénéficiaires actifs pour l'utilisateur!
         */
        $utilisateur = $this->utilisateurManager->parUid($uriVariables['uid']); //sur POST c'est pas rempli...
        foreach ($utilisateur->getBeneficiaires() as $beneficiaire) {
            if ($entity->canHaveBeneficiaire($beneficiaire)) {
                $entity->addBeneficiaire($beneficiaire);
            }
        }

        $this->amenagementRepository->save($entity, true);
        $this->messageBus->dispatch(new AmenagementModifieMessage($entity));

        $resource = new Amenagement($entity);

        //liste des aménagements par utilisateurs impactée !
        $this->messageBus->dispatch(new RessourceModifieeMessage($resource->beneficiaire));
        $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource->beneficiaire));

        return $resource;
    }
}
