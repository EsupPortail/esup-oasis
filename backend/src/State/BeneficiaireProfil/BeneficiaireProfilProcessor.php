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

namespace App\State\BeneficiaireProfil;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\ApiResource\BeneficiaireProfil;
use App\Entity\ProfilBeneficiaire;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\ProfilBeneficiaireRepository;
use App\Repository\TypologieHandicapRepository;
use App\Service\ErreurLdapException;
use App\State\TransformerService;
use App\State\Utilisateur\BeneficiaireInconnuException;
use App\State\Utilisateur\UtilisateurManager;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class BeneficiaireProfilProcessor implements ProcessorInterface
{
    public function __construct(private UtilisateurManager           $utilisateurManager,
                                private ProfilBeneficiaireRepository $profilBeneficiaireRepository,
                                private TypologieHandicapRepository  $typologieHandicapRepository,
                                private TransformerService           $transformerService,
                                private MessageBusInterface          $messageBus,
                                private ValidatorInterface           $validator,)
    {
    }

    /**
     * @param BeneficiaireProfil $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return BeneficiaireProfil|null
     * @throws Exception
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): BeneficiaireProfil|null
    {
        try {
            $utilisateur = $this->utilisateurManager->parUid($uriVariables['uid']);
            $gestionnaire = $this->utilisateurManager->parUid($data->gestionnaire->uid);
        } catch (ErreurLdapException) {
            throw new NotFoundHttpException('Utilisateur inconnu');
        }
        $profil = $this->profilBeneficiaireRepository->find($data->profil?->id ?? ProfilBeneficiaire::A_DETERMINER);

        if ($operation instanceof Delete) {
            $this->validator->validate($data, ['groups' => [BeneficiaireProfil::GROUP_VALIDATION_DELETE]]);

            try {
                $this->utilisateurManager->supprimerBeneficiaire($utilisateur, $data->id);
                $this->messageBus->dispatch(new RessourceModifieeMessage($data));
                return null;
            } catch (BeneficiaireInconnuException $exception) {
                //todo: remplacer ça par une contrainte en amont
                throw new UnprocessableEntityHttpException($exception->getMessage());
            }
        } else {
            try {
                $beneficiaireEntity = $this->utilisateurManager->majBeneficiaires($utilisateur, $profil, $data->debut,
                    $data->fin, $data->id, $gestionnaire,
                    array_map(fn($typo) => $this->typologieHandicapRepository->find($typo->id), $data->typologies ?? []),
                    $data->avecAccompagnement);
                $resource = $this->transformerService->transform($beneficiaireEntity, BeneficiaireProfil::class);
                if (null !== $data->id) {
                    $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
                } else {
                    $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));
                }
                return $resource;
            } catch (BeneficiaireInconnuException $exception) {
                //todo: remplacer ça par une contrainte en amont
                throw new UnprocessableEntityHttpException($exception->getMessage());
            }
        }
    }
}