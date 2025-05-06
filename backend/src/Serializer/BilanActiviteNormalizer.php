<?php

/*
 * Copyright (c) 2024. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\Serializer;

use App\ApiResource\Amenagement;
use App\ApiResource\BeneficiaireProfil;
use App\ApiResource\TypeAmenagement;
use App\Entity\ProfilBeneficiaire;
use App\MessageHandler\BilanActivite;
use App\MessageHandler\UtilisateurBilanActivite;
use ArrayObject;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class BilanActiviteNormalizer implements NormalizerInterface
{

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
    {
        if ($format === 'customcsv') {
            return $this->toArray($data);
        }
        return [$data];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!$data instanceof BilanActivite || !in_array($format, ['customcsv'])) {
            return false;
        }

        return true;
    }

    public function getSupportedTypes(?string $format): array
    {
        if (!in_array($format, ['customcsv'])) {
            return [];
        }

        return [BilanActivite::class => false];
    }

    /**
     * @param BilanActivite $bilanActivite
     * @return array
     */
    private function toArray(BilanActivite $bilanActivite): array
    {
        $types = $this->types($bilanActivite);

        $typesPedagogiques = array_filter($types, fn($type) => $type->pedagogique);
        $typesAideHumaine = array_filter($types, fn($type) => $type->aideHumaine);
        $typesExamens = array_filter($types, fn($type) => $type->examens);

        $entetes = $this->entetes($typesPedagogiques, $typesAideHumaine, $typesExamens);
        $lines[] = $entetes[0];//codes
        $lines[] = $entetes[1];//libellés

        /**
         * Données
         */
        foreach ($bilanActivite->beneficiaires as $beneficiaire) {
            [$pedagogiques, $aideHumaine, $examens] = $this->idsTypesAmenagementsParCategorie($beneficiaire->amenagements);

            foreach ($beneficiaire->profils as $profil) {
                $lines[] = $this->ligneProfil($beneficiaire, $profil, $pedagogiques, $typesPedagogiques, $aideHumaine,
                    $typesAideHumaine, $examens, $typesExamens);
            }

        }

        return $lines;
    }

    protected function idsTypesAmenagementsParCategorie(array $amenagements): array
    {
        $pedagogiques = array_map(
            fn(Amenagement $amenagement) => $amenagement->typeAmenagement->id,
            array_filter($amenagements,
                fn($amenagement) => $amenagement->typeAmenagement->pedagogique)
        );
        $aideHumaine = array_map(
            fn(Amenagement $amenagement) => $amenagement->typeAmenagement->id,
            array_filter($amenagements,
                fn($amenagement) => $amenagement->typeAmenagement->aideHumaine)
        );
        $examens = array_map(
            fn(Amenagement $amenagement) => $amenagement->typeAmenagement->id,
            array_filter($amenagements,
                fn($amenagement) => $amenagement->typeAmenagement->examens)
        );

        return [$pedagogiques, $aideHumaine, $examens];
    }

    /**
     * @param UtilisateurBilanActivite $beneficiaire
     * @param BeneficiaireProfil $profil
     * @param array $pedagogiques
     * @param array $typesPedagogiques
     * @param array $aideHumaine
     * @param array $typesAideHumaine
     * @param array $examens
     * @param array $typesExamens
     * @return array
     */
    protected function ligneProfil(UtilisateurBilanActivite $beneficiaire, BeneficiaireProfil $profil, array $pedagogiques,
                                   array                    $typesPedagogiques, array $aideHumaine, array $typesAideHumaine,
                                   array                    $examens, array $typesExamens): array
    {
        $ligne = [
            $beneficiaire->numero,
            $beneficiaire->gestionnaire->nom . ' ' . $beneficiaire->gestionnaire->prenom,
            $profil->profil->libelle,
            $beneficiaire->anneeNaissance,
            $beneficiaire->sexe,
            $beneficiaire->derniereInscription?->formation->composante->libelle,
            $beneficiaire->regimeInscription,
            '', //apprentissage/contrat pro
            '', //modfrmn
            $beneficiaire->derniereInscription?->formation->niveau,
            $beneficiaire->derniereInscription?->formation->diplome,
            $beneficiaire->derniereInscription?->formation->discipline,
            match (count($profil->typologies)) {
                0 => '',
                1 => $profil->typologies[0]->libelle,
                default => implode(' - ', array_map(fn($typo) => $typo->libelle, $profil->typologies))
            },
            match ($profil->profil->id) {
                ProfilBeneficiaire::HANDICAP_PERMANENT, ProfilBeneficiaire::INCAPACITE_TEMPORAIRE => 'oui',
                default => 'non'
            },
            '', //com
            '', //codpfpp
            match (count($pedagogiques)) {
                0 => 'non',
                default => 'oui'
            },
            '',
        ];
        //aménagements pédago dans un ordre fixe
        foreach ($typesPedagogiques as $type) {
            $ligne[] = in_array($type->id, $pedagogiques) ? 'oui' : 'non';
        }
        $ligne[] = match (count($aideHumaine)) {
            0 => 'non',
            default => 'oui'
        };
        //aménagements aide humaine dans un ordre fixe
        foreach ($typesAideHumaine as $type) {
            $ligne[] = in_array($type->id, $aideHumaine) ? 'oui' : 'non';
        }
        $ligne[] = '';//aidhnat
        $ligne[] = match (count($examens)) {
            0 => 'non',
            default => 'oui'
        };
        //aménagements examens dans un ordre fixe
        foreach ($typesExamens as $type) {
            $ligne[] = in_array($type->id, $examens) ? 'oui' : 'non';
        }

        /**
         * Le reste est non renseignable : autae, session différée, niveau de suivi, autres....
         */
        return [...$ligne, '', '', '', $beneficiaire->nbEntretiens];
    }

    /**
     * @param array $typesPedagogiques
     * @param array $typesAideHumaine
     * @param array $typesExamens
     * @return array
     */
    protected function entetes(array $typesPedagogiques, array $typesAideHumaine, array $typesExamens): array
    {
        /**
         * Ligne d'entête
         */
        $entete = ['numetu', 'CAS', 'profil', 'an', 'sexe', 'composante', 'typfrmn', 'apprentissage/contrat pro',
            'modfrmn', 'codsco', 'codfmt', 'codfil', 'codhd', 'hdtmp', 'com', 'codpfpp', 'codpfas', 'amenagement EDT'];
        $enteteLibelles = ['numéro anonymat', "chargé d'accompagnement", 'profil', 'Année de naissance', 'sexe', 'composante', 'Type de formation', 'apprentissage/contrat pro',
            'format formation', "Année d'étude cursus", 'Diplôme présenté', 'Discipline', 'Typologie de handicap', 'Profil permanent/temporaire',
            'commentaire libre', "niveau de formalisation du plan d'accompagnement", 'aménagements pédagogiques?', 'amenagement EDT'];
        //ajout items types pédago
        foreach ($typesPedagogiques as $type) {
            $entete[] = $type->libelle;
        }
        $entete[] = 'codmeahF';
        $enteteLibelles[] = 'Aménagement avec aide humaine?';
        foreach ($typesAideHumaine as $type) {
            $entete[] = $type->libelle;
        }
        $entete[] = 'aidhnat';
        $enteteLibelles[] = 'Autre aide humaine?';
        $entete[] = 'codmeae';
        $enteteLibelles[] = "Aménagement d'examen?";
        foreach ($typesExamens as $type) {
            $entete[] = $type->libelle;
        }
        return [
            [...$entete, 'précisions modalités évaluation', 'autae', 'session différée', 'codmeaa',
                'autaa', 'codamL', 'précisions accompagnement autre'],
            [...$enteteLibelles, 'précisions modalités évaluation', "Autre aménagement d'examen?", 'session différée', 'niveau de suivi',
                'autre aide?', 'informations sur les accompagnements hors Université', 'précisions accompagnement autre'],
        ];
    }

    /**
     * @param BilanActivite $bilanActivite
     * @return array
     */
    protected function types(BilanActivite $bilanActivite): array
    {
        /**
         * On a besoin de commencer par lister les colonnes...il faut aller récupérer tous les types d'aménagements utilisés
         */
        $types = [];
        foreach ($bilanActivite->beneficiaires as $utilisateurBilanActivite) {
            foreach ($utilisateurBilanActivite->amenagements as $amenagement) {
                $types[$amenagement->typeAmenagement->id] = $amenagement->typeAmenagement;
            }
        }

        /**
         * On trie :
         *  - d'abord les pédagogiques, puis les aides humaines, puis les examens
         *  - par libellé dans chaque catégorie
         */
        $types = array_values($types);
        usort($types,
            fn(TypeAmenagement $a, TypeAmenagement $b) => match (true) {
                $a->pedagogique !== $b->pedagogique => $b->pedagogique <=> $a->pedagogique,
                $a->aideHumaine !== $b->aideHumaine => $b->aideHumaine <=> $a->aideHumaine,
                default => $a->libelle <=> $b->libelle
            }
        );
        return $types;
    }
}