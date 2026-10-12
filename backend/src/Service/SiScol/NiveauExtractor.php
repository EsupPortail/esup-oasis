<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\SiScol;

/**
 * Extraction du niveau d'études (L1/L2/L3/M1/M2/D1/D2/D3) à
 * partir du code étape Apogée. Le code Apogée encode généralement le
 * niveau en préfixe : L1INFO, L2MATHS, M1ARTS, M2POLI, D1PHYS, etc.
 *
 * Service isolé pour le tester unitairement et pour pouvoir injecter
 * une variante (LMD britannique, niveaux IUT/BUT) sans toucher au
 * pipeline Apogée. Les codes hors barème (PASS-MED, LAS, doctorats
 * sans numéro, codes locaux) renvoient null plutôt qu'une supposition.
 *
 * Positionné en post-traitement de Formation::niveau : ce dernier
 * porte le niveau brut Apogée (NIVEAU) du diplôme, tandis que ce
 * service dérive un niveau LMD normalisé à partir du code étape de
 * l'inscription elle-même.
 */
class NiveauExtractor
{
    private const string PATTERN = '/^(L[1-3]|M[1-2]|D[1-3])/i';

    public function extract(?string $codeEtape): ?string
    {
        if ($codeEtape === null || $codeEtape === '') {
            return null;
        }
        if (preg_match(self::PATTERN, $codeEtape, $matches) !== 1) {
            return null;
        }

        return strtoupper($matches[1]);
    }
}
