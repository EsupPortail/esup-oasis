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

namespace App\Serializer;

use App\ApiResource\DecisionAmenagementExamens;
use App\Entity\Parametre;
use App\Repository\ParametreRepository;
use App\Service\FileStorage\StorageProviderInterface;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use App\Util\AnneeUniversitaireAwareTrait;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class DecisionAmenagementEditionNormalizer implements NormalizerInterface
{

    use AnneeUniversitaireAwareTrait;

    public function __construct(private DecisionAmenagementManager $decisionAmenagementManager,
                                private StorageProviderInterface   $storageProvider,
                                private ParametreRepository        $parametreRepository)
    {

    }

    /**
     * @param DecisionAmenagementExamens $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        //On génère un tableau contenant l'état de la décision + tous les aménagements d'examen concernés
        $data[0] = $object;

        $entity = $this->decisionAmenagementManager->parUidEtAnnee($object->uid, $object->annee);
        $data['amenagements'] = array_filter(
            $entity->getBeneficiaire()->getAmenagementsActifs(),
            fn($amenagement) => $amenagement->getType()->isExamens()
        );

        $data['annee'] = $this->anneeDuJour($this->now());
        $data['president']['qualite'] = $this->parametreRepository->findOneBy([
            'cle' => 'PRESIDENT_QUALITE',
        ])?->getValeurCourante()->getValeur();
        $data['president']['nom'] = $this->parametreRepository->findOneBy([
            'cle' => 'PRESIDENT_NOM',
        ])?->getValeurCourante()->getValeur();
        $data['responsable_phase']['qualite'] = $this->parametreRepository->findOneBy([
            'cle' => 'RESPONSABLE_PHASE_QUALITE',
        ])?->getValeurCourante()->getValeur();
        $data['responsable_phase']['nom'] = $this->parametreRepository->findOneBy([
            'cle' => 'RESPONSABLE_PHASE_NOM',
        ])?->getValeurCourante()->getValeur();

        /**
         * Signature stockée en paramètre
         */
        $fichier = $this->parametreRepository->findOneBy([
            'cle' => Parametre::SIGNATURE_DECISIONS,
        ])->getValeurCourante()?->getFichier();

        if ($fichier !== null) {
            $file = $this->storageProvider->get($fichier->getMetadata());
            if ($file instanceof File) {
                $file = $file->getContent();
            }
            $file = base64_encode($file);
        }

        $data['responsable_phase']['signature']['contents'] = $file ?? null;
        $data['responsable_phase']['signature']['mimeType'] = $fichier?->getTypeMime();

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!$data instanceof DecisionAmenagementExamens || $format != 'pdf') {
            return false;
        }

        return true;
    }

    function getSupportedTypes(?string $format): array
    {
        if (!in_array($format, ['pdf'])) {
            return [];
        }

        return [DecisionAmenagementExamens::class => false];
    }


}