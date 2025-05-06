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

namespace App\State\OptionReponse;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\OptionReponse;
use App\ApiResource\Question;
use App\Entity\DisciplineArtistique;
use App\Entity\TypeAmenagement;
use App\Entity\ClubSportif;
use App\Entity\Reponse;
use App\Entity\TypeEngagement;
use App\Entity\TypologieHandicap;
use App\Repository\CategorieAmenagementRepository;
use App\Repository\DisciplineArtistiqueRepository;
use App\Repository\EtablissementEnseignementArtistiqueRepository;
use App\Repository\TypeAmenagementRepository;
use App\Repository\ClubSportifRepository;
use App\Repository\DisciplineSportiveRepository;
use App\Repository\QuestionRepository;
use App\Repository\TypeEngagementRepository;
use App\Repository\TypologieHandicapRepository;
use App\State\AbstractEntityProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OptionReponseProvider extends AbstractEntityProvider
{

    public function __construct(#[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')] ProviderInterface       $itemProvider,
                                #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')] ProviderInterface $collectionProvider,
                                private readonly CategorieAmenagementRepository                                               $categorieAmenagementRepository,
                                private readonly ClubSportifRepository                                                        $clubSportifRepository,
                                private readonly QuestionRepository                                                           $questionRepository,
                                private readonly DisciplineSportiveRepository                                                 $disciplineSportiveRepository,
                                private readonly DisciplineArtistiqueRepository                                               $disciplineArtistiqueRepository,
                                private readonly EtablissementEnseignementArtistiqueRepository                                $etablissementEnseignementArtistiqueRepository,
                                private readonly TypologieHandicapRepository                                                  $typologieHandicapRepository,
                                private readonly TypeEngagementRepository                                                     $typeEngagementRepository,
                                private readonly TypeAmenagementRepository                                                    $amenagementRepository,)
    {
        parent::__construct($itemProvider, $collectionProvider);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /**
         * Seul GET sur items est supporté, deux cas :
         * - question avec liste d'options liées
         * - question avec une table représentant la liste d'options
         */
        $question = $this->questionRepository->find($uriVariables['questionId']);
        if (null !== ($table = $question->getTableOptions())) {
            $option = $this->getOptionForTable($table, $uriVariables['id']);
            $option->questionId = $uriVariables['questionId'];
            return $option;
        }


        $relevantVariables = ['id' => $uriVariables['id']];

        $link = new Link(parameterName: 'id', fromClass: OptionReponse::class, identifiers: ['id']);
        $correctOperation = (new (get_class($operation)))->withClass(OptionReponse::class)
            ->withStateOptions($operation->getStateOptions())
            ->withUriVariables([$link]);

        return parent::provide($correctOperation, $relevantVariables, $context);
    }

    #[Override] protected function getResourceClass(): string
    {
        return OptionReponse::class;
    }

    #[Override] protected function getEntityClass(): string
    {
        return \App\Entity\OptionReponse::class;
    }

    /**
     * @param \App\Entity\OptionReponse $entity
     * @return OptionReponse
     * @throws Exception
     */
    #[Override] public function transform($entity): mixed
    {
        $resource = new OptionReponse();
        $resource->id = $entity->getId();
        $resource->libelle = $entity->getLibelle();
        $resource->questionId = $entity->getQuestion()->getId();
        $resource->questionsLiees = array_map(
            fn($question) => $this->transformerService->transform($question, Question::class),
            $entity->getQuestionsLiees()->toArray()
        );
        return $resource;
    }


    /**
     * @param string $tableName
     * @param int $questionId
     * @return array
     * @throws Exception
     */
    public function getOptionsForTable(string $tableName, int $questionId): array
    {
        $originalValues = match ($tableName) {
            'discipline_sportive' => $this->getDisciplinesSportives(),
            'discipline_artistique' => $this->getDisciplinesArtistiques(),
            'typologie_handicap' => $this->getTypologiesHandicap(),
            'type_engagement' => $this->getTypesEngagements(),
            'amenagement_examens' => $this->getAmenagementsExamens(),
            'amenagement_pedagogique' => $this->getAmenagementsPedagogiques(),
            'categorie_amenagement_examens' => $this->getCategoriesAmenagementExamens(),
            'categorie_amenagement_pedagogique' => $this->getCategoriesAmenagementPedagogique(),
            'clubs_centre_formation' => $this->getClubsCentresFormation(),
            'clubs_professionnels' => $this->getClubsProfessionnels(),
            'etablissement_artistique' => $this->getEtablissementsArtistiques(),
            default => []
        };

        $options = array_map(
            function ($item) use ($questionId) {
                $option = $this->transformerService->transform($item, OptionReponse::class);
                $option->questionId = $questionId;
                return $option;
            },
            $originalValues
        );

        usort($options, function (OptionReponse $a, OptionReponse $b) {
            return strtolower($a->libelle) <=> strtolower($b->libelle);
        });

        return $options;
    }

    /**
     * @param string $tableName
     * @param mixed $id
     * @return mixed
     * @throws Exception
     */
    private function getOptionForTable(string $tableName, int $id): OptionReponse
    {
        $repo = $this->getRepository($tableName);
        return $this->transformerService->transform($repo->find($id), OptionReponse::class);
    }

    /**
     * @param Reponse $reponse
     * @return array
     * @throws Exception
     */
    public function getOptionsForReponse(Reponse $reponse): array
    {
        $options = match ($reponse->getQuestion()->getTableOptions()) {
            null => $reponse->getOptionsChoisies()->toArray(),
            'sportif_haut_niveau' => [], //pas réellement des options
            'discipline_sportive' => $reponse->getDisciplinesSportives()->toArray(),
            'discipline_artistique' => $reponse->getDisciplinesArtistiques()->toArray(),
            'etablissement_artistique' => $reponse->getEtablissementsEnseignementArtistique()->toArray(),
            'typologie_handicap' => $reponse->getTypologiesHandicap()->toArray(),
            'type_engagement' => $reponse->getTypesEngagement()->toArray(),
            'amenagement_examens', 'amenagement_pedagogique' => $reponse->getTypesAmenagement()->toArray(),
            'categorie_amenagement_examens', 'categorie_amenagement_pedagogique' => $reponse->getCategoriesAmenagement()->toArray(),
            'clubs_professionnels', 'clubs_centre_formation' => $reponse->getClubs()->toArray(),
        };

        return array_values(array_map(
                function ($option) use ($reponse) {
                    $res = $this->transformerService->transform($option, OptionReponse::class);
                    $res->questionId = $reponse->getQuestion()->getId();
                    return $res;
                },
                $options
            )
        );
    }

    /**
     * @param Reponse $reponse
     * @param string $tableName
     * @param array $optionsChoisies
     * @return Reponse
     */
    public function majReponseAvecOptions(Reponse $reponse, string $tableName, array $optionsChoisies): Reponse
    {
        $repo = $this->getRepository($tableName);
        $options = array_map(
            fn($option) => $repo->find($option->id),
            $optionsChoisies
        );

        $reponse = match ($tableName) {
            'discipline_sportive' => $reponse->majDisciplinesSportives($options),
            'discipline_artistique' => $reponse->majDisciplinesArtistiques($options),
            'typologie_handicap' => $reponse->majTypologies($options),
            'type_engagement' => $reponse->majTypesEngagement($options),
            'amenagement_examens', 'amenagement_pedagogique' => $reponse->majAmenagements($options),
            'categorie_amenagement_examens', 'categorie_amenagement_pedagogique' => $reponse->majCategoriesAmenagement($options),
            'clubs_professionnels', 'clubs_centre_formation' => $reponse->majClubs($options),
            'etablissement_artistique' => $reponse->majEtablissementsEnseignementArtistique($options),
        };

        return $reponse;
    }

    /**
     * @param string $tableName
     * @return ServiceEntityRepository
     */
    protected function getRepository(string $tableName): ServiceEntityRepository
    {
        return match ($tableName) {
            'discipline_sportive' => $this->disciplineSportiveRepository,
            'discipline_artistique' => $this->disciplineArtistiqueRepository,
            'typologie_handicap' => $this->typologieHandicapRepository,
            'type_engagement' => $this->typeEngagementRepository,
            'amenagement_examens', 'amenagement_pedagogique' => $this->amenagementRepository,
            'categorie_amenagement_examens', 'categorie_amenagement_pedagogique' => $this->categorieAmenagementRepository,
            'clubs_professionnels', 'clubs_centre_formation' => $this->clubSportifRepository,
            'etablissement_artistique' => $this->etablissementEnseignementArtistiqueRepository,
        };
    }

    /**
     * @return array
     */
    private function getDisciplinesSportives(): array
    {
        return $this->disciplineSportiveRepository->findBy([
            'actif' => true,
        ]);
    }

    /**
     * @return DisciplineArtistique[]
     */
    private function getDisciplinesArtistiques(): array
    {
        return $this->disciplineArtistiqueRepository->findBy([
            'actif' => true,
        ]);
    }

    /**
     * @return TypologieHandicap[]
     */
    private function getTypologiesHandicap(): array
    {
        return $this->typologieHandicapRepository->findBy([
            'actif' => true,
        ]);
    }

    /**
     * @return TypeEngagement[]
     */
    private function getTypesEngagements(): array
    {
        return $this->typeEngagementRepository->findBy([
            'actif' => true,
        ]);
    }

    /**
     * @return TypeAmenagement[]
     */
    private function getAmenagementsExamens(): array
    {
        return $this->amenagementRepository->findBy([
            'actif' => true,
            'examens' => true,
        ]);
    }

    /**
     * @return TypeAmenagement[]
     */
    private function getAmenagementsPedagogiques(): array
    {
        return $this->amenagementRepository->findBy([
            'actif' => true,
            'pedagogique' => true,
        ]);
    }

    /**
     * @return ClubSportif[]
     */
    private function getClubsCentresFormation(): array
    {
        return $this->clubSportifRepository->findBy([
            'actif' => true,
            'centreFormation' => true,
        ]);
    }

    /**
     * @return ClubSportif[]
     */
    private function getClubsProfessionnels(): array
    {
        return $this->clubSportifRepository->findBy([
            'actif' => true,
            'professionnel' => true,
        ]);
    }

    private function getCategoriesAmenagementExamens(): array
    {
        return $this->categorieAmenagementRepository->getByType(examens: true);
    }

    private function getCategoriesAmenagementPedagogique(): array
    {
        return $this->categorieAmenagementRepository->getByType(pedagogique: true);
    }

    private function getEtablissementsArtistiques(): array
    {
        return $this->etablissementEnseignementArtistiqueRepository->findBy([
            'actif' => true,
        ]);
    }

}