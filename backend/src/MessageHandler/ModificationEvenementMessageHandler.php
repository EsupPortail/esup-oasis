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

namespace App\MessageHandler;

use App\Message\ModificationEvenementMessage;
use App\Repository\EvenementRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(handles: ModificationEvenementMessage::class)]
readonly class ModificationEvenementMessageHandler
{
    public function __construct(private TagAwareCacheInterface $cache,
                                private EvenementRepository    $evenementRepository)
    {

    }

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(ModificationEvenementMessage $message): void
    {
        $event = $this->evenementRepository->find($message->getId());
        $collectionTags = [];
        if (null !== $event) { //pas un delete, qui sera géré via dateOrigine
            $collectionTags = ['collection_evenements_' . $event->getDebut()->format('Y-m-d')];
        }
        if (null !== ($dateOrigine = $message->getDateOrigine())) {
            $collectionTags[] = 'collection_evenements_' . $dateOrigine->format('Y-m-d');
        }
        if ($message->isCreation() || ($dateOrigine ?? false)) {
            $collectionTags[] = 'collection_evenements_sans_dates';
        }

        //On invalide le cache pour l'événement lui-même et pour toutes les collections pouvant le contenir
        $this->cache->invalidateTags([
            'evenement_' . $message->getId(),
            ...$collectionTags,
        ]);
    }
}