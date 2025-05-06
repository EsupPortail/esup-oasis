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

use App\Repository\UtilisateurRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity('numeroAnonyme')]
class Utilisateur implements UserInterface
{
    use ClockAwareTrait;

    public const string ROLE_ADMIN = 'ROLE_ADMIN';
    public const string ROLE_ADMIN_TECHNIQUE = 'ROLE_ADMIN_TECHNIQUE';
    public const string ROLE_USER = 'ROLE_USER';
    public const string ROLE_BENEFICIAIRE = 'ROLE_BENEFICIAIRE';
    public const string ROLE_INTERVENANT = 'ROLE_INTERVENANT';
    public const string ROLE_GESTIONNAIRE = 'ROLE_GESTIONNAIRE';
    public const string ROLE_RENFORT = 'ROLE_RENFORT';
    public const string ROLE_RENFORT_DEMANDES = self::ROLE_RENFORT;
    public const string ROLE_PLANIFICATEUR = 'ROLE_PLANIFICATEUR';
    public const string ROLE_DEMANDEUR = 'ROLE_DEMANDEUR';
    public const string ROLE_MEMBRE_COMMISSION = 'ROLE_MEMBRE_COMMISSION';
    public const string ROLE_ATTRIBUER_PROFIL = 'ROLE_ATTRIBUER_PROFIL';
    public const string ROLE_VALIDER_CONFORMITE_DEMANDE = 'ROLE_VALIDER_CONFORMITE_DEMANDE';
    public const string ROLE_REFERENT_COMPOSANTE = 'ROLE_REFERENT_COMPOSANTE';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $uid = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Beneficiaire::class,
        cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(["debut" => "DESC"])]
    private Collection $beneficiaires;

    #[ORM\OneToOne(mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?Intervenant $intervenant = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $admin = false;

    #[ORM\ManyToMany(targetEntity: Service::class, inversedBy: 'utilisateurs')]
    private Collection $services;

    #[ORM\OneToMany(mappedBy: 'gestionnaire', targetEntity: Beneficiaire::class)]
    private Collection $beneficiairesGeres;

    #[ORM\OneToMany(mappedBy: 'etudiant', targetEntity: Inscription::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $inscriptions;

    #[ORM\Column(nullable: true)]
    private ?int $numeroEtudiant = null;

    #[ORM\OneToMany(mappedBy: 'utilisateurCreation', targetEntity: Evenement::class)]
    private Collection $evenementsCrees;

    #[ORM\OneToMany(mappedBy: 'utilisateurModification', targetEntity: Evenement::class)]
    private Collection $evenementsModifies;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailPerso = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telPerso = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $abonneImmediat = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $abonneVeille = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $abonneAvantVeille = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $abonneRecapHebdo = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $destinataireTechnique = false;

    #[ORM\OneToMany(mappedBy: 'repondant', targetEntity: Reponse::class, fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $reponses;

    #[ORM\OneToMany(mappedBy: 'demandeur', targetEntity: Demande::class, fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $demandes;

    #[ORM\OneToMany(mappedBy: 'proprietaire', targetEntity: Fichier::class)]
    private Collection $fichiers;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: MembreCommission::class,
        cascade: ['persist'], orphanRemoval: true)]
    private Collection $membreCommissions;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateNaissance = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: AvisEse::class, orphanRemoval: true)]
    private Collection $avisEse;

    /**
     * @var Collection<int, Entretien>
     */
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Entretien::class, orphanRemoval: true)]
    private Collection $entretiens;

    /**
     * @var Collection<int, ParametreUI>
     */
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: ParametreUI::class, orphanRemoval: true)]
    private Collection $parametresUI;

    /**
     * @var Collection<int, Composante>
     */
    #[ORM\ManyToMany(targetEntity: Composante::class, mappedBy: 'referents')]
    private Collection $composantes;

    /**
     * @var Collection<int, DecisionAmenagementExamens>
     */
    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: DecisionAmenagementExamens::class, orphanRemoval: true)]
    private Collection $decisionsAmenagementExamens;

    /**
     * @var Collection<int, PieceJointeBeneficiaire>
     */
    #[ORM\OneToMany(mappedBy: 'beneficiaire', targetEntity: PieceJointeBeneficiaire::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $piecesJointes;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contactUrgence = null;

    #[ORM\Column(nullable: true)]
    private ?bool $boursier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statutEtudiant = null;

    #[ORM\Column(unique: true, nullable: true)]
    private ?int $numeroAnonyme = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $roles = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $etatAvisEse = null;

    /**
     * @var Collection<int, Bilan>
     */
    #[ORM\OneToMany(mappedBy: 'demandeur', targetEntity: Bilan::class)]
    private Collection $bilans;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $gestionnaire = false;

    public function __construct()
    {
        $this->beneficiaires = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->beneficiairesGeres = new ArrayCollection();
        $this->inscriptions = new ArrayCollection();
        $this->evenementsCrees = new ArrayCollection();
        $this->evenementsModifies = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->demandes = new ArrayCollection();
        $this->fichiers = new ArrayCollection();
        $this->membreCommissions = new ArrayCollection();
        $this->avisEse = new ArrayCollection();
        $this->entretiens = new ArrayCollection();
        $this->parametresUI = new ArrayCollection();
        $this->composantes = new ArrayCollection();
        $this->decisionsAmenagementExamens = new ArrayCollection();
        $this->piecesJointes = new ArrayCollection();
        $this->bilans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->uid;
    }

    /**
     * Utilisé par JWTManager - flemme de surcharger leur truc juste pour ça...
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getRoles(): array
    {
        $rolesStockes = $this->getRolesStockes() ?? [];
        if (!empty($rolesStockes)) {
            return $rolesStockes;
        }
        return $this->getRolesCalcules();
    }

    /**
     * @see UserInterface
     */
    public function getRolesCalcules(): array
    {
        $roles[] = self::ROLE_USER;

        if ($this->estDemandeur()) {
            $roles[] = self::ROLE_DEMANDEUR;
        }

        if ($this->estMembreCommission()) {
            $roles[] = self::ROLE_MEMBRE_COMMISSION;
        }

        if (!$this->getComposantes()->isEmpty()) {
            $roles[] = self::ROLE_REFERENT_COMPOSANTE;
        }

        if (!$this->getBeneficiaires()->isEmpty()) {
            $roles[] = self::ROLE_BENEFICIAIRE;
        }
        if (null !== $this->getIntervenant() && !$this->getIntervenant()->isArchive()) {
            foreach ($this->getIntervenant()->getTypesEvenements() as $type) {
                if ($type->getId() !== TypeEvenement::TYPE_RENFORT) {
                    $roles[] = self::ROLE_INTERVENANT;
                    break;
                }
            }
        }
        if ($this->isAdmin()) {
            $roles[] = self::ROLE_ADMIN;
            $roles[] = self::ROLE_GESTIONNAIRE;
            $roles[] = self::ROLE_PLANIFICATEUR;
            if ($this->isDestinataireTechnique()) {
                $roles[] = self::ROLE_ADMIN_TECHNIQUE;
            }
        }
        if (!$this->getServices()->isEmpty()) {
            if ($this->isRenfort()) {
                $roles[] = self::ROLE_PLANIFICATEUR;
                $roles[] = self::ROLE_RENFORT;
            }
            if ($this->isGestionnaire()) {
                $roles[] = self::ROLE_PLANIFICATEUR;
                $roles[] = self::ROLE_GESTIONNAIRE;
            }
        }
        return array_values(array_unique($roles));
    }

    public function isRenfort(): bool
    {
        if (!$this->getServices()->isEmpty()) {
            if (null !== $this->getIntervenant() && !$this->getIntervenant()->isArchive()) {
                foreach ($this->getIntervenant()->getTypesEvenements() as $type) {
                    if ($type->getId() === TypeEvenement::TYPE_RENFORT) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }


    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * @return Collection<int, Beneficiaire>
     */
    public function getBeneficiaires(): Collection
    {
        return $this->beneficiaires;
    }

    public function addBeneficiaire(Beneficiaire $beneficiaire): self
    {
        if (!$this->beneficiaires->contains($beneficiaire)) {
            $this->beneficiaires->add($beneficiaire);
            $beneficiaire->setUtilisateur($this);
        }

        return $this;
    }

    public function removeBeneficiaire(Beneficiaire $beneficiaire): self
    {
        $this->beneficiaires->removeElement($beneficiaire);

        return $this;
    }

    public function getIntervenant(): ?Intervenant
    {
        return $this->intervenant;
    }

    public function setIntervenant(?Intervenant $intervenant): self
    {
        // set the owning side of the relation if necessary
        if (null !== $intervenant && $intervenant->getUtilisateur() !== $this) {
            $intervenant->setUtilisateur($this);
        }

        $this->intervenant = $intervenant;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
        }

        return $this;
    }

    public function removeService(Service $service): self
    {
        $this->services->removeElement($service);

        return $this;
    }

    /**
     * @return Collection<int, Beneficiaire>
     */
    public function getBeneficiairesGeres(): Collection
    {
        return $this->beneficiairesGeres;
    }

    public function addBeneficiairesGere(Beneficiaire $beneficiairesGere): self
    {
        if (!$this->beneficiairesGeres->contains($beneficiairesGere)) {
            $this->beneficiairesGeres->add($beneficiairesGere);
            $beneficiairesGere->setGestionnaire($this);
        }

        return $this;
    }

    public function removeBeneficiairesGere(Beneficiaire $beneficiairesGere): self
    {
        if ($this->beneficiairesGeres->removeElement($beneficiairesGere)) {
            // set the owning side to null (unless already changed)
            if ($beneficiairesGere->getGestionnaire() === $this) {
                $beneficiairesGere->setGestionnaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Inscription>
     */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    public function addInscription(Inscription $inscription): self
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setEtudiant($this);
        }

        return $this;
    }

    public function removeInscription(Inscription $inscription): self
    {
        if ($this->inscriptions->removeElement($inscription)) {
            // set the owning side to null (unless already changed)
            if ($inscription->getEtudiant() === $this) {
                $inscription->setEtudiant(null);
            }
        }

        return $this;
    }

    public function getNumeroEtudiant(): ?int
    {
        return $this->numeroEtudiant;
    }

    public function setNumeroEtudiant(?int $numeroEtudiant): self
    {
        $this->numeroEtudiant = $numeroEtudiant;

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsCrees(): Collection
    {
        return $this->evenementsCrees;
    }

    public function addEvenementsCree(Evenement $evenementsCree): self
    {
        if (!$this->evenementsCrees->contains($evenementsCree)) {
            $this->evenementsCrees->add($evenementsCree);
            $evenementsCree->setUtilisateurCreation($this);
        }

        return $this;
    }

    public function removeEvenementsCree(Evenement $evenementsCree): self
    {
        if ($this->evenementsCrees->removeElement($evenementsCree)) {
            // set the owning side to null (unless already changed)
            if ($evenementsCree->getUtilisateurCreation() === $this) {
                $evenementsCree->setUtilisateurCreation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsModifies(): Collection
    {
        return $this->evenementsModifies;
    }

    public function addEveneMentsModify(Evenement $eveneMentsModify): self
    {
        if (!$this->evenementsModifies->contains($eveneMentsModify)) {
            $this->evenementsModifies->add($eveneMentsModify);
            $eveneMentsModify->setUtilisateurModification($this);
        }

        return $this;
    }

    public function removeEveneMentsModify(Evenement $eveneMentsModify): self
    {
        if ($this->evenementsModifies->removeElement($eveneMentsModify)) {
            // set the owning side to null (unless already changed)
            if ($eveneMentsModify->getUtilisateurModification() === $this) {
                $eveneMentsModify->setUtilisateurModification(null);
            }
        }

        return $this;
    }

    public function getEmailPerso(): ?string
    {
        return $this->emailPerso;
    }

    public function setEmailPerso(?string $emailPerso): self
    {
        $this->emailPerso = $emailPerso;

        return $this;
    }

    public function getTelPerso(): ?string
    {
        return $this->telPerso;
    }

    public function setTelPerso(?string $telPerso): self
    {
        $this->telPerso = $telPerso;

        return $this;
    }

    public function isAbonneImmediat(): ?bool
    {
        return $this->abonneImmediat;
    }

    public function setAbonneImmediat(bool $abonneImmediat): static
    {
        $this->abonneImmediat = $abonneImmediat;

        return $this;
    }

    public function isAbonneVeille(): ?bool
    {
        return $this->abonneVeille;
    }

    public function setAbonneVeille(bool $abonneVeille): static
    {
        $this->abonneVeille = $abonneVeille;

        return $this;
    }

    public function isAbonneAvantVeille(): ?bool
    {
        return $this->abonneAvantVeille;
    }

    public function setAbonneAvantVeille(bool $abonneAvantVeille): static
    {
        $this->abonneAvantVeille = $abonneAvantVeille;

        return $this;
    }

    public function isAbonneRecapHebdo(): ?bool
    {
        return $this->abonneRecapHebdo;
    }

    public function setAbonneRecapHebdo(bool $abonneRecapHebdo): static
    {
        $this->abonneRecapHebdo = $abonneRecapHebdo;

        return $this;
    }

    public function isDestinataireTechnique(): ?bool
    {
        return $this->destinataireTechnique;
    }

    public function setDestinataireTechnique(bool $destinataireTechnique): static
    {
        $this->destinataireTechnique = $destinataireTechnique;

        return $this;
    }

    public function isGestionnaire(): bool
    {
        return $this->gestionnaire;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setRepondant($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            // set the owning side to null (unless already changed)
            if ($reponse->getRepondant() === $this) {
                $reponse->setRepondant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Demande>
     */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(Demande $demande): static
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
            $demande->setDemandeur($this);
        }

        return $this;
    }

    public function removeDemande(Demande $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            // set the owning side to null (unless already changed)
            if ($demande->getDemandeur() === $this) {
                $demande->setDemandeur(null);
            }
        }

        return $this;
    }

    public function getDemandePourCampagne(CampagneDemande $campagne): ?Demande
    {
        foreach ($this->getDemandes() as $demande) {
            if ($demande->getCampagne()->getId() === $campagne->getId()) {
                return $demande;
            }
        }
        return null;
    }

    /**
     * @return bool
     */
    private function estDemandeur(): bool
    {
        //pas étudiant?
        if (null === $this->getNumeroEtudiant()) {
            return false;
        }

        //demande en cours?
        foreach ($this->getDemandes() as $demande) {
            if ($demande->getEtat()->getId() === EtatDemande::EN_COURS || $demande->getEtat()->getId() === EtatDemande::NON_CONFORME) {
                return true;
            }
        }
        //inscription en cours?
        foreach ($this->getInscriptions() as $inscription) {
            if ($inscription->getFin() > $this->now()) {
                return true;
            }
        }

        //rien de bon...
        return false;
    }

    /**
     * @return Collection<int, Fichier>
     */
    public function getFichiers(): Collection
    {
        return $this->fichiers;
    }

    public function addFichier(Fichier $fichier): static
    {
        if (!$this->fichiers->contains($fichier)) {
            $this->fichiers->add($fichier);
            $fichier->setProprietaire($this);
        }

        return $this;
    }

    public function removeFichier(Fichier $fichier): static
    {
        if ($this->fichiers->removeElement($fichier)) {
            // set the owning side to null (unless already changed)
            if ($fichier->getProprietaire() === $this) {
                $fichier->setProprietaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MembreCommission>
     */
    public function getMembreCommissions(): Collection
    {
        return $this->membreCommissions;
    }

    public function addMembreCommission(MembreCommission $membreCommission): static
    {
        if (!$this->membreCommissions->contains($membreCommission)) {
            $this->membreCommissions->add($membreCommission);
            $membreCommission->setUtilisateur($this);
        }

        return $this;
    }

    public function removeMembreCommission(MembreCommission $membreCommission): static
    {
        if ($this->membreCommissions->removeElement($membreCommission)) {
            // set the owning side to null (unless already changed)
            if ($membreCommission->getUtilisateur() === $this) {
                $membreCommission->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function estRenfortDemandes(): bool
    {
        //todo
        return false;
    }

    /**
     * @return Tag[]
     */
    public function getTagsActifs(): array
    {
        $tags = [];
        foreach ($this->getBeneficiairesActifs() as $beneficiaire) {
            foreach ($beneficiaire->getTags() as $tag) {
                $tags[$tag->getId()] = $tag;
            }
        }
        return $tags;
    }

    /**
     * @return Beneficiaire[]
     */
    public function getBeneficiairesActifs(): array
    {
        $now = $this->now();
        $benefs = [];
        foreach ($this->getBeneficiaires() as $beneficiaire) {
            if ($now >= $beneficiaire->getDebut() && (null == $beneficiaire->getFin() || $now < $beneficiaire->getFin())) {
                $benefs[] = $beneficiaire;
            }
        }
        return $benefs;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function getDateNaissance(): ?DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    /**
     * @return Collection<int, AvisEse>
     */
    public function getAvisEse(): Collection
    {
        return $this->avisEse;
    }

    public function addAvisEse(AvisEse $avisEse): static
    {
        if (!$this->avisEse->contains($avisEse)) {
            $this->avisEse->add($avisEse);
            $avisEse->setUtilisateur($this);
        }

        return $this;
    }

    public function removeAvisEse(AvisEse $avisEse): static
    {
        if ($this->avisEse->removeElement($avisEse)) {
            // set the owning side to null (unless already changed)
            if ($avisEse->getUtilisateur() === $this) {
                $avisEse->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Entretien>
     */
    public function getEntretiens(): Collection
    {
        return $this->entretiens;
    }

    public function addEntretien(Entretien $entretien): static
    {
        if (!$this->entretiens->contains($entretien)) {
            $this->entretiens->add($entretien);
            $entretien->setUtilisateur($this);
        }

        return $this;
    }

    public function removeEntretien(Entretien $entretien): static
    {
        if ($this->entretiens->removeElement($entretien)) {
            // set the owning side to null (unless already changed)
            if ($entretien->getUtilisateur() === $this) {
                $entretien->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Amenagement[]
     */
    public function getAmenagementsActifs(): array
    {
        $amenagements = [];
        foreach ($this->getBeneficiairesActifs() as $beneficiaire) {
            $amenagementsActifs = array_filter(
                $beneficiaire->getAmenagementsActifs(),
                fn($amenagement) => !array_key_exists($amenagement->getId(), $amenagements)
            );
            foreach ($amenagementsActifs as $amenagementsActif) {
                $amenagements[$amenagementsActif->getId()] = $amenagementsActif;
            }
        }

        return $amenagements;
    }

    /**
     * @param DateTimeInterface|null $pourDate
     * @return string
     */
    public function getEtatAvisEse(?DateTimeInterface $pourDate = null): string
    {
        if (null !== $pourDate || null == $this->etatAvisEse) {
            return $this->getEtatAvisEseCalcule($pourDate);
        }

        return $this->etatAvisEse;
    }

    public function getEtatAvisEseCalcule(?DateTimeInterface $pourDate = null): string
    {
        foreach ($this->getAvisEse() as $avis) {
            if ($avis->estActif($pourDate)) {
                return AvisEse::ETAT_EN_COURS;
            }
            if ($avis->estEnCours($pourDate) && $avis->estEnAttente()) {
                return AvisEse::ETAT_EN_ATTENTE;
            }
        }
        //rien en cours...est-ce qu'on a un ancien avis?
        if ($this->getAvisEse()->count() > 0) {
            return AvisEse::ETAT_EN_ATTENTE;
        }

        return AvisEse::ETAT_AUCUN;
    }

    public function getDerniereInscription(): ?Inscription
    {
        $inscriptions = $this->getInscriptions()->toArray();
        usort($inscriptions, function (Inscription $a, Inscription $b) {
            return $b->getDebut() <=> $a->getDebut();
        });

        return array_shift($inscriptions);
    }

    /**
     * @return Collection<int, ParametreUI>
     */
    public function getParametresUI(): Collection
    {
        return $this->parametresUI;
    }

    public function addParametresUI(ParametreUI $parametresUI): static
    {
        if (!$this->parametresUI->contains($parametresUI)) {
            $this->parametresUI->add($parametresUI);
            $parametresUI->setUtilisateur($this);
        }

        return $this;
    }

    public function removeParametresUI(ParametreUI $parametresUI): static
    {
        if ($this->parametresUI->removeElement($parametresUI)) {
            // set the owning side to null (unless already changed)
            if ($parametresUI->getUtilisateur() === $this) {
                $parametresUI->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Composante>
     */
    public function getComposantes(): Collection
    {
        return $this->composantes;
    }

    public function addComposante(Composante $composante): static
    {
        if (!$this->composantes->contains($composante)) {
            $this->composantes->add($composante);
            $composante->addReferent($this);
        }

        return $this;
    }

    public function removeComposante(Composante $composante): static
    {
        if ($this->composantes->removeElement($composante)) {
            $composante->removeReferent($this);
        }

        return $this;
    }

    /**
     * @return Inscription[]
     */
    public function getInscriptionsEnCours(): array
    {
        $now = $this->now();
        return array_filter(
            $this->getInscriptions()->toArray(),
            fn(Inscription $ins) => $now >= $ins->getDebut() && $now <= $ins->getFin()
        );
    }

    /**
     * @return Collection<int, DecisionAmenagementExamens>
     */
    public function getDecisionsAmenagementExamens(): Collection
    {
        return $this->decisionsAmenagementExamens;
    }

    public function addDecisionsAmenagementExamen(DecisionAmenagementExamens $decisionsAmenagementExamen): static
    {
        if (!$this->decisionsAmenagementExamens->contains($decisionsAmenagementExamen)) {
            $this->decisionsAmenagementExamens->add($decisionsAmenagementExamen);
            $decisionsAmenagementExamen->setBeneficiaire($this);
        }

        return $this;
    }

    public function removeDecisionsAmenagementExamen(DecisionAmenagementExamens $decisionsAmenagementExamen): static
    {
        if ($this->decisionsAmenagementExamens->removeElement($decisionsAmenagementExamen)) {
            // set the owning side to null (unless already changed)
            if ($decisionsAmenagementExamen->getBeneficiaire() === $this) {
                $decisionsAmenagementExamen->setBeneficiaire(null);
            }
        }

        return $this;
    }

    public function getDecisionAmenagementExamens(DateTimeInterface $debut, DateTimeInterface $fin): DecisionAmenagementExamens|null
    {
        foreach ($this->getDecisionsAmenagementExamens() as $decision) {
            if ($debut == $decision->getDebut() && $fin == $decision->getFin()) {
                return $decision;
            }
        }
        return null;
    }

    /**
     * @return Collection<int, PieceJointeBeneficiaire>
     */
    public function getPiecesJointes(): Collection
    {
        return $this->piecesJointes;
    }

    public function addPiecesJointe(PieceJointeBeneficiaire $piecesJointe): static
    {
        if (!$this->piecesJointes->contains($piecesJointe)) {
            $this->piecesJointes->add($piecesJointe);
            $piecesJointe->setBeneficiaire($this);
        }

        return $this;
    }

    public function removePiecesJointe(PieceJointeBeneficiaire $piecesJointe): static
    {
        if ($this->piecesJointes->removeElement($piecesJointe)) {
            // set the owning side to null (unless already changed)
            if ($piecesJointe->getBeneficiaire() === $this) {
                $piecesJointe->setBeneficiaire(null);
            }
        }

        return $this;
    }

    public function getContactUrgence(): ?string
    {
        return $this->contactUrgence;
    }

    public function setContactUrgence(?string $contactUrgence): static
    {
        $this->contactUrgence = $contactUrgence;

        return $this;
    }

    public function isBoursier(): ?bool
    {
        return $this->boursier;
    }

    public function setBoursier(?bool $boursier): static
    {
        $this->boursier = $boursier;

        return $this;
    }

    public function getStatutEtudiant(): ?string
    {
        return $this->statutEtudiant;
    }

    public function setStatutEtudiant(?string $statutEtudiant): static
    {
        $this->statutEtudiant = $statutEtudiant;

        return $this;
    }

    public function getNumeroAnonyme(): ?int
    {
        return $this->numeroAnonyme;
    }

    public function setNumeroAnonyme(?int $numeroAnonyme): static
    {
        $this->numeroAnonyme = $numeroAnonyme;

        return $this;
    }

    /**
     * @param DateTime $debut
     * @param DateTime $fin
     * @return Beneficiaire[]
     */
    public function getBeneficiairesParIntervalle(DateTimeInterface $debut, DateTimeInterface $fin,
                                                  bool              $avecAccompagnement = true): array
    {
        return array_filter(
            $this->getBeneficiaires()->toArray(),
            fn(Beneficiaire $benef) => (
                (!$avecAccompagnement || $benef->isAvecAccompagnement())
                && (
                    ($debut >= $benef->getDebut() && ($debut < $benef->getFin() || $benef->getFin() === null))
                    ||
                    ($benef->getDebut() >= $debut && $benef->getDebut() < $fin)
                )
            )
        );
    }

    /**
     * @param DateTimeInterface $debut
     * @param DateTimeInterface $fin
     * @return Amenagement[]
     */
    public function getAmenagementsParIntervalle(DateTimeInterface $debut, DateTimeInterface $fin): array
    {
        $result = [];
        foreach ($this->getBeneficiairesParIntervalle($debut, $fin) as $beneficiaire) {
            foreach ($beneficiaire->getAmenagementsParIntervalle($debut, $fin) as $amenagement) {
                $result[$amenagement->getId()] = $amenagement;
            }
        }

        return $result;
    }

    public function getRolesStockes(): ?array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function setEtatAvisEse(?string $etatAvisEse): static
    {
        $this->etatAvisEse = $etatAvisEse;

        return $this;
    }

    /**
     * @return Collection<int, Bilan>
     */
    public function getBilans(): Collection
    {
        return $this->bilans;
    }

    public function addBilan(Bilan $bilan): static
    {
        if (!$this->bilans->contains($bilan)) {
            $this->bilans->add($bilan);
            $bilan->setDemandeur($this);
        }

        return $this;
    }

    public function removeBilan(Bilan $bilan): static
    {
        if ($this->bilans->removeElement($bilan)) {
            // set the owning side to null (unless already changed)
            if ($bilan->getDemandeur() === $this) {
                $bilan->setDemandeur(null);
            }
        }

        return $this;
    }


    public function countEntretiens(DateTimeInterface $debut, DateTimeInterface $fin): int
    {
        $entretiensDansIntervalle = array_filter(
            $this->getEntretiens()->toArray(),
            fn(Entretien $entretien) => $debut <= $entretien->getDate() && $entretien->getDate() <= $fin
        );

        return count($entretiensDansIntervalle);
    }

    public function setGestionnaire(bool $gestionnaire): static
    {
        $this->gestionnaire = $gestionnaire;

        return $this;
    }

    private function estMembreCommission(): bool
    {
        return $this->getMembreCommissions()->count() > 0;
    }
}
