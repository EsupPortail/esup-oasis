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

namespace App\Entity;

use App\Repository\ReponseRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Question $question = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $repondant = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?CampagneDemande $campagne = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $dateModification = null;

    #[ORM\ManyToMany(targetEntity: OptionReponse::class, cascade: ['persist'])]
    private Collection $optionsChoisies;

    #[ORM\ManyToMany(targetEntity: DisciplineSportive::class, inversedBy: 'reponses')]
    private Collection $disciplinesSportives;

    #[ORM\ManyToMany(targetEntity: TypologieHandicap::class, inversedBy: 'reponses')]
    private Collection $typologiesHandicap;

    #[ORM\ManyToMany(targetEntity: TypeEngagement::class, inversedBy: 'reponses')]
    private Collection $typesEngagement;

    #[ORM\ManyToMany(targetEntity: TypeAmenagement::class, inversedBy: 'reponses')]
    private Collection $typesAmenagement;

    #[ORM\ManyToMany(targetEntity: ClubSportif::class, inversedBy: 'reponses')]
    private Collection $clubs;

    #[ORM\ManyToMany(targetEntity: Fichier::class, inversedBy: 'reponses', cascade: ['persist'])]
    private Collection $piecesJustificatives;

    /**
     * @var Collection<int, CategorieAmenagement>
     */
    #[ORM\ManyToMany(targetEntity: CategorieAmenagement::class, inversedBy: 'reponses')]
    private Collection $categoriesAmenagement;

    /**
     * @var Collection<int, DisciplineArtistique>
     */
    #[ORM\ManyToMany(targetEntity: DisciplineArtistique::class, inversedBy: 'reponses')]
    private Collection $disciplinesArtistiques;

    /**
     * @var Collection<int, EtablissementEnseignementArtistique>
     */
    #[ORM\ManyToMany(targetEntity: EtablissementEnseignementArtistique::class, inversedBy: 'reponses')]
    private Collection $etablissementsEnseignementArtistique;

    public function __construct()
    {
        $this->optionsChoisies = new ArrayCollection();
        $this->disciplinesSportives = new ArrayCollection();
        $this->typologiesHandicap = new ArrayCollection();
        $this->typesEngagement = new ArrayCollection();
        $this->typesAmenagement = new ArrayCollection();
        $this->clubs = new ArrayCollection();
        $this->piecesJustificatives = new ArrayCollection();
        $this->categoriesAmenagement = new ArrayCollection();
        $this->disciplinesArtistiques = new ArrayCollection();
        $this->etablissementsEnseignementArtistique = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getRepondant(): ?Utilisateur
    {
        return $this->repondant;
    }

    public function setRepondant(?Utilisateur $repondant): static
    {
        $this->repondant = $repondant;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getCampagne(): ?CampagneDemande
    {
        return $this->campagne;
    }

    public function setCampagne(?CampagneDemande $campagne): static
    {
        $this->campagne = $campagne;

        return $this;
    }

    public function getDateModification(): ?DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(DateTimeInterface $dateModification): static
    {
        $this->dateModification = DateTime::createFromInterface($dateModification);

        return $this;
    }

    /**
     * @return Collection<int, OptionReponse>
     */
    public function getOptionsChoisies(): Collection
    {
        return $this->optionsChoisies;
    }

    public function addOptionsChoisie(OptionReponse $optionsChoisy): static
    {
        if (!$this->optionsChoisies->contains($optionsChoisy)) {
            $this->optionsChoisies->add($optionsChoisy);
        }

        return $this;
    }

    public function removeOptionsChoisie(OptionReponse $optionsChoisy): static
    {
        $this->optionsChoisies->removeElement($optionsChoisy);

        return $this;
    }

    /**
     * @param DisciplineSportive[] $disciplines
     * @return Reponse
     */
    public function majDisciplinesSportives(array $disciplines): static
    {
        foreach ($this->getDisciplinesSportives() as $discipline) {
            if (!in_array($discipline, $disciplines)) {
                $this->removeDisciplineSportive($discipline);
            }
        }
        foreach ($disciplines as $discipline) {
            $this->addDisciplineSportive($discipline);
        }
        return $this;
    }

    /**
     * @return Collection<int, DisciplineSportive>
     */
    public function getDisciplinesSportives(): Collection
    {
        return $this->disciplinesSportives;
    }

    public function addDisciplineSportive(DisciplineSportive $discipline): static
    {
        if (!$this->disciplinesSportives->contains($discipline)) {
            $this->disciplinesSportives->add($discipline);
        }

        return $this;
    }

    public function removeDisciplineSportive(DisciplineSportive $discipline): static
    {
        $this->disciplinesSportives->removeElement($discipline);

        return $this;
    }

    /**
     * @return Collection<int, TypologieHandicap>
     */
    public function getTypologiesHandicap(): Collection
    {
        return $this->typologiesHandicap;
    }

    public function addTypologiesHandicap(TypologieHandicap $typologiesHandicap): static
    {
        if (!$this->typologiesHandicap->contains($typologiesHandicap)) {
            $this->typologiesHandicap->add($typologiesHandicap);
        }

        return $this;
    }

    public function removeTypologiesHandicap(TypologieHandicap $typologiesHandicap): static
    {
        $this->typologiesHandicap->removeElement($typologiesHandicap);

        return $this;
    }

    /**
     * @param TypologieHandicap[] $typologies
     * @return $this
     */
    public function majTypologies(array $typologies): static
    {
        foreach ($this->getTypologiesHandicap() as $typologie) {
            if (!in_array($typologie, $typologies)) {
                $this->removeTypologiesHandicap($typologie);
            }
        }
        foreach ($typologies as $typologie) {
            $this->addTypologiesHandicap($typologie);
        }
        return $this;
    }

    /**
     * @return Collection<int, TypeEngagement>
     */
    public function getTypesEngagement(): Collection
    {
        return $this->typesEngagement;
    }

    public function addTypesEngagement(TypeEngagement $typesEngagement): static
    {
        if (!$this->typesEngagement->contains($typesEngagement)) {
            $this->typesEngagement->add($typesEngagement);
        }

        return $this;
    }

    public function removeTypesEngagement(TypeEngagement $typesEngagement): static
    {
        $this->typesEngagement->removeElement($typesEngagement);

        return $this;
    }

    /**
     * @param TypeEngagement[] $typesEngagement
     * @return $this
     */
    public function majTypesEngagement(array $typesEngagement): static
    {
        foreach ($this->getTypesEngagement() as $type) {
            if (!in_array($type, $typesEngagement)) {
                $this->removeTypesEngagement($type);
            }
        }
        foreach ($typesEngagement as $type) {
            $this->addTypesEngagement($type);
        }
        return $this;
    }

    /**
     * @return Collection<int, TypeAmenagement>
     */
    public function getTypesAmenagement(): Collection
    {
        return $this->typesAmenagement;
    }

    public function addAmenagement(TypeAmenagement $amenagement): static
    {
        if (!$this->typesAmenagement->contains($amenagement)) {
            $this->typesAmenagement->add($amenagement);
        }

        return $this;
    }

    public function removeAmenagement(TypeAmenagement $amenagement): static
    {
        $this->typesAmenagement->removeElement($amenagement);

        return $this;
    }

    /**
     * @param TypeAmenagement[] $amenagements
     * @return $this
     */
    public function majAmenagements(array $amenagements): static
    {
        foreach ($this->getTypesAmenagement() as $amenagement) {
            if (!in_array($amenagement, $amenagements)) {
                $this->removeAmenagement($amenagement);
            }
        }

        foreach ($amenagements as $amenagement) {
            $this->addAmenagement($amenagement);
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubSportif>
     */
    public function getClubs(): Collection
    {
        return $this->clubs;
    }

    public function addClub(ClubSportif $club): static
    {
        if (!$this->clubs->contains($club)) {
            $this->clubs->add($club);
        }

        return $this;
    }

    public function removeClub(ClubSportif $club): static
    {
        $this->clubs->removeElement($club);

        return $this;
    }

    /**
     * @param ClubSportif[] $clubs
     * @return $this
     */
    public function majClubs(array $clubs): self
    {
        foreach ($this->getClubs() as $club) {
            if (!in_array($club, $clubs)) {
                $this->removeClub($club);
            }
        }

        foreach ($clubs as $club) {
            $this->addClub($club);
        }

        return $this;
    }

    public function getOptionsChoisiesTousTypes(): array
    {
        return (match ($this->getQuestion()->getTableOptions()) {
            'discipline_sportive' => $this->getDisciplinesSportives(),
            'discipline_artistique' => $this->getDisciplinesArtistiques(),
            'typologie_handicap' => $this->getTypologiesHandicap(),
            'type_engagement' => $this->getTypesEngagement(),
            'amenagement_examens', 'amenagement_pedagogique' => $this->getTypesAmenagement(),
            'clubs_professionnels', 'clubs_centre_formation' => $this->getClubs(),
            'etablissement_artistique' => $this->getEtablissementsEnseignementArtistique(),
            default => $this->getOptionsChoisies()
        })->toArray();
    }

    /**
     * @return Collection<int, Fichier>
     */
    public function getPiecesJustificatives(): Collection
    {
        return $this->piecesJustificatives;
    }

    public function addPieceJustificative(Fichier $piece): static
    {
        if (!$this->piecesJustificatives->contains($piece)) {
            $this->piecesJustificatives->add($piece);
        }

        return $this;
    }

    public function removePieceJustificative(Fichier $piece): static
    {
        $this->piecesJustificatives->removeElement($piece);

        return $this;
    }

    /**
     * @param Fichier[] $fichiers
     * @return $this
     */
    public function setPiecesJustificatives(array $fichiers): static
    {
        foreach ($this->getPiecesJustificatives() as $pj) {
            if (!in_array($pj, $fichiers)) {
                $this->removePieceJustificative($pj);
            }
        }
        foreach ($fichiers as $fichier) {
            $this->addPieceJustificative($fichier);
            $fichier->setProprietaire($this->getRepondant());
        }
        return $this;
    }

    /**
     * @return Collection<int, CategorieAmenagement>
     */
    public function getCategoriesAmenagement(): Collection
    {
        return $this->categoriesAmenagement;
    }

    public function addCategoriesAmenagement(CategorieAmenagement $categoriesAmenagement): static
    {
        if (!$this->categoriesAmenagement->contains($categoriesAmenagement)) {
            $this->categoriesAmenagement->add($categoriesAmenagement);
        }

        return $this;
    }

    public function removeCategoriesAmenagement(CategorieAmenagement $categoriesAmenagement): static
    {
        $this->categoriesAmenagement->removeElement($categoriesAmenagement);

        return $this;
    }

    /**
     * @param CategorieAmenagement[] $options
     * @return $this
     */
    public function majCategoriesAmenagement(array $options): static
    {
        foreach ($this->getCategoriesAmenagement() as $categorie) {
            if (!in_array($categorie, $options)) {
                $this->removeClub($categorie);
            }
        }

        foreach ($options as $categorie) {
            $this->addCategoriesAmenagement($categorie);
        }

        return $this;
    }

    /**
     * @return Demande
     */
    public function getDemande(): Demande
    {
        return $this->getCampagne()->getDemande($this->getRepondant());
    }

    /**
     * Nécessaire pour construire un objet factice
     *
     * @param int $id
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Collection<int, DisciplineArtistique>
     */
    public function getDisciplinesArtistiques(): Collection
    {
        return $this->disciplinesArtistiques;
    }

    public function addDisciplinesArtistique(DisciplineArtistique $disciplinesArtistique): static
    {
        if (!$this->disciplinesArtistiques->contains($disciplinesArtistique)) {
            $this->disciplinesArtistiques->add($disciplinesArtistique);
        }

        return $this;
    }

    public function removeDisciplinesArtistique(DisciplineArtistique $disciplinesArtistique): static
    {
        $this->disciplinesArtistiques->removeElement($disciplinesArtistique);

        return $this;
    }

    /**
     * @param DisciplineArtistique[] $disciplines
     * @return Reponse
     */
    public function majDisciplinesArtistiques(array $disciplines): static
    {
        foreach ($this->getDisciplinesArtistiques() as $discipline) {
            if (!in_array($discipline, $disciplines)) {
                $this->removeDisciplinesArtistique($discipline);
            }
        }
        foreach ($disciplines as $discipline) {
            $this->addDisciplinesArtistique($discipline);
        }
        return $this;
    }

    /**
     * @return Collection<int, EtablissementEnseignementArtistique>
     */
    public function getEtablissementsEnseignementArtistique(): Collection
    {
        return $this->etablissementsEnseignementArtistique;
    }

    public function addEtablissementEnseignementArtistique(EtablissementEnseignementArtistique $etablissementsEnseignementArtistique): static
    {
        if (!$this->etablissementsEnseignementArtistique->contains($etablissementsEnseignementArtistique)) {
            $this->etablissementsEnseignementArtistique->add($etablissementsEnseignementArtistique);
        }

        return $this;
    }

    public function removeEtablissementEnseignementArtistique(EtablissementEnseignementArtistique $etablissementsEnseignementArtistique): static
    {
        $this->etablissementsEnseignementArtistique->removeElement($etablissementsEnseignementArtistique);

        return $this;
    }

    /**
     * @param EtablissementEnseignementArtistique[] $etablissements
     * @return Reponse
     */
    public function majEtablissementsEnseignementArtistique(array $etablissements): static
    {
        foreach ($this->getEtablissementsEnseignementArtistique() as $etab) {
            if (!in_array($etab, $etablissements)) {
                $this->removeEtablissementEnseignementArtistique($etab);
            }
        }
        foreach ($etablissements as $etablissement) {
            $this->addEtablissementEnseignementArtistique($etablissement);
        }
        return $this;
    }

}
