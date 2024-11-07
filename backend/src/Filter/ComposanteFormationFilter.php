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

namespace App\Filter;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\ApiResource\Composante;
use App\ApiResource\Formation;
use App\Entity\Utilisateur;
use App\Entity\Amenagement;
use App\Repository\FormationRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ComposanteFormationFilter extends AbstractFilter
{

    use ClockAwareTrait;

    public function __construct(private readonly Security              $security,
                                private FormationRepository            $formationRepository,
                                private readonly IriConverterInterface $iriConverter,
                                protected ManagerRegistry              $managerRegistry,
                                ?LoggerInterface                       $logger = null,
                                protected ?array                       $properties = null,
                                protected ?NameConverterInterface      $nameConverter = null,)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    #[Override] protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator,
                                                  string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        /**
         * On ne veut que les aménagements des bénéficiaires actifs
         * pour lesquels l'étudiant liés est inscrit à l'instant T dans la composante demandée
         */

        if (!in_array($operation->getClass(), [Amenagement::class, Utilisateur::class]) || !in_array($property, ['composante', 'formation'])) {
            return;
        }

        $values = $this->cleanValues($value, $property);

        $alias = $queryBuilder->getRootAliases()[0];
        $benefAlias = $queryNameGenerator->generateJoinAlias('beneficiaires');
        $utilisateurAlias = $queryNameGenerator->generateJoinAlias('utilisateur');
        $inscriptionsAlias = $queryNameGenerator->generateJoinAlias('inscriptions');
        $formationAlias = $queryNameGenerator->generateJoinAlias('formation');

        $nowParam = $queryNameGenerator->generateParameterName('now');

        $inscriptionJoinWith = sprintf(':%s between %s.debut and %s.fin', $nowParam, $inscriptionsAlias, $inscriptionsAlias);

        if ($operation->getClass() == Amenagement::class) {
            $queryBuilder->join(sprintf('%s.beneficiaires', $alias), $benefAlias)
                ->join(sprintf('%s.utilisateur', $benefAlias), $utilisateurAlias)
                ->join(sprintf('%s.inscriptions', $utilisateurAlias), $inscriptionsAlias, Join::WITH, $inscriptionJoinWith)
                ->join(sprintf('%s.formation', $inscriptionsAlias), $formationAlias);
        } else {
            //on est sur Utilisateur
            $queryBuilder->join(sprintf('%s.inscriptions', $alias), $inscriptionsAlias, Join::WITH, $inscriptionJoinWith)
                ->join(sprintf('%s.formation', $inscriptionsAlias), $formationAlias);
        }
        $exprs = [];

        /**
         * @var Composante|Formation $entity
         */
        foreach ($values as $value) {
            try {
                $entity = $this->iriConverter->getResourceFromIri($value);
            } catch (NotFoundHttpException $e) {
                throw new NotFoundHttpException(
                    sprintf('Valeur inconnue pour %s ("%s")', $property, $value)
                );
            }
            if (!($entity instanceof Formation || $entity instanceof Composante)) {
                throw new BadRequestHttpException(
                    sprintf('le paramètre %s attend une IRI de %s', $property, ucfirst($property))
                );
            }

            $idParam = $queryNameGenerator->generateParameterName('id');

            if ($property == 'composante') {
                $composanteAlias = $queryNameGenerator->generateJoinAlias('composante');
                $queryBuilder->leftJoin(sprintf('%s.composante', $formationAlias), $composanteAlias);
                $exprs[] = $queryBuilder->expr()->eq(':' . $idParam, sprintf('%s.id', $composanteAlias));
            } else {
                $exprs[] = $queryBuilder->expr()->eq(':' . $idParam, sprintf('%s.id', $formationAlias));
            }
            $queryBuilder->setParameter($idParam, $entity->id);
        }

        $orX = $queryBuilder->expr()->orX();
        $orX->addMultiple($exprs);
        $queryBuilder->andWhere($orX)
            ->setParameter($nowParam, $this->now());

        return;
    }


    /**
     * Vérifie que les composantes demandées sont bien accessibles à l'utilisateur, sinon les retire
     *
     * @param $values
     * @return array
     */
    private function cleanValues($values, $property)
    {
        $values = is_array($values) ? $values : [$values];

        if ($this->security->isGranted(Utilisateur::ROLE_REFERENT_COMPOSANTE)) {
            /**
             * @var Utilisateur $user
             */
            $user = $this->security->getUser();

            $composantesAccessiblesIRIs = array_map(
                $this->getComposanteIri(...),
                $user->getComposantes()->toArray()
            );

            if ($property === 'composante') {
                //on retire ce qui n'est pas accessible
                $values = array_filter($values, fn($iri) => in_array($iri, $composantesAccessiblesIRIs));

                if (empty($values)) { //on évite de perdre le filtrage sur les composantes autorisées...
                    $values = $composantesAccessiblesIRIs;
                }
            } else {
                //formation, on ne conserve que celles qui sont ok
                $formationsAccessiblesIRIs = array_map(
                    $this->getFormationIri(...),
                    $this->formationRepository->findByComposantes($user->getComposantes())
                );

                $values = array_filter($values, fn($iri) => in_array($iri, $formationsAccessiblesIRIs));

                if (empty($values)) { //on évite de perdre le filtrage sur les composantes autorisées...
                    $values = $formationsAccessiblesIRIs;
                }
            }
        }

        return $values;
    }

    private function getComposanteIri(\App\Entity\Composante $composante)
    {
        return Composante::COLLECTION_URI . '/' . $composante->getId();
    }

    private function getFormationIri(\App\Entity\Formation $formation)
    {
        return Formation::COLLECTION_URI . '/' . $formation->getId();
    }


    #[Override] public function getDescription(string $resourceClass): array
    {
        $description = [];
        $description['composante'] = [
            'property' => "'composante",
            'type' => Type::BUILTIN_TYPE_STRING,
            'required' => false,
            'is_collection' => false,
            'openapi' => [
                'description' => "composante d'inscription du bénéficiaire",
                'name' => 'composante',
                'type' => 'string',
            ],
        ];
        $description['composante[]'] = [
            'property' => 'composante',
            'type' => Type::BUILTIN_TYPE_STRING,
            'required' => false,
            'is_collection' => true,
            'openapi' => [
                'description' => "composante d'inscription du bénéficiaire",
                'name' => 'composante',
                'type' => 'string',
            ],
        ];

        $description['formation'] = [
            'property' => "'formation",
            'type' => Type::BUILTIN_TYPE_STRING,
            'required' => false,
            'is_collection' => false,
            'openapi' => [
                'description' => "formation d'inscription du bénéficiaire",
                'name' => 'composante',
                'type' => 'string',
            ],
        ];
        $description['formation[]'] = [
            'property' => 'formation',
            'type' => Type::BUILTIN_TYPE_STRING,
            'required' => false,
            'is_collection' => true,
            'openapi' => [
                'description' => "formation d'inscription du bénéficiaire",
                'name' => 'composante',
                'type' => 'string',
            ],
        ];

        return $description;
    }


}