<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests\Service\SiScol;

use App\Service\SiScol\NiveauResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NiveauResolverTest extends TestCase
{
    private NiveauResolver $resolver;

    protected function setUp(): void
    {
        // Barèmes par défaut (SISE national) : cycle 1=Licence(bac+0), 2=Master(bac+3), 3=Doctorat(bac+5).
        $this->resolver = new NiveauResolver();
    }

    /**
     * @return array<string, array{?int, ?int, ?string, ?string}>
     */
    public static function casNominaux(): array
    {
        return [
            // libellé attendu, cycle, année dans le diplôme
            'Licence année 1 => L1' => ['L1', 1, 1],
            'Licence année 2 => L2' => ['L2', 1, 2],
            'Licence année 3 => L3' => ['L3', 1, 3],
            'Master année 1 => M1' => ['M1', 2, 1],
            'Master année 2 => M2' => ['M2', 2, 2],
            'Doctorat année 1 => D1' => ['D1', 3, 1],
            'Doctorat année 3 => D3' => ['D3', 3, 3],
        ];
    }

    #[DataProvider('casNominaux')]
    public function testCasNominauxLmd(?string $attendu, ?int $cycle, ?int $annee): void
    {
        self::assertSame($attendu, $this->resolver->resolve($cycle, $annee));
    }

    /**
     * @return array<string, array{?int, ?int}>
     */
    public static function casVides(): array
    {
        return [
            'cycle inconnu' => [null, 2],
            'année inconnue' => [2, null],
            'année nulle (0)' => [2, 0],
            'cycle hors barème' => [9, 1],
            'hors barème (bac+9)' => [3, 4], // 5 + 4 = 9, pas de libellé => vide
        ];
    }

    #[DataProvider('casVides')]
    public function testCasNonCouvertsRenvoientVide(?int $cycle, ?int $annee): void
    {
        self::assertNull($this->resolver->resolve($cycle, $annee));
    }

    public function testSurchargeParTypeDiplomePrioritaire(): void
    {
        // Cas particulier piloté en config : un type "santé" forcé à un libellé,
        // et un autre forcé à vide (null), sans toucher à l'algorithme.
        $resolver = new NiveauResolver(
            surchargeParType: ['43' => 'Pharmacie 4', '99' => null],
        );

        // La surcharge prime même si le calcul nominal donnerait autre chose.
        self::assertSame('Pharmacie 4', $resolver->resolve(2, 4, '43'));
        self::assertNull($resolver->resolve(1, 2, '99'));
        // Un type sans surcharge retombe sur le calcul nominal.
        self::assertSame('M1', $resolver->resolve(2, 1, '37'));
    }

    public function testBaremesInjectablesRestentAdaptables(): void
    {
        // On peut fournir des barèmes différents (autre établissement / autre nomenclature).
        $resolver = new NiveauResolver(
            entreeParCycle: [1 => 0, 2 => 3],
            libelleParNiveau: [1 => 'Année 1', 4 => 'Année 4'],
        );
        self::assertSame('Année 1', $resolver->resolve(1, 1));
        self::assertSame('Année 4', $resolver->resolve(2, 1));
        self::assertNull($resolver->resolve(1, 2)); // pas de libellé pour bac+2 ici
    }

    public function testTypeLmdConnuDonneUnNiveau(): void
    {
        // Type de diplôme dans l'allowlist LMD (Licence 86) : niveau nominal.
        self::assertSame('L1', $this->resolver->resolve(1, 1, '86'));
        self::assertSame('M1', $this->resolver->resolve(2, 1, '37'));
    }

    public function testTypeHorsLmdRendNiveauNonApplicable(): void
    {
        // Type connu mais hors LMD et sans libellé propre (DU '72', échange '21') :
        // niveau vide, même si le cycle + l'année donneraient nominalement un niveau.
        self::assertNull($this->resolver->resolve(1, 1, '72'));
        self::assertNull($this->resolver->resolve(1, 1, '21'));
    }

    public function testCpesAssimileLicence(): void
    {
        // CPES (type '19', cycle 1) : dans l'allowlist LMD => niveau Licence nominal.
        self::assertSame('L1', $this->resolver->resolve(1, 1, '19'));
        self::assertSame('L2', $this->resolver->resolve(1, 2, '19'));
        self::assertSame('L3', $this->resolver->resolve(1, 3, '19'));
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function casPrefixesParType(): array
    {
        return [
            // libellé attendu, code type, année dans le diplôme
            'BUT année 1 => BUT1' => ['BUT1', '16', 1],
            'BUT année 3 => BUT3' => ['BUT3', '16', 3],
            'DEUST année 2 => DEUST2' => ['DEUST2', '13', 2],
            'LP 3 ans année 2 => LP2' => ['LP2', '18', 2],
            'LP 1 an année 1 => LP1' => ['LP1', '85', 1],
            'Ingénieur année 1 => ING3' => ['ING3', '34', 1],
            'Ingénieur année 3 => ING5' => ['ING5', '34', 3],
        ];
    }

    #[DataProvider('casPrefixesParType')]
    public function testLibellePropreParTypeNonLmd(string $attendu, string $type, int $annee): void
    {
        // Le cycle n'entre pas dans le libellé préfixé : seul l'année + offset compte.
        self::assertSame($attendu, $this->resolver->resolve(4, $annee, $type));
    }

    public function testTypePrefixeSansAnneeRendVide(): void
    {
        // Type à libellé propre mais année dans le diplôme manquante ou nulle => vide.
        self::assertNull($this->resolver->resolve(1, null, '16'));
        self::assertNull($this->resolver->resolve(1, 0, '16'));
    }

    public function testSantePrimeSurLibellePropre(): void
    {
        // Un type à libellé propre marqué santé (tem_sante = 'O') reste vide.
        self::assertNull($this->resolver->resolve(1, 1, '16', true));
    }

    public function testSanteRendNiveauNonApplicable(): void
    {
        // Formation de santé (tem_sante = 'O') : niveau vide même pour un cycle LMD.
        self::assertNull($this->resolver->resolve(1, 1, '86', true));
        self::assertNull($this->resolver->resolve(1, 1, null, true));
    }

    public function testTypeManquantRetombeSurLeCalculNominal(): void
    {
        // Sans type de diplôme, le filtre par famille ne s'applique pas (repli nominal).
        self::assertSame('L1', $this->resolver->resolve(1, 1));
    }

    public function testAllowlistVideDesactiveLeFiltrageParType(): void
    {
        // typesLmd = [] : pas de filtrage par famille, tout type retombe sur le nominal.
        // prefixeParType = [] pour isoler l'allowlist (sinon '16' donnerait BUT1).
        $resolver = new NiveauResolver(typesLmd: [], prefixeParType: []);
        self::assertSame('L1', $resolver->resolve(1, 1, '16'));
    }

    public function testTypeObligatoireDesactiveLeRepliNominalSansType(): void
    {
        // typeDiplomeObligatoire = true : sans type de diplôme identifié, pas de
        // repli nominal LMD (un cycle + année seuls ne suffisent plus). Utilisé
        // par les établissements qui remontent toujours le type (ex. UPSaclay) :
        // un type null y signale une inscription non synchronisée, pas un LMD.
        $resolver = new NiveauResolver(typeDiplomeObligatoire: true);
        self::assertNull($resolver->resolve(1, 1, null));
        // Un type LMD connu reste résolu normalement.
        self::assertSame('L1', $resolver->resolve(1, 1, '86'));
    }

    public function testTypeFacultatifGardeLeRepliNominalParDefaut(): void
    {
        // Par défaut (typeDiplomeObligatoire = false, comportement Bordeaux) :
        // sans type, le calcul nominal s'applique (rétro-compatibilité).
        self::assertSame('L1', $this->resolver->resolve(1, 1, null));
    }
}
