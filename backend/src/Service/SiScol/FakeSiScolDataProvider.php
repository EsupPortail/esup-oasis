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

namespace App\Service\SiScol;

use App\Entity\Formation;
use App\Entity\Utilisateur;
use DateTime;
use DateTimeInterface;

class FakeSiScolDataProvider extends AbstractSiScolDataProvider
{

    /**
     * @inheritDoc
     */
    public function getProviderId(): string
    {
        return "fake";
    }
    
    /**
     * Situation sociale simulée renvoyée par le mock, surchargeable depuis les
     * tests pour exercer les différentes branches de la projection réelle
     * (UtilisateurManager) sans dépendre d'Apogée. Valeurs par défaut : étudiant
     * non boursier ("NO"/"Normal").
     */
    public static bool $boursier = false;
    public static ?string $codeSituationSociale = 'NO';
    public static ?string $libelleSituationSociale = 'Normal';

    /**
     * Adresse simulée, surchargeable depuis les tests pour exercer la
     * projection de l'adresse Apogée vers Utilisateur::adresse.
     * Valeurs par défaut : pas d'adresse connue.
     */
    public static ?string $adresseLigne1 = null;
    public static ?string $adresseLigne2 = null;
    public static ?string $adresseComplement = null;
    public static ?string $adresseCodePostal = null;
    public static ?string $adresseVille = null;
    public static ?string $adressePays = null;

    /**
     * @inheritDoc
     */
    public function getInscriptions(Utilisateur $etudiant, DateTimeInterface $debut, ?DateTimeInterface $fin): array
    {
        // Simple mock returning something if numero etudiant is set
        if (!$etudiant->getNumeroEtudiant()) {
            return [];
        }

        // Simulating some side effects from ApogeeProvider if not already set
        if (!$etudiant->getTelPerso()) {
            $etudiant->setTelPerso('0102030405');
        }
        if (!$etudiant->getDateNaissance()) {
            $etudiant->setDateNaissance(new DateTime('2000-01-01'));
        }
        if (!$etudiant->getGenre()) {
            $etudiant->setGenre('M');
        }

        return [
            [
                'codeFormation' => 'FOR_01#1',
                'libFormation' => 'Formation de test',
                'codeComposante' => 'COMP_01',
                'libComposante' => 'Composante de test',
                'debut' => new DateTime($debut->format('Y') . '-09-01'),
                'fin' => new DateTime(((int) $debut->format('Y') + 1) . '-08-31'),
                'boursier' => self::$boursier,
                'codeSituationSociale' => self::$codeSituationSociale,
                'libelleSituationSociale' => self::$libelleSituationSociale,
                'adresseLigne1' => self::$adresseLigne1,
                'adresseLigne2' => self::$adresseLigne2,
                'adresseComplement' => self::$adresseComplement,
                'adresseCodePostal' => self::$adresseCodePostal,
                'adresseVille' => self::$adresseVille,
                'adressePays' => self::$adressePays,
                'statut' => 'FI',
                'niveau' => 'L1',
                'discipline' => 'Informatique',
                'diplome' => 'Licence',
            ],
        ];
    }

    public function getFormation(Formation $incomplete): array
    {
        return [
            'diplome' => 'Diplôme de test',
            'niveau' => 'Niveau de test',
            'discipline' => 'Discipline de test',
        ];
    }
}
