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

namespace App\State;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use App\Message\RessourceCollectionModifieeMessage;
use App\Message\RessourceModifieeMessage;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Persistence\ManagerRegistry;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Messenger\MessageBusInterface;


readonly class MappedEntityProcessor
{
    public function __construct(private ManagerRegistry         $managerRegistry,
                                private MappedEntityTransformer $transformer,
                                private MessageBusInterface     $messageBus)
    {
    }

    /**
     * @param mixed $data
     * @param Operation $operation
     * @param string $entityClass
     * @param array $uriVariables
     * @param array $context
     * @param ?callable $onCreation Callback called on successful creation
     * @param ?callable $onModification Callback called on successful modification
     * @param ?callable $onDeletion Callback called on successful deletion
     * @return array|mixed|object|null
     * @throws ReflectionException
     * @noinspection PhpUnusedParameterInspection
     */
    public function process(mixed     $data, Operation $operation, string $entityClass,
                            array     $uriVariables = [], array $context = [],
                            ?callable $onCreation = null, ?callable $onModification = null,
                            ?callable $onDeletion = null): mixed
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        $repository = $manager->getRepository($entityClass);
        //Si POST, il faut prendre tout ce qu'il y a en entrée tel quel...
        if ($operation instanceof Post) {
            if (!$data instanceof $entityClass) {
                $entity = $this->transformer->transform($data, $entityClass, $context['map_groups'] ?? []);
                $this->reLinkExistingEntities($entity, $context, $data);
            } else {
                $entity = $data;
            }
            $repository->save($entity, true);
            if (is_callable($onCreation)) {
                $onCreation($entity);
            }
            $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data));
            return $this->transformer->transform($entity, get_class($data));
        } else {
            if (!$data instanceof $entityClass) {
                $entity = $repository->find($data->id);
            } else {
                $entity = $data;
            }
            //PATCH-PUT ou DELETE?
            if ($operation instanceof Delete) {
                $this->messageBus->dispatch(new RessourceModifieeMessage($data));
                $this->messageBus->dispatch(new RessourceCollectionModifieeMessage($data));
                $repository->remove($entity);
                $manager->flush();
                if (is_callable($onDeletion)) {
                    $onDeletion($entity);
                }
            } else {
                //PATCH/PUT : on écrase tout avec le contenu de $data, qui est déjà patché
                if (!$data instanceof $entityClass) {
                    $this->messageBus->dispatch(new RessourceModifieeMessage($data));
                    $this->transformer->transform($data, $entity);
                    $this->reLinkExistingEntities($entity, $context, $data);
                }
                if (is_callable($onModification)) {
                    $onModification($entity, $context['previous_data'] ?? null);
                }
                $manager->flush();
            }
            return $data;
        }
    }

    /**
     * Ugly but works?
     *
     * @param object|string|null $entity
     * @param array $context
     * @param                    $data
     * @return void
     * @throws ReflectionException
     */
    private function reLinkExistingEntities(object|string|null $entity, array $context, $data): void
    {
        $reflectionClass = new ReflectionClass($entity);
        foreach ($context['existingEntities'] ?? [] as $existing) {
            $name = $existing;
            $reflectionProp = $reflectionClass->getProperty($name);

            $value = $reflectionProp->getValue($entity);
            if (null === $value) {
                //Rien à faire, on passe son chemin!
                return;
            }
            if (is_countable($value) && is_iterable($value)) {
                if (count($value) == 0) {
                    return;
                }
                $classname = get_class($value[0]);
                $registry = $this->managerRegistry->getManagerForClass($classname);

                foreach ($value as $key => $item) {
                    $ids = $this->getEntityIdentifiers($item);
                    $entityItem = $registry->getRepository($classname)->findOneBy($ids);
                    $value->remove($key);
                    if (null != $entityItem) {
                        $value->add($entityItem);
                    }
                }

            } else {
                $classname = get_class($value);
                $registry = $this->managerRegistry->getManagerForClass($classname);
                $ids = $this->getEntityIdentifiers($value);

                $entityItem = $registry->getRepository($classname)->findOneBy($ids);
                $reflectionProp->setValue($entity, $entityItem);
            }
        }
    }

    /**
     * @throws ReflectionException
     * @deprecated
     */
    private function getApiResourceIdentifiers($data): array
    {
        $res = [];
        $reflectionClass = new ReflectionClass($data);
        foreach ($reflectionClass->getProperties() as $property) {
            $reflectionProp = new ReflectionProperty(get_class($data), $property->name);
            $attributes = $reflectionProp->getAttributes(ApiProperty::class);
            foreach ($attributes as $attribute) {
                $args = $attribute->getArguments();
                if (array_key_exists('identifier', $args) && $args['identifier'] === true) {
                    $res[$property->name] = $property->getValue($data);
                }
            }
        }
        return $res;
    }

    /**
     * @param $data
     * @return array
     * @throws ReflectionException
     */
    private function getEntityIdentifiers($data): array
    {
        $res = [];
        $reflectionClass = new ReflectionClass($data);
        foreach ($reflectionClass->getProperties() as $property) {
            $reflectionProp = new ReflectionProperty(get_class($data), $property->name);
            $attributes = $reflectionProp->getAttributes(Id::class);
            if (!empty($attributes)) {
                $res[$property->name] = $property->getValue($data);
            }

        }
        return $res;
    }
}