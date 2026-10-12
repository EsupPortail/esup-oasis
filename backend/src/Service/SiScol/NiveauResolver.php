<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\SiScol;

/**
 * Résout le niveau d'études normalisé (L1/L2/L3/M1/M2/D1-D3…) à partir de
 * données Apogée nationales (donc valable multi-établissements) :
 *   niveau (bac+N) = niveau d'entrée du cycle du diplôme + année dans le diplôme.
 *
 * - année dans le diplôme = COD_SIS_DAA (codage SISE national, par étape),
 * - cycle = cycle du diplôme (1 = Licence/bac+0, 2 = Master/bac+3, 3 = Doctorat/bac+5).
 *
 * Piloté par configuration (OCP + DIP) : les barèmes (entrée par cycle, libellé
 * par niveau) et les surcharges par type de diplôme sont injectés, donc
 * adaptables sans toucher à l'algorithme. Les cas particuliers (santé, DUT/BUT,
 * ingénieur, PASS/LAS) se traitent en ajoutant une surcharge de configuration.
 * Tout cas non couvert renvoie null (niveau non applicable).
 */
class NiveauResolver
{
    /**
     * Types de diplôme (Apogée cod_tpd_etb, codes établissement) donnant lieu à un
     * niveau L/M/D nominal (entrée du cycle + année dans le diplôme). Établissement-
     * spécifique, donc surchargeable via le constructeur.
     *   Licence : 86 (Licence LMD), 93 (Licence Double Diplôme LMD).
     *   Master  : 37 (MASTER), 39 (Master Enseignement / MEEF).
     *   CPES    : 19 (Cycle Pluridisciplinaire d'Études Supérieures) — cycle 1,
     *             assimilé Licence (année 1/2/3 => L1/L2/L3) par décision client.
     * Liste validée sur données Apogée réelles (28/08/2026). Les grades de licence/
     * master de santé (22, 38) portent tem_sante = 'O' et sont donc écartés en amont
     * par la branche santé. Aucun type de doctorat « recherche » n'existe dans les
     * inscriptions Apogée UPSaclay (cycle 3 = santé, HDR, DU) : le niveau D n'est pas
     * sourçable ici (doctorants gérés hors Apogée). DU (72–79), échange entrant (21)
     * et LAS/PADHUE (03, « Sans diplôme ») rendent le niveau vide (décision client).
     */
    public const array TYPES_LMD_UPSACLAY = ['86', '93', '37', '39', '19'];

    /**
     * Types de diplôme non-LMD dont le niveau s'affiche avec un libellé propre =
     * préfixe + (année dans le diplôme + offset). Codes établissement UPSaclay,
     * décision client du 28/08/2026, validés sur données Apogée réelles (année dans
     * le diplôme = cod_sis_daa, aucune valeur nulle observée sur ces types) :
     *   16 BUT         => BUT1/BUT2/BUT3  (offset 0)
     *   13 DEUST       => DEUST1/DEUST2   (offset 0)
     *   18 LP en 3 ans => LP1/LP2/LP3     (offset 0)
     *   85 LP en 1 an  => LP1             (offset 0, année toujours 1)
     *   34 Ingénieur   => ING3/ING4/ING5  (offset 2 : étapes réelles P3/P4/P5)
     *
     * @var array<string, array{0: string, 1: int}> code type => [préfixe, offset]
     */
    public const array PREFIXE_PAR_TYPE_UPSACLAY = [
        '16' => ['BUT', 0],
        '13' => ['DEUST', 0],
        '18' => ['LP', 0],
        '85' => ['LP', 0],
        '34' => ['ING', 2],
    ];

    /**
     * @param array<int,int>         $entreeParCycle    cycle Apogée => niveau d'entrée (bac+N de départ)
     * @param array<int,string>      $libelleParNiveau  bac+N => libellé normalisé (1 => 'L1' … 8 => 'D3')
     * @param array<string,?string>  $surchargeParType  code type de diplôme => libellé forcé (ou null pour vide)
     * @param list<string>           $typesLmd          types de diplôme LMD ; hors de cette liste,
     *                                                  niveau non applicable ([] = pas de filtrage)
     * @param array<string,array{0:string,1:int}> $prefixeParType  code type non-LMD =>
     *                                                  [préfixe, offset] ; libellé = préfixe + (année + offset)
     * @param bool                   $typeDiplomeObligatoire  si true, un type de diplôme manquant
     *                                                  n'autorise pas le repli nominal : le type absent
     *                                                  signale une inscription non synchronisée, pas un
     *                                                  diplôme LMD. false = repli nominal conservé.
     */
    public function __construct(
        private readonly array $entreeParCycle = [1 => 0, 2 => 3, 3 => 5],
        private readonly array $libelleParNiveau = [
            1 => 'L1', 2 => 'L2', 3 => 'L3', 4 => 'M1', 5 => 'M2', 6 => 'D1', 7 => 'D2', 8 => 'D3',
        ],
        private readonly array $surchargeParType = [],
        private readonly array $typesLmd = self::TYPES_LMD_UPSACLAY,
        private readonly array $prefixeParType = self::PREFIXE_PAR_TYPE_UPSACLAY,
        private readonly bool $typeDiplomeObligatoire = false,
    ) {
    }

    /**
     * @param int|null    $cycle            cycle du diplôme (Apogée cod_cyc)
     * @param int|null    $anneeDansDiplome année dans le diplôme (Apogée cod_sis_daa)
     * @param string|null $codeTypeDiplome  type de diplôme (cod_tpd_etb) : famille et surcharges
     * @param bool        $sante            formation de santé (Apogée tem_sante = 'O') : niveau non applicable
     */
    public function resolve(
        ?int $cycle,
        ?int $anneeDansDiplome,
        ?string $codeTypeDiplome = null,
        bool $sante = false,
    ): ?string {
        // 1. Surcharge explicite par type (config) : priorité absolue.
        if ($codeTypeDiplome !== null && array_key_exists($codeTypeDiplome, $this->surchargeParType)) {
            return $this->surchargeParType[$codeTypeDiplome];
        }

        // 2. Santé (PASS/LAS/MED, tem_sante = 'O') : niveau L/M/D non applicable.
        if ($sante) {
            return null;
        }

        // 3. Libellé propre par type non-LMD (BUT, DEUST, licence pro, ingénieur) :
        //    préfixe + (année dans le diplôme + offset). Ex. BUT année 2 => BUT2,
        //    ingénieur année 1 => ING3 (offset 2).
        if ($codeTypeDiplome !== null && isset($this->prefixeParType[$codeTypeDiplome])) {
            if ($anneeDansDiplome === null || $anneeDansDiplome < 1) {
                return null;
            }
            [$prefixe, $offset] = $this->prefixeParType[$codeTypeDiplome];

            return $prefixe.($anneeDansDiplome + $offset);
        }

        // 4. Hors périmètre LMD : type de diplôme connu mais absent de l'allowlist
        //    LMD (DU, échange entrant, LAS…) => niveau non applicable en l'état.
        if ($codeTypeDiplome !== null && $this->typesLmd !== []
            && !in_array($codeTypeDiplome, $this->typesLmd, true)) {
            return null;
        }

        // 5. Type obligatoire : sans type de diplôme identifié, pas de repli
        //    nominal LMD. Sur un établissement qui remonte toujours le type, un
        //    type null signale une inscription non synchronisée, pas un LMD.
        //    Par défaut (false), le type manquant retombe sur le nominal (rétro-
        //    compatibilité : établissements qui ne remontent pas le type).
        if ($codeTypeDiplome === null && $this->typeDiplomeObligatoire) {
            return null;
        }

        // 6. Cas nominal LMD : entrée(cycle) + année dans le diplôme.
        if ($cycle === null || $anneeDansDiplome === null || $anneeDansDiplome < 1) {
            return null;
        }
        if (!array_key_exists($cycle, $this->entreeParCycle)) {
            return null;
        }

        $bacPlusN = $this->entreeParCycle[$cycle] + $anneeDansDiplome;

        // 7. Fallback : null (niveau vide) si hors barème.
        return $this->libelleParNiveau[$bacPlusN] ?? null;
    }
}
