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

namespace App\Util;

use App\Entity\Beneficiaire;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Symfony\Component\Clock\ClockAwareTrait;

trait AnneeUniversitaireAwareTrait
{
    use ClockAwareTrait;

    /**
     * @param DateTimeImmutable|null $jour
     * @return array<DateTimeInterface, DateTimeInterface>
     * @throws Exception
     */
    public function bornesAnneeDuJour(?DateTimeImmutable $jour = null): array
    {
        $anneeDebut = $this->anneeDuJour($jour);

        return [
            'debut' => new DateTime($anneeDebut.'-09-01'),
            'fin' => new DateTime(($anneeDebut + 1).'-08-31'),
        ];
    }

    public function anneeDuJour(?DateTimeImmutable $jour = null): int
    {
        $jour = $jour ?? $this->now();

        return match (true) {
            $jour->format('m') > '08' => (int)$jour->format('Y'),
            default => (int)$jour->format('Y') - 1
        };
    }

    /**
     * @param Beneficiaire[] $benefs
     * @return DateTimeImmutable
     */
    protected function getDebutAnneeUniversitairePourBeneficiaires(array $benefs): DateTimeImmutable
    {
        return $this->calculerDebutAnneeUniversitaire($this->getDernierBeneficiaire($benefs));
    }

    protected function calculerDebutAnneeUniversitaire(Beneficiaire $dernierBenef): DateTimeImmutable
    {
        // Utiliser dateFin si elle est définie, sinon utiliser dateDebut
        if ($dernierBenef->getFin() !== null) {
            $dateReference = $dernierBenef->getFin();
        } else {
            $dateReference = new DateTime('now');// Si dateFin est null, considérer la date actuelle
            // Si la date du jour est entre le 1er juillet et le 31 août, considérer l'année universitaire suivante
            if ($dateReference->format('m') > '06' && $dateReference->format('m') < '09') {
                $dateReference = $dateReference->modify('+1 year');
            }
        }

        // Extraire l'année et le mois de la date de référence
        $annee = (int)$dateReference->format('Y');
        $mois = (int)$dateReference->format('m');

        $anneeDebut = match (true) {
            $mois >= 9 => $annee,               // Après septembre, année N
            default => $annee - 1               // Avant septembre, année N-1
        };

        // La date de début de l'année universitaire
        return DateTimeImmutable::createFromFormat('Y-m-d', $anneeDebut.'-09-01');
    }

    /**
     * @param Beneficiaire[] $benefs
     * @return Beneficiaire
     */
    protected function getDernierBeneficiaire(array $benefs): Beneficiaire
    {
        usort(
            $benefs,
            fn(Beneficiaire $a, Beneficiaire $b) => match (true) {
                null == $a->getFin() => 1,
                null == $b->getFin() => -1,
                default => $b->getDebut() <=> $a->getDebut(),
            },
        );

        return current($benefs);
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    private function getDebutSemestre1(): DateTimeInterface
    {
        return new DateTime($this->anneeDuJour().'-09-01');
    }

    /**
     * @return DateTimeInterface
     * @throws Exception
     */
    private function getDebutSemestre2(): DateTimeInterface
    {
        return new DateTime(($this->anneeDuJour() + 1).'-01-01');
    }

    /**
     * @return DateTimeInterface
     * @throws Exception
     */
    private function getFinSemestre1(): DateTimeInterface
    {
        return new DateTime($this->anneeDuJour().'-12-31');
    }

    /**
     * @return DateTimeInterface
     * @throws Exception
     */
    private function getFinSemestre2(): DateTimeInterface
    {
        return new DateTime(($this->anneeDuJour() + 1).'-08-31');
    }

}