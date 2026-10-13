<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

namespace App\Service\Decision;

use App\Entity\DecisionAmenagementExamens;
use App\Entity\Fichier;
use App\Entity\PieceJointeBeneficiaire;
use App\Entity\Utilisateur;
use App\Repository\PieceJointeBeneficiaireRepository;
use App\Service\FileStorage\StorageProviderInterface;
use Symfony\Component\Clock\ClockAwareTrait;

/**
 * Dépose une copie du PDF de la décision au dossier du bénéficiaire, après envoi par e-mail
 * ou retour du parapheur, signée ou refusée. Les copies antérieures sont conservées.
 */
readonly class ArchivageDecision
{
    use ClockAwareTrait;

    public function __construct(
        private PieceJointeBeneficiaireRepository $pieceJointeBeneficiaireRepository,
        private StorageProviderInterface $storageProvider,
    ) {}

    /**
     * @param string $pdf contenu binaire du PDF
     * @param Utilisateur $auteur utilisateur au nom duquel la pièce jointe est déposée
     * @param ?string $etatSignature null après un envoi par e-mail, sinon l'état rendu par le parapheur
     *                               (DecisionAmenagementExamens::ETAT_SIGNATURE_*)
     */
    public function archiver(
        DecisionAmenagementExamens $decision,
        string $pdf,
        Utilisateur $auteur,
        ?string $etatSignature = null,
    ): void {
        $dateDepot = $this->now();
        $mimeType = 'application/pdf';

        [$suffixe, $qualificatif] = match ($etatSignature) {
            null => ['', ''],
            DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE => ['-signee', ' signée'],
            DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE => ['-refusee', ' refusée'],
            default => ['-non-signee', ' non signée'],
        };
        $filename = 'decision-' . $decision->getId() . $suffixe . '.pdf';
        $description = "Décision d'aménagements" . $qualificatif . ' au ' . $dateDepot->format('d/m/Y');

        $metadata = $this->storageProvider->store(
            contents: $pdf,
            filename: $filename,
            mimeType: $mimeType,
            description: $description,
        );

        $fichier = new Fichier();
        $fichier->setNom($filename);
        $fichier->setProprietaire($decision->getBeneficiaire());
        $fichier->setMetadata($metadata);
        $fichier->setTypeMime($mimeType);
        // une décision rendue sans signature n'est pas le document de la décision : copie au dossier seulement
        if (null === $etatSignature || DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE === $etatSignature) {
            $decision->setFichier($fichier);
        }

        $pieceJointe = new PieceJointeBeneficiaire();
        $pieceJointe->setFichier($fichier);
        $pieceJointe->setBeneficiaire($decision->getBeneficiaire());
        $pieceJointe->setUtilisateurCreation($auteur);
        $pieceJointe->setDateDepot($dateDepot);
        $pieceJointe->setLibelle($description);

        $this->pieceJointeBeneficiaireRepository->save($pieceJointe, true);
    }
}
