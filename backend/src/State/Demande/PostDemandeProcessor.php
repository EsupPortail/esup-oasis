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

namespace App\State\Demande;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Demande;
use App\Entity\EtatDemande;
use App\Entity\Reponse;
use App\Message\RessourceCollectionModifieeMessage;
use App\Repository\DemandeRepository;
use App\Repository\EtatDemandeRepository;
use App\Repository\ReponseRepository;
use App\Repository\TypeDemandeRepository;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use Exception;
use Override;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

class PostDemandeProcessor implements ProcessorInterface
{

    use ClockAwareTrait;

    public function __construct(private readonly DemandeRepository     $demandeRepository,
                                private readonly TypeDemandeRepository $typeDemandeRepository,
                                private readonly EtatDemandeRepository $etatDemandeRepository,
                                private readonly ReponseRepository     $reponseRepository,
                                private readonly UtilisateurManager    $utilisateurManager,
                                private readonly TransformerService    $transformerService,
                                private readonly MessageBusInterface   $messageBus)
    {

    }

    /**
     * @param Demande $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return mixed
     * @throws Exception
     */
    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Demande
    {
        //On récupère le demandeur à partir de son uid
        $demandeur = $this->utilisateurManager->parUid($data->demandeur->uid, true);

        //On récupère la campagne en cours pour le type de demande passé
        if (!$campagne = $this->typeDemandeRepository->find($data->typeDemande->id)->getCampagneEnCoursPourDate($this->now())) {
            throw new UnprocessableEntityHttpException('Aucune campagne en cours pour ce type de demandes');
        }

        if (null !== $demandeur->getDemandePourCampagne($campagne)) {
            throw new UnprocessableEntityHttpException('Une seule demande par utilisateur et par campagne');
        }

        $demande = new \App\Entity\Demande();
        $demande->setCampagne($campagne);
        $demande->setDemandeur($demandeur);
        //$this->utilisateurManager->majInscriptionsEtIdentite($demandeur, $this->now(), null);
        $demande->setEtat($this->etatDemandeRepository->find(EtatDemande::EN_COURS));

        /**
         * On récupère les réponses aux questions de ce type demande s'il y en a!
         * @var Reponse[] $reponsesExistantes
         */
        $reponsesExistantes = $this->reponseRepository->findBy([
            'repondant' => $demandeur,
        ]);

        foreach ($campagne->getTypeDemande()->getEtapes() as $etape) {
            foreach ($etape->getQuestionsEtape() as $questionEtape) {
                if (!$questionEtape->getQuestion()->isReponseConservable()) {
                    continue;
                }
                //on a des réponses existantes à cette question?
                $reponses = array_filter($reponsesExistantes, fn(Reponse $reponse) => $reponse->getQuestion() === $questionEtape->getQuestion());
                if (!empty($reponses)) {
                    usort($reponses, fn(Reponse $a, Reponse $b) => $a->getDateModification() <=> $b->getDateModification());
                    $reponseExistante = array_pop($reponses);
                    $reponse = new Reponse();
                    $reponse->setRepondant($demandeur);
                    $reponse->setQuestion($questionEtape->getQuestion());
                    $reponse->setCampagne($campagne);
                    $reponse->setCommentaire($reponseExistante->getCommentaire());
                    foreach ($reponseExistante->getOptionsChoisies() as $optionChoisie) {
                        $reponse->addOptionsChoisie($optionChoisie);
                    }
                    $reponse->setDateModification($this->now());
                    $this->reponseRepository->save($reponse);//on flush pas tout de suite, pas la peine
                }

            }
        }

        $this->demandeRepository->save($demande, true);

        $resource = $this->transformerService->transform($demande, Demande::class);

        $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));

        return $resource;
    }
}