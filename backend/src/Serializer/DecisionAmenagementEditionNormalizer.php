<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
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
use App\Service\ParametreService;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use App\Util\AnneeUniversitaireAwareTrait;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class DecisionAmenagementEditionNormalizer implements NormalizerInterface
{
    use AnneeUniversitaireAwareTrait;

    public function __construct(
        private DecisionAmenagementManager $decisionAmenagementManager,
        private StorageProviderInterface $storageProvider,
        private ParametreService $parametreService,
    ) {}

    /**
     * @param DecisionAmenagementExamens $object
     * @param string|null $format
     * @param array $context
     * @return array
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        //On génère un tableau contenant l'état de la décision + tous les aménagements d'examen concernés
        $data[0] = $object;

        $entity = $this->decisionAmenagementManager->parUidEtAnnee($object->uid, $object->annee);
        $data['amenagements'] = array_filter($entity
            ->getBeneficiaire()
            ->getAmenagementsActifs(), fn($amenagement) => $amenagement->getType()->isExamens());

        $data['annee'] = $this->anneeDuJour($this->now());
        $data['lieu'] = $this->parametreService->valeur(Parametre::LIEU_COURRIER);
        $data['adresse_postale'] = $this->parametreService->valeur(Parametre::ADRESSE_POSTALE);
        $data['president']['qualite'] = $this->parametreService->valeur(Parametre::PRESIDENT_QUALITE);
        $data['president']['nom'] = $this->parametreService->valeur(Parametre::PRESIDENT_NOM);
        $data['responsable_phase']['qualite'] = $this->parametreService->valeur(Parametre::RESPONSABLE_PHASE_QUALITE);
        $data['responsable_phase']['nom'] = $this->parametreService->valeur(Parametre::RESPONSABLE_PHASE_NOM);

        /**
         * Signature stockée en paramètre
         */
        $fichier = $this->parametreService->valeur(Parametre::SIGNATURE_DECISIONS);

        if ($fichier !== null) {
            $file = $this->storageProvider->get($fichier->getMetadata());
            if ($file instanceof File) {
                $file = $file->getContent();
            }
            $file = base64_encode($file);
        }

        $data['responsable_phase']['signature']['contents'] = $file ?? null;
        $data['responsable_phase']['signature']['mimeType'] = $fichier?->getTypeMime();

        return array_merge($data, $this->parametreService->getAppEnv());
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
