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

namespace App\Entity;

use App\Repository\InscriptionRepository;
use App\Service\SiScol\NiveauExtractor;
use App\Service\SiScol\NiveauResolver;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[Index(name: 'IDX_INSCRIPTION_FIN', columns: ['fin'])]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $etudiant = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $formation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $fin = null;

    /**
     * Code étape Apogée (cod_etp), conservé pour exposer le cursus
     * d'inscription et en dériver le niveau d'études (L1/L2/L3/M1/M2/D1-D3).
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codeEtape = null;

    /**
     * compteur natif Apogée (nbr_ins_etp) du nombre
     * d'inscriptions administratives à l'étape. Base officielle du
     * calcul du redoublement (cf. RedoublementCalculator).
     */
    #[ORM\Column(nullable: true)]
    private ?int $nombreInscriptionsEtape = null;

    /**
     * code du cursus aménagé SISE (cod_sis_cur_amg). Sa présence
     * neutralise le calcul du redoublement (étalement / contrat
     * pédagogique pluri-annuel) et alimente l'affichage "cursus aménagé".
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codeCursusAmenage = null;

    /**
     * libellé du cursus aménagé (lib_cur_amg), affiché tel quel
     * sur la fiche bénéficiaire.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelleCursusAmenage = null;

    /**
     * Cycle du diplôme Apogée (cod_cyc : 1 = Licence, 2 = Master,
     * 3 = Doctorat). Donne le niveau d'entrée (bac+N). Couplé à
     * anneeDansDiplome, permet de dériver le niveau LMD via NiveauResolver.
     */
    #[ORM\Column(nullable: true)]
    private ?int $cycle = null;

    /**
     * Année dans le diplôme (Apogée cod_sis_daa, codage SISE national,
     * donc multi-établissements). niveau (bac+N) = niveau d'entrée du
     * cycle + anneeDansDiplome.
     */
    #[ORM\Column(nullable: true)]
    private ?int $anneeDansDiplome = null;

    /**
     * Type de diplôme Apogée (cod_tpd_etb, code établissement). Discrimine la
     * famille de diplôme (Licence/Master/Doctorat vs BUT/DUT/ingénieur/DU) :
     * seuls les types LMD donnent lieu à un niveau L/M/D (cf. NiveauResolver).
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codeTypeDiplome = null;

    /**
     * Indicateur santé (Apogée typ_diplome.tem_sante = 'O') : formations de
     * santé (PASS/LAS/MED…) pour lesquelles le niveau L/M/D n'est pas affiché.
     */
    #[ORM\Column]
    private bool $sante = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiant(): ?Utilisateur
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Utilisateur $etudiant): self
    {
        $this->etudiant = $etudiant;

        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;

        return $this;
    }

    public function getDebut(): ?DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(DateTimeInterface $debut): self
    {
        $this->debut = DateTime::createFromInterface($debut);

        return $this;
    }

    public function getFin(): ?DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(DateTimeInterface $fin): self
    {
        $this->fin = DateTime::createFromInterface($fin);

        return $this;
    }

    public function getCodeEtape(): ?string
    {
        return $this->codeEtape;
    }

    public function setCodeEtape(?string $codeEtape): self
    {
        $this->codeEtape = $codeEtape;

        return $this;
    }

    public function getNombreInscriptionsEtape(): ?int
    {
        return $this->nombreInscriptionsEtape;
    }

    public function setNombreInscriptionsEtape(?int $nombreInscriptionsEtape): self
    {
        $this->nombreInscriptionsEtape = $nombreInscriptionsEtape;

        return $this;
    }

    public function getCodeCursusAmenage(): ?string
    {
        return $this->codeCursusAmenage;
    }

    public function setCodeCursusAmenage(?string $codeCursusAmenage): self
    {
        $this->codeCursusAmenage = $codeCursusAmenage;

        return $this;
    }

    public function getLibelleCursusAmenage(): ?string
    {
        return $this->libelleCursusAmenage;
    }

    public function setLibelleCursusAmenage(?string $libelleCursusAmenage): self
    {
        $this->libelleCursusAmenage = $libelleCursusAmenage;

        return $this;
    }

    public function getCycle(): ?int
    {
        return $this->cycle;
    }

    public function setCycle(?int $cycle): self
    {
        $this->cycle = $cycle;

        return $this;
    }

    public function getAnneeDansDiplome(): ?int
    {
        return $this->anneeDansDiplome;
    }

    public function setAnneeDansDiplome(?int $anneeDansDiplome): self
    {
        $this->anneeDansDiplome = $anneeDansDiplome;

        return $this;
    }

    public function getCodeTypeDiplome(): ?string
    {
        return $this->codeTypeDiplome;
    }

    public function setCodeTypeDiplome(?string $codeTypeDiplome): self
    {
        $this->codeTypeDiplome = $codeTypeDiplome;

        return $this;
    }

    public function isSante(): bool
    {
        return $this->sante;
    }

    public function setSante(bool $sante): self
    {
        $this->sante = $sante;

        return $this;
    }

    /**
     * Niveau d'études (L1..M2 / D1-D3, BUT1…) de l'inscription.
     *
     * Le niveau porté par la formation fait foi : un établissement dont le SI
     * scolarité le renseigne garde exactement son affichage, la dérivation
     * ci-dessous ne s'applique jamais. Elle ne sert qu'à combler un niveau
     * absent, à partir des données Apogée nationales (cycle du diplôme + année
     * dans le diplôme), en séparant les familles via le type de diplôme (seul
     * le LMD donne un niveau L/M/D ; santé et autres familles restent vides),
     * avec repli sur le préfixe du code étape. null quand aucune source ne
     * permet de conclure.
     */
    public function getNiveau(): ?string
    {
        $niveauFormation = $this->formation?->getNiveau();
        if (null !== $niveauFormation && '' !== $niveauFormation) {
            return $niveauFormation;
        }

        // Type de diplôme obligatoire : un type manquant (inscription non
        // synchronisée) ne doit pas produire de faux niveau LMD ; on retombe
        // alors sur le préfixe du code étape s'il encode le niveau.
        return (new NiveauResolver(typeDiplomeObligatoire: true))
            ->resolve($this->cycle, $this->anneeDansDiplome, $this->codeTypeDiplome, $this->sante)
            ?? (new NiveauExtractor())->extract($this->codeEtape);
    }
}
