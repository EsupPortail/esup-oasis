<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests\Entity;

use App\Entity\Formation;
use App\Entity\Inscription;
use PHPUnit\Framework\TestCase;

final class InscriptionTest extends TestCase
{
    public function testGetNiveauPrefersNiveauFromFormation(): void
    {
        // Un SI scolarité qui renseigne le niveau de la formation garde exactement
        // son affichage : la dérivation ne s'applique pas, même quand cycle, année
        // et type de diplôme permettraient de conclure autrement.
        $formation = (new Formation())->setNiveau('Licence 1ère année');
        $inscription = (new Inscription())
            ->setFormation($formation)
            ->setCycle(2)
            ->setAnneeDansDiplome(2)
            ->setCodeTypeDiplome('37');

        self::assertSame('Licence 1ère année', $inscription->getNiveau());
    }

    public function testGetNiveauIsDerivedWhenFormationHasNone(): void
    {
        // Formation sans niveau (colonne vide ou SI qui ne la renseigne pas) :
        // la dérivation prend le relais plutôt que de laisser le champ vide.
        foreach ([null, ''] as $niveauFormation) {
            $inscription = (new Inscription())
                ->setFormation((new Formation())->setNiveau($niveauFormation))
                ->setCycle(2)
                ->setAnneeDansDiplome(1)
                ->setCodeTypeDiplome('37');

            self::assertSame('M1', $inscription->getNiveau());
        }
    }

    public function testGetNiveauDerivesLmdFromCycleAndYear(): void
    {
        // Le type de diplôme est obligatoire côté entité (UPSaclay) : un type LMD
        // (Licence 86, Master 37) donne le niveau nominal depuis cycle + année.
        self::assertSame('L1', (new Inscription())
            ->setCycle(1)->setAnneeDansDiplome(1)->setCodeTypeDiplome('86')
            ->getNiveau());
        self::assertSame('L2', (new Inscription())
            ->setCycle(1)->setAnneeDansDiplome(2)->setCodeTypeDiplome('86')
            ->getNiveau());
        self::assertSame('L3', (new Inscription())
            ->setCycle(1)->setAnneeDansDiplome(3)->setCodeTypeDiplome('86')
            ->getNiveau());
        self::assertSame('M1', (new Inscription())
            ->setCycle(2)->setAnneeDansDiplome(1)->setCodeTypeDiplome('37')
            ->getNiveau());
        self::assertSame('M2', (new Inscription())
            ->setCycle(2)->setAnneeDansDiplome(2)->setCodeTypeDiplome('37')
            ->getNiveau());
    }

    public function testGetNiveauIsNullWithoutDegreeType(): void
    {
        // Type de diplôme manquant (inscription non synchronisée) : pas de repli
        // nominal LMD, niveau vide — malgré cycle + année valides.
        self::assertNull((new Inscription())->setCycle(1)->setAnneeDansDiplome(1)->getNiveau());
    }

    public function testGetNiveauFallsBackToCodeEtapePrefix(): void
    {
        self::assertSame('M1', (new Inscription())->setCodeEtape('M1INFO')->getNiveau());
    }

    public function testGetNiveauIsNullWhenNotApplicable(): void
    {
        self::assertNull((new Inscription())->getNiveau());
        self::assertNull((new Inscription())->setCodeEtape('PASSMED')->getNiveau());
    }

    public function testGetNiveauResolvedForLmdDegreeType(): void
    {
        self::assertSame(
            'L1',
            (new Inscription())->setCycle(1)->setAnneeDansDiplome(1)->setCodeTypeDiplome('86')->getNiveau(),
        );
    }

    public function testGetNiveauIsNullForNonLmdDegreeType(): void
    {
        // DU/échange (type hors LMD sans libellé propre) : pas de niveau, malgré cycle + année.
        self::assertNull(
            (new Inscription())->setCycle(1)->setAnneeDansDiplome(1)->setCodeTypeDiplome('72')->getNiveau(),
        );
    }

    public function testGetNiveauUsesOwnLabelForNonLmdDegreeType(): void
    {
        // BUT/ingénieur : libellé propre dérivé du type + année (BUT2, ING3…).
        self::assertSame(
            'BUT2',
            (new Inscription())->setCycle(1)->setAnneeDansDiplome(2)->setCodeTypeDiplome('16')->getNiveau(),
        );
        self::assertSame(
            'ING3',
            (new Inscription())->setCycle(4)->setAnneeDansDiplome(1)->setCodeTypeDiplome('34')->getNiveau(),
        );
    }

    public function testGetNiveauIsNullForSante(): void
    {
        // Formation de santé (tem_sante = 'O') : niveau vide même pour un cycle LMD.
        self::assertNull(
            (new Inscription())->setCycle(1)->setAnneeDansDiplome(1)->setSante(true)->getNiveau(),
        );
    }
}
