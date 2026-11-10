<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\Signature\Fast;

use App\Service\Signature\AbstractParapheur;
use App\Service\Signature\SuiviSignature;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * FAST-Parapheur (Docaposte) : dépôt standard dans un circuit, état déduit de l'historique.
 * PARAPHEUR=fast, réglages FAST_* dans .env.
 */
class FastParapheur extends AbstractParapheur
{
    use ClockAwareTrait;

    public const string ID = 'fast';

    public function __construct(
        private readonly ClientFast $client,
        private readonly EtatSignatureDeriver $deriver,
    ) {}

    public function getProviderId(): string
    {
        return self::ID;
    }

    public function deposer(string $pdf, string $circuit, string $libelle, ?string $destinataire): string
    {
        // affiché aux signataires ; daté pour distinguer les versions successives d'une décision
        $nomFichier = sprintf('%s-%s.pdf', new AsciiSlugger('fr')->slug($libelle)->lower(), $this->now()->format('Ymd-His'));

        return $this->client->deposer($pdf, $nomFichier, $circuit, $libelle, $destinataire);
    }

    public function suivre(string $documentId): SuiviSignature
    {
        $historique = $this->client->historique($documentId);

        return new SuiviSignature(
            $this->deriver->deriver(array_column($historique, 'stateName')),
            $this->deriver->dateDeSignature($historique),
        );
    }

    public function telecharger(string $documentId): string
    {
        return $this->client->telecharger($documentId);
    }
}
