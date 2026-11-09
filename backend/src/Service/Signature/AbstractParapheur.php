<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\Signature;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Parapheur électronique qui fait signer la décision d'aménagements, sélectionné par la variable
 * d'environnement PARAPHEUR. Le circuit (signataires, étapes, relances) est porté par le parapheur.
 */
#[AutoconfigureTag('oasis.parapheur')]
abstract class AbstractParapheur
{
    abstract public function getProviderId(): string;

    /** Faux pour un parapheur non configuré : les décisions restent envoyées par e-mail. */
    public function estDisponible(): bool
    {
        return true;
    }

    /**
     * @param string $libelle libellé affiché aux signataires
     * @param ?string $destinataire adresse à laquelle le parapheur transmet le document signé ; null quand
     *                              OASIS l'envoie lui-même (PARAPHEUR_ENVOIE_DOCUMENT)
     *
     * @return string identifiant du document dans le parapheur
     *
     * @throws ParapheurException
     */
    abstract public function deposer(string $pdf, string $circuit, string $libelle, ?string $destinataire): string;

    /**
     * @throws DocumentInconnuException si le parapheur ne connaît pas le document
     * @throws ParapheurException
     */
    abstract public function suivre(string $documentId): SuiviSignature;

    /**
     * @return string contenu du PDF signé
     *
     * @throws ParapheurException
     */
    abstract public function telecharger(string $documentId): string;
}
