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

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\CampagneDemande;
use App\ApiResource\Demande;
use App\ApiResource\EtapeDemandeEtudiant;
use App\ApiResource\EtatDemande;
use App\ApiResource\ProfilBeneficiaire;
use App\ApiResource\QuestionDemande;
use App\ApiResource\ReponseDemande;
use App\ApiResource\TypeDemande;
use App\ApiResource\Utilisateur;
use App\Entity\OptionReponse;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Filter\PreloadAssociationsFilter;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use App\Repository\TypeDemandeRepository;
use App\State\AbstractEntityProvider;
use DateTime;
use Exception;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ResetInterface;

class DemandeProvider extends AbstractEntityProvider implements ResetInterface
{

    private array $currentContext;

    public function __construct(#[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')] ProviderInterface       $itemProvider,
                                #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')] ProviderInterface $collectionProvider,
                                private readonly ReponseRepository                                                            $reponseRepository,
                                private readonly QuestionRepository                                                           $questionRepository,
                                private readonly TypeDemandeRepository                                                        $typeDemandeRepository,
                                protected readonly Security                                                                   $security)
    {
        parent::__construct($itemProvider, $collectionProvider);
    }

    #[Override]
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {

        if ($operation instanceof GetCollection) {
            $context = $this->addFilters($context);
        }

        $this->currentContext = $context;

        return parent::provide($operation, $uriVariables, $context);
    }

    /**
     * @param array $context
     * @return array
     */
    private function addFilters(array $context): array
    {
        $context['filters'][PreloadAssociationsFilter::PROPERTY] = [
            'demandeur' => [
                'sourceEntity' => 'root',
                'relationName' => 'demandeur'
            ],
            'inscriptions' => [
                'sourceEntity' => 'demandeur',
                'relationName' => 'inscriptions'
            ],
            'formation' => [
                'sourceEntity' => 'inscriptions',
                'relationName' => 'formation'
            ],
            'intervenant' => [
                'sourceEntity' => 'demandeur',
                'relationName' => 'intervenant',
            ],
            'services' => [
                'sourceEntity' => 'demandeur',
                'relationName' => 'services'
            ],
            'beneficiaires' => [
                'sourceEntity' => 'demandeur',
                'relationName' => 'beneficiaires'
            ],
            'profilBeneficiaire' => [
                'sourceEntity' => 'beneficiaires',
                'relationName' => 'profil'
            ],
            'decisions' => [
                'sourceEntity' => 'demandeur',
                'relationName' => 'decisionsAmenagementExamens'
            ],
            'campagne' => [
                'sourceEntity' => 'root',
                'relationName' => 'campagne'
            ],
            'typeDemande' => [
                'sourceEntity' => 'campagne',
                'relationName' => 'typeDemande'
            ],
            'etatDemande' => [
                'sourceEntity' => 'root',
                'relationName' => 'etat'
            ],
            'profilAttribue' => [
                'sourceEntity' => 'root',
                'relationName' => 'profilAttribue'
            ],
            'reponses' => [
                'sourceEntity' => 'demandeur',
                'relationName' => 'reponses'
            ],
            'question' => [
                'sourceEntity' => 'reponses',
                'relationName' => 'question'
            ],
            'optionsChoisies' => [
                'sourceEntity' => 'reponses',
                'relationName' => 'optionsChoisies'
            ],
            'optionsReponse' => [
                'sourceEntity' => 'question',
                'relationName' => 'optionsReponse'
            ],
            'questionsLiees' => [
                'sourceEntity' => 'optionsChoisies',
                'relationName' => 'questionsLiees'
            ],
            'optionsReponseLiees' => [
                'sourceEntity' => 'questionsLiees',
                'relationName' => 'optionsReponse'
            ],
            'pieces' => [
                'sourceEntity' => 'reponses',
                'relationName' => 'piecesJustificatives'
            ],
            'avis_ese' => [
                'sourceEntity' => 'pieces',
                'relationName' => 'avisEse'
            ],
            'entretien' => [
                'sourceEntity' => 'pieces',
                'relationName' => 'entretien'
            ],
            'bilan' => [
                'sourceEntity' => 'pieces',
                'relationName' => 'bilan'
            ],
            'piece_jointe_beneficiaire' => [
                'sourceEntity' => 'pieces',
                'relationName' => 'pieceJointeBeneficiaire'
            ],
            'decisions_amenagement_examens' => [
                'sourceEntity' => 'pieces',
                'relationName' => 'decisionAmenagementExamens'
            ]
        ];

        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_GESTIONNAIRE)) {
            return $context;
        }

        /**
         * @var \App\Entity\Utilisateur $user
         */
        $user = $this->security->getUser();

        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_MEMBRE_COMMISSION)) {
            if (($context['filters']['demandeur'] ?? '') !== Utilisateur::COLLECTION_URI . '/' . $user->getUserIdentifier()) {
                //on limite aux campagnes auxquelles ils ont accès
                $campagnesAccessibles = [];
                foreach ($user->getMembreCommissions() as $membreCommission) {
                    foreach ($membreCommission->getCommission()->getCampagnes() as $campagne) {
                        $campagnesAccessibles[] = TypeDemande::COLLECTION_URI . '/' . $campagne->getTypeDemande()->getId() . '/campagnes/' . $campagne->getId();
                    }
                }
                if (array_key_exists('campagne', $context['filters'])) {
                    if (!is_array($context['filters']['campagne'])) {
                        $context['filters']['campagne'] = [$context['filters']['campagne']];
                    }
                    $context['filters']['campagne'] = array_filter(
                        $context['filters']['campagne'],
                        fn($campagneIri) => in_array($campagneIri, $campagnesAccessibles)
                    );
                } else {
                    $context['filters']['campagne'] = $campagnesAccessibles;
                }
            }
            return $context;
        }

        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_RENFORT_DEMANDES)) {
            if (($context['filters']['demandeur'] ?? '') !== Utilisateur::COLLECTION_URI . '/' . $user->getUserIdentifier()) {
                //on ne montre pas les types avec visibilité limitée
                $typesNonLimites = $this->typeDemandeRepository->findBy(
                    [
                        'visibiliteLimitee' => false,
                    ]
                );
                $iriTypesValides = array_map(
                    fn(\App\Entity\TypeDemande $type) => TypeDemande::COLLECTION_URI . '/' . $type->getId(),
                    $typesNonLimites
                );
                if (array_key_exists('campagne.typeDemande', $context['filters'] ?? [])) {
                    if (!is_array($context['filters']['campagne.typeDemande'])) {
                        $context['filters']['campagne.typeDemande'] = [$context['filters']['campagne.typeDemande']];
                    }
                    $context['filters']['campagne.typeDemande'] = array_filter(
                        $context['filters']['campagne.typeDemande'],
                        fn($type) => in_array($type, $iriTypesValides)
                    );
                } else {
                    $context['filters']['campagne.typeDemande'] = $iriTypesValides;
                }
            }

            return $context;
        }

        //simple utilisateur, on filtre sur ses propres demandes
        $context['filters']['demandeur'] = Utilisateur::COLLECTION_URI . '/' . $user->getUserIdentifier();
        return $context;
    }

    #[Override]
    protected function getResourceClass(): string
    {
        return Demande::class;
    }

    #[Override]
    protected function getEntityClass(): string
    {
        return \App\Entity\Demande::class;
    }

    /**
     * @param \App\Entity\Demande $entity
     * @return Demande
     * @throws Exception
     */
    #[Override]
    public function transform($entity): mixed
    {
        $resource = new Demande();
        $resource->id = $entity->getId();
        $resource->dateDepot = $entity->getDateDepot();
        $resource->demandeur = $this->transformerService->transform($entity->getDemandeur(), Utilisateur::class);
        $resource->etat = new EtatDemande($entity->getEtat()->getId(), $entity->getEtat()->getLibelle());
        $resource->typeDemande = $this->transformerService->transform($entity->getCampagne()->getTypeDemande(), TypeDemande::class);
        $resource->campagne = $this->transformerService->transform($entity->getCampagne(), CampagneDemande::class);
        $resource->idCommission = $resource->campagne->commission?->id;
        $resource->profilAttribue = $this->transformerService->transform($entity->getProfilAttribue(), ProfilBeneficiaire::class);
        $resource->commentaire = $entity->getCommentaire();
        if (($this->currentContext['filters']['format_simple'] ?? false) === 'true') {
            return $resource;
        }

        $resource->complete = true;

        /**
         * On ajoute les questions/réponses
         */
        $reponsesExistantes = $this->reponseRepository->findBy([
            'campagne' => $entity->getCampagne(),
            'repondant' => $entity->getDemandeur(),
        ]);

        foreach ($entity->getCampagne()->getTypeDemande()->getEtapes() as $etape) {
            $etapeDemande = new EtapeDemandeEtudiant();
            $etapeDemande->id = $etape->getId();
            $etapeDemande->libelle = $etape->getLibelle();
            $etapeDemande->ordre = $etape->getOrdre();
            /**
             * Gestion des accompagnements optionnels
             */
            if ($entity->getCampagne()->getTypeDemande()->isAccompagnementOptionnel() && $etape->isSiDemandeAccompagnement()) {
                /**
                 * @var Reponse[] $reponse
                 */
                $reponse = array_filter(
                    $reponsesExistantes,
                    fn(Reponse $rep) => $rep->getQuestion()->getId() === Question::QUESTION_DEMANDE_ACCOMPAGNEMENT
                );
                //on ajoute la question
                $questionEntity = $this->questionRepository->find(Question::QUESTION_DEMANDE_ACCOMPAGNEMENT);
                $question = $this->initQuestionDemande($questionEntity);
                $question->reponse = match (count($reponse)) {
                    0 => null,
                    default => new ReponseDemande(current($reponse), $this->transformerService)
                };
                $resource->complete = match (count($reponse)) {
                    0 => false, //cette question est obligatoire dans ce cas!
                    default => $resource->complete
                };
                $etapeDemande->questions[] = $question;
                if (empty($reponse)
                    || current($reponse)->getOptionsChoisies()->first()->getId() === OptionReponse::OPTION_DEMANDE_ACCOMPAGNEMENT_NON) {
                    //on sort de suite, les autres questions sont sans objet
                    $resource->etapes[] = $etapeDemande;
                    continue;
                }
            }
            foreach ($etape->getQuestionsAvecReponsesExistantes($reponsesExistantes) as $questionEntity) {
                $question = $this->initQuestionDemande($questionEntity);
                $reponse = array_filter($reponsesExistantes, fn(Reponse $reponse) => $reponse->getQuestion() === $questionEntity);
                //on essaye de récupérer la réponse coté champ cible
                if (count($reponse) === 0 && null !== $questionEntity->getChampCible()) {
                    $valeur = $this->valeurPourChamp($questionEntity->getChampCible(), $entity->getDemandeur());
                    $reponseEntity = new Reponse();
                    $reponseEntity->setId(-1);
                    $reponseEntity->setCommentaire($valeur);
                    $reponseEntity->setQuestion($questionEntity);
                    $reponseEntity->setCampagne($entity->getCampagne());
                    $reponseEntity->setDateModification(new DateTime());
                    $reponseEntity->setRepondant($entity->getDemandeur());
                    $reponse = [$reponseEntity];
                }
                $question->reponse = match (count($reponse)) {
                    0 => null,
                    default => new ReponseDemande(current($reponse), $this->transformerService)
                };
                if ($question->obligatoire && null === $question->reponse) {
                    $resource->complete = false;
                }
                $etapeDemande->questions[] = $question;
            }
            $resource->etapes[] = $etapeDemande;
        }

        return $resource;
    }

    /**
     * @param Question|null $questionEntity
     * @return QuestionDemande
     */
    protected function initQuestionDemande(?Question $questionEntity): QuestionDemande
    {
        $question = new QuestionDemande();
        $question->id = $questionEntity->getId();
        $question->libelle = $questionEntity->getLibelle();
        $question->aide = $questionEntity->getAide();
        $question->typeReponse = $questionEntity->getTypeReponse();
        $question->obligatoire = $questionEntity->isObligatoire();
        $question->choixMultiple = $questionEntity->isChoixMultiple();
        $question->tableOptions = $questionEntity->getTableOptions();
        return $question;
    }


    public function reset(): void
    {
        $this->currentContext = [];
    }

    private function valeurPourChamp(?string $champ, ?\App\Entity\Utilisateur $demandeur): ?string
    {
        return match ($champ) {
            Question::CHAMP_CIBLE_EMAIL_PERSO => $demandeur->getEmailPerso(),
            Question::CHAMP_CIBLE_TEL_PERSO => $demandeur->getTelPerso(),
            Question::CHAMP_CONTACT_URGENCE => $demandeur->getContactUrgence(),
            default => null
        };
    }
}