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

namespace App\State\BilanActivite;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\BilanActivite;
use App\ApiResource\Composante;
use App\ApiResource\Formation;
use App\ApiResource\ProfilBeneficiaire;
use App\ApiResource\Utilisateur;
use App\Entity\Bilan;
use App\Message\BilanActiviteDemandeMessage;
use App\Repository\BilanRepository;
use App\State\TransformerService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class BilanActiviteProcessor implements ProcessorInterface
{
    use ClockAwareTrait;

    public function __construct(private BilanRepository       $bilanRepository,
                                private Security              $security,
                                private IriConverterInterface $iriConverter,
                                private TransformerService    $transformerService,
                                private MessageBusInterface   $messageBus)
    {

    }

    /**
     * @param BilanActivite $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?BilanActivite
    {
        //Support de POST et DELETE uniquement
        if ($operation instanceof Delete) {
            $existant = $this->bilanRepository->find($data->id);
            $this->bilanRepository->remove($existant, true);
            return null;
        }

        $bilan = new Bilan();
        $bilan->setDateDemande($this->now());
        $bilan->setDebut($data->debut);
        $bilan->setFin($data->fin);
        $bilan->setDemandeur($this->security->getUser());
        $bilan->setParametres([
            'gestionnaires' => array_map(
                fn(Utilisateur $gest) => $this->iriConverter->getIriFromResource($gest),
                $data->gestionnaires
            ),
            'composantes' => array_map(
                fn(Composante $cmp) => $this->iriConverter->getIriFromResource($cmp),
                $data->composantes
            ),
            'formations' => array_map(
                fn(Formation $form) => $this->iriConverter->getIriFromResource($form),
                $data->formations
            ),
            'profils' => array_map(
                fn(ProfilBeneficiaire $profil) => $this->iriConverter->getIriFromResource($profil),
                $data->profils
            ),
        ]);

        $this->bilanRepository->save($bilan, true);

        //on envoie le message pour déclencher l'export
        $this->messageBus->dispatch(new BilanActiviteDemandeMessage($bilan->getId()));

        return $this->transformerService->transform($bilan, BilanActivite::class);
    }
}