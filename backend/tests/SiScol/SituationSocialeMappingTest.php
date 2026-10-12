<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Tests\SiScol;

use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

/**
 * Accesseurs de la situation sociale sur l'entité Utilisateur.
 *
 * Ce test couvre uniquement le contrat de l'entité (valeur par défaut, get/set,
 * accessors fluents) sur les deux champs ajoutés codeSituationSociale et
 * libelleSituationSociale.
 *
 * La règle de projection — recopie depuis l'inscription la plus récente — est testée
 * sur le code de production dans {@see \App\Tests\UtilisateurManagerTest}, et n'est pas
 * répliquée ici pour éviter un test tautologique.
 *
 * Le témoin boursier reste alimenté par le seul indicateur du SI scolarité : la
 * situation sociale est exposée comme information, elle n'en tire aucune conclusion.
 */
final class SituationSocialeMappingTest extends TestCase
{
    public function testSituationSocialeEstNulleParDefaut(): void
    {
        $utilisateur = new Utilisateur();

        self::assertNull($utilisateur->getCodeSituationSociale());
        self::assertNull($utilisateur->getLibelleSituationSociale());
    }

    public function testGetSetSituationSocialeConserventLesValeurs(): void
    {
        $utilisateur = new Utilisateur();

        $utilisateur->setCodeSituationSociale('BO');
        $utilisateur->setLibelleSituationSociale('Boursier');

        self::assertSame('BO', $utilisateur->getCodeSituationSociale());
        self::assertSame('Boursier', $utilisateur->getLibelleSituationSociale());
    }

    public function testSituationSocialeAccepteNull(): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setCodeSituationSociale('PU');
        $utilisateur->setLibelleSituationSociale('Pupille de la nation');

        $utilisateur->setCodeSituationSociale(null);
        $utilisateur->setLibelleSituationSociale(null);

        self::assertNull($utilisateur->getCodeSituationSociale());
        self::assertNull($utilisateur->getLibelleSituationSociale());
    }

    public function testAccessorsSituationSocialeSontFluents(): void
    {
        $utilisateur = new Utilisateur();

        self::assertSame($utilisateur, $utilisateur->setCodeSituationSociale('PU'));
        self::assertSame($utilisateur, $utilisateur->setLibelleSituationSociale('Pupille de la nation'));
    }
}
