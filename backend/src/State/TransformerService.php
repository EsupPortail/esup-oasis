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

use Doctrine\Common\Util\ClassUtils;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Fournit le service de transformation des Entity en ApiResource adaptées
 */
class TransformerService
{
    private array $availableTransformations;

    public function __construct()
    {
    }

    /**
     * @param string $from
     * @param string $to
     * @param callable $callback
     * @return void
     */
    public function addTransformation(string $from, string $to, callable $callback): void
    {
        $this->availableTransformations[$from][$to] = $callback;
    }

    /**
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function canTransform(string $from, string $to): bool
    {
        return isset($this->availableTransformations[$from][$to]);
    }

    /**
     * @param ?object $entity
     * @param string $to
     * @return ?object
     * @throws Exception
     */
    public function transform(?object $entity, string $to): mixed
    {
        if (null === $entity) {
            return null;
        }
        $from = ClassUtils::getClass($entity);
        if (!$this->canTransform($from, $to)) {
            throw new Exception('transformation non disponible : ' . $from . ' => ' . $to);
        }
        return $this->availableTransformations[$from][$to]($entity);
    }


    /**
     * @param AbstractTransformer[] $handlers
     */
    #[Required]
    public function initDependencies(#[AutowireIterator('transformateur', indexAttribute: 'key')] iterable $handlers): void
    {
        foreach ($handlers as $handler) {
            $handler->setTransformerService($this);
        }
    }
}