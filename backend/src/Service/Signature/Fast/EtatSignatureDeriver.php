<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

namespace App\Service\Signature\Fast;

use App\Entity\DecisionAmenagementExamens;
use DateTimeImmutable;
use Exception;
use Normalizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Dérive l'état de signature de l'historique FAST, qui n'expose pas d'état courant.
 * Libellés comparés sans accents ni casse, libellés inconnus ignorés. Le circuit est terminé sur un état
 * de fin, choisi par l'établissement (FAST_ETATS_FIN), tant qu'aucune nouvelle étape n'a été envoyée après.
 */
class EtatSignatureDeriver
{
    // tous les circuits ne classent ni n'archivent le document : la signature suffit par défaut
    public const string ETATS_FIN_PAR_DEFAUT = 'Signé, Classé, Archivé';

    /** @var list<string> libellés normalisés des états qui terminent un circuit */
    private array $etatsFin;

    /**
     * @param string|null $etatsFin états de l'historique qui terminent un circuit, séparés par des virgules ;
     *                              vide : ETATS_FIN_PAR_DEFAUT. Un établissement qui classe ou archive ses
     *                              documents peut n'attendre que cet état.
     */
    public function __construct(
        #[Autowire('%env(default::FAST_ETATS_FIN)%')]
        ?string $etatsFin = null,
    ) {
        $etats = array_filter(array_map($this->normaliser(...), explode(',', (string) $etatsFin)));
        if ([] === $etats) {
            $etats = array_map($this->normaliser(...), explode(',', self::ETATS_FIN_PAR_DEFAUT));
        }
        $this->etatsFin = array_values($etats);
    }

    // terminaux négatifs ; le refus peut porter un suffixe d'étape (« Refusé à l'étape OTP »)
    private const string PREFIXE_REFUS = 'refus';
    private const string SIGNATURE_REJETEE = 'signature rejetee';
    private const string VISA_DESAPPROUVE = 'visa desapprouve';
    private const string CLASSE_INTERROMPU = 'classe (interrompu)';
    private const string DOCUMENT_REMPLACE = 'document remplace';
    private const string ECHEC_ENVOI = "echec de l'envoi a fast";
    private const string ECHEC_TRAITEMENT = 'echec du traitement fast';

    private const string SIGNE = 'signe';
    // « Envoyé pour visa », « Envoyé pour signature » : une nouvelle étape relance le circuit
    private const string NOUVELLE_ETAPE = 'envoye pour';

    /**
     * Date de la dernière signature, à défaut celle de l'état de fin qui a terminé le circuit (circuit de visa seul).
     *
     * @param array<int, array{stateName: string, date: string}> $historique dans l'ordre chronologique
     */
    public function dateDeSignature(array $historique): ?DateTimeImmutable
    {
        $derniereSignature = null;
        $fin = null;

        foreach ($historique as $entree) {
            $normalise = $this->normaliser($entree['stateName'] ?? '');
            if ($this->estSignature($normalise)) {
                $derniereSignature = $entree['date'] ?? null;
            }
            if ($this->estEtatFin($normalise)) {
                $fin ??= $entree['date'] ?? null;
            } elseif (str_starts_with($normalise, self::NOUVELLE_ETAPE)) {
                $fin = null;
            }
        }

        return $this->lireDate($derniereSignature ?? $fin);
    }

    /**
     * @param string[] $libelles les `stateName` de l'historique FAST, dans l'ordre chronologique
     *
     * @return string une constante DecisionAmenagementExamens::ETAT_SIGNATURE_*
     */
    public function deriver(array $libelles): string
    {
        $erreur = false;
        $termine = false;

        foreach ($libelles as $libelle) {
            $normalise = $this->normaliser($libelle);

            // Les issues négatives priment sur toute progression antérieure.
            if (str_starts_with($normalise, self::PREFIXE_REFUS)
                || self::SIGNATURE_REJETEE === $normalise
                || self::VISA_DESAPPROUVE === $normalise) {
                return DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE;
            }

            if (self::CLASSE_INTERROMPU === $normalise) {
                return DecisionAmenagementExamens::ETAT_SIGNATURE_EXPIREE;
            }

            if (self::DOCUMENT_REMPLACE === $normalise) {
                return DecisionAmenagementExamens::ETAT_SIGNATURE_REMPLACEE;
            }

            if (self::ECHEC_ENVOI === $normalise || self::ECHEC_TRAITEMENT === $normalise) {
                $erreur = true;
            }

            // Un circuit à plusieurs signatures écrit « Signé » à chacune, puis envoie l'étape suivante : lu entre
            // les deux, il serait tenu pour terminé ; FAST les inscrivant dans la même seconde, le cas n'a pas été
            // observé. Le classement ou l'archivage, choisis comme état de fin, lèvent ce doute.
            if ($this->estEtatFin($normalise)) {
                $termine = true;
            } elseif (str_starts_with($normalise, self::NOUVELLE_ETAPE)) {
                $termine = false;
            }
        }

        if ($termine) {
            return DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE;
        }

        return match ($erreur) {
            true => DecisionAmenagementExamens::ETAT_SIGNATURE_ERREUR,
            false => DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE,
        };
    }

    // l'état peut porter un suffixe (« Signé à l'étape 2 ») ; « Signature rejetée » ne compte pas
    private function estEtatFin(string $normalise): bool
    {
        return array_any(
            $this->etatsFin,
            fn(string $etat) => $etat === $normalise || str_starts_with($normalise, $etat . ' '),
        );
    }

    private function estSignature(string $normalise): bool
    {
        return self::SIGNE === $normalise || str_starts_with($normalise, self::SIGNE . ' ');
    }

    private function normaliser(string $libelle): string
    {
        $epure = trim($libelle);
        $decompose = Normalizer::normalize($epure, Normalizer::FORM_D);

        if (false === $decompose) {
            return strtolower($epure);
        }

        return strtolower(preg_replace('/\p{Mn}+/u', '', $decompose) ?? $epure);
    }

    /**
     * Date d'une entrée d'historique ; null si elle est absente ou illisible, plutôt
     * que d'inventer une date de signature.
     */
    private function lireDate(?string $date): ?DateTimeImmutable
    {
        if (null === $date || '' === trim($date)) {
            return null;
        }

        try {
            return new DateTimeImmutable($date);
        } catch (Exception) {
            return null;
        }
    }
}
