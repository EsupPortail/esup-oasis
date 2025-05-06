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

namespace App\State\Reponse;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Demande;
use App\ApiResource\Reponse;
use App\ApiResource\Telechargement;
use App\Entity\EtatDemande;
use App\Entity\Question;
use App\Message\EtatDemandeModifieMessage;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\DemandeRepository;
use App\Repository\EtatDemandeRepository;
use App\Repository\FichierRepository;
use App\Repository\OptionReponseRepository;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use App\State\OptionReponse\OptionReponseProvider;
use App\State\TransformerService;
use Exception;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ReponseProcessor implements ProcessorInterface
{
    use ClockAwareTrait;

    public function __construct(private readonly ReponseRepository       $reponseRepository,
                                private readonly DemandeRepository       $demandeRepository,
                                private readonly QuestionRepository      $questionRepository,
                                private readonly OptionReponseRepository $optionReponseRepository,
                                private readonly FichierRepository       $fichierRepository,
                                private readonly EtatDemandeRepository   $etatDemandeRepository,
                                private readonly OptionReponseProvider   $optionReponseProvider,
                                private readonly TransformerService      $transformerService,
                                private readonly MessageBusInterface     $messageBus,
                                private readonly Security                $security)
    {
    }

    /**
     * @param Reponse $data
     * @throws Exception
     * @throws ExceptionInterface
     */
    #[Override] public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Reponse
    {
        $demande = $this->demandeRepository->find($uriVariables['demandeId']);
        $question = $this->questionRepository->find($uriVariables['questionId']);

        /**
         * Support de PUT uniquement
         */
        $reponse = $this->reponseRepository->findOneBy(
            [
                'campagne' => $demande->getCampagne(),
                'question' => $question,
                'repondant' => $demande->getDemandeur(),
            ]
        );

        $new = false;
        if (!$reponse) {
            $reponse = new \App\Entity\Reponse();
            $new = true;
        }


        $optionEntites = array_map(fn($option) => $this->optionReponseRepository->find($option->id), $data->optionsChoisies);

        $reponse->setCampagne($demande->getCampagne());
        $reponse->setQuestion($question);
        $reponse->setCommentaire($data->commentaire);
        $reponse->setRepondant($demande->getDemandeur());
        $reponse->setDateModification(new DatePoint());

        if (null === ($tableName = $reponse->getQuestion()->getTableOptions())
            || $reponse->getQuestion()->getTypeReponse() === Question::TYPE_TEXT) {
            /**
             * Cas des questions avec options "classiques"
             */
            foreach ($reponse->getOptionsChoisies() as $existante) {
                if (!in_array($existante, $optionEntites)) {
                    $reponse->removeOptionsChoisie($existante);
                }
            }
            foreach ($optionEntites as $option) {
                $reponse->addOptionsChoisie($option);
            }
        } else {
            /**
             * Cas des questions avec options basées sur une table de ref
             */
            $reponse = $this->optionReponseProvider->majReponseAvecOptions($reponse, $tableName, $data->optionsChoisies);
        }

        /**
         * Fichiers attachés
         */
        $reponse->setPiecesJustificatives(
            array_map(
                fn(Telechargement $pj) => $this->fichierRepository->find($pj->id),
                $data->piecesJustificatives
            )
        );

        $this->reponseRepository->save($reponse, true);

        /**
         * Est-ce qu'on a validé tout le questionnaire?
         */
        $demandeResource = $this->transformerService->transform($demande, Demande::class);
        if ($question->getTypeReponse() == Question::TYPE_SUBMIT) {
            //vérification faite en amont par ValidationDemandePossibleConstraintValidator
            $etatPrecedent = $demande->getEtat();
            $demande->setEtat($this->etatDemandeRepository->find(EtatDemande::RECEPTIONNEE));
            $demande->setDateDepot($this->now());
            $this->demandeRepository->save($demande, true);

            $this->messageBus->dispatch(new EtatDemandeModifieMessage(
                    $demande->getId(),
                    $etatPrecedent->getId(),
                    $demande->getEtat()->getId(),
                    $this->security->getuser()->getUserIdentifier(),
                    null,
                    null
                )
            );

        }

        //la demande doit être invalidée dans tous les cas - recalcul du champ 'complete"
        $this->messageBus->dispatch(new RessourceModifieeMessage($demandeResource));

        $resource = $this->transformerService->transform($reponse, Reponse::class);
        if ($new) {
            $this->messageBus->dispatch(new RessourceModifieeMessage($resource));
        } else {
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($resource));
        }

        return $resource;
    }
}