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

namespace App\State\Utilisateur;

use App\ApiResource\TypeEvenement;
use App\Entity\Beneficiaire;
use App\Entity\Demande;
use App\Entity\Inscription;
use App\Entity\Intervenant;
use App\Entity\PieceJointeBeneficiaire;
use App\Entity\ProfilBeneficiaire;
use App\Entity\Question;
use App\Entity\Tag;
use App\Entity\TypologieHandicap;
use App\Entity\Utilisateur;
use App\Message\CreationIntervenantMessage;
use App\Message\RoleUtilisateursModifiesMessage;
use App\Repository\CampusRepository;
use App\Repository\CompetenceRepository;
use App\Repository\ProfilBeneficiaireRepository;
use App\Repository\ReponseRepository;
use App\Repository\ServiceRepository;
use App\Repository\TypeEvenementRepository;
use App\Repository\TypologieHandicapRepository;
use App\Repository\UtilisateurRepository;
use App\Service\ErreurLdapException;
use App\Service\LdapService;
use App\Service\SiScol\AbstractSiScolDataProvider;
use App\Service\SiScol\BackendUnavailableException;
use App\Service\SiScol\FormationManager;
use App\State\Demande\DemandeManager;
use App\Util\AnneeUniversitaireAwareTrait;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Generator;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class UtilisateurManager
{
    use AnneeUniversitaireAwareTrait;
    use ClockAwareTrait;

    public function __construct(private LdapService                  $ldapService,
                                private UtilisateurRepository        $utilisateurRepository,
                                private CampusRepository             $campusRepository,
                                private CompetenceRepository         $competenceRepository,
                                private TypeEvenementRepository      $typeEvenementRepository,
                                private ServiceRepository            $serviceRepository,
                                private LoggerInterface              $logger,
                                private Security                     $security,
                                private AbstractSiScolDataProvider   $scolProvider,
                                private FormationManager             $formationManager,
                                private ProfilBeneficiaireRepository $profilBeneficiaireRepository,
                                private TypologieHandicapRepository  $typologieHandicapRepository,
                                private DemandeManager               $demandeManager,
                                private MessageBusInterface          $messageBus,
                                private TagAwareCacheInterface       $cache, private ReponseRepository $reponseRepository)
    {

    }

    /**
     * @param string $uid
     * @param bool   $creerSiNouveau
     * @return Utilisateur
     * @throws ErreurLdapException
     */
    public function parUid(string $uid, bool $creerSiNouveau = false): Utilisateur
    {
        $userEntity = $this->utilisateurRepository->findOneBy([
            'uid' => $uid,
        ]);
        if (null !== $userEntity) {
            //si doute concernant leur fraicheur, récupération des données de scol
            if (null !== $userEntity->getNumeroEtudiant()) {
                if (empty($userEntity->getInscriptionsEnCours()) && $creerSiNouveau) {
                    $this->majInscriptionsEtIdentite($userEntity, ...$this->bornesAnneeDuJour($this->now()));
                }
            }
            return $userEntity;
        }

        //todo: remplacer l'exception LDAP par une "utilisateur inconnu"
        $userEntry = $this->ldapService->searchUid($uid, ['uid', 'sn', 'givenname', 'mail', 'supannetuid']);

        $user = new Utilisateur();
        $user->setUid($userEntry[0]['uid'][0]);
        $user->setNom($userEntry[0]['sn'][0] ?? $userEntry[0]['uid'][0]);
        $user->setPrenom($userEntry[0]['givenname'][0] ?? $userEntry[0]['uid'][0]);
        $user->setEmail($userEntry[0]['mail'][0] ?? '');
        $user->setNumeroEtudiant($userEntry[0]['supannetuid'][0] ?? null);

        if ($creerSiNouveau) {
            $this->utilisateurRepository->save($user, true);
            if (null !== $user->getNumeroEtudiant()) {
                $this->majInscriptionsEtIdentite($user, ...$this->bornesAnneeDuJour($this->now()));
            }
        }

        return $user;
    }

    /**
     * @param string $term
     * @return Utilisateur[]|Generator
     */
    public function search(string $term, bool $etudiantsSeulement = false): array|Generator
    {
        //on cherche d'abord en local ce qui correspond
        $existants = $this->utilisateurRepository->search($term);

        //On cherche dans l'annuaire...possiblement pas connu en local!
        $searchString = '(&(eduPersonAffiliation=member)(|(ubxstatutcompte=ACTIF)(ubxstatutcompte=NOUVEAU))(|(uid=*' .
            $term . '*)(cn=*' . $term . '*)))';

        try {
            $entries = $this->ldapService->query($searchString, ['uid', 'sn', 'givenname', 'mail', 'supannetuid']);
        } catch (ErreurLdapException $e) {
            $this->logger->debug($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
            return []; //Relancer une exception pour prévenir ? Retourner quand même ceux présents en base?
        }
        foreach ($entries as $entry) {
            if ($etudiantsSeulement && !array_key_exists('supannetuid', $entry)) {
                continue;
            }
            if (array_key_exists($entry['uid'][0], $existants)) {
                yield $existants[$entry['uid'][0]];
                //en profiter pour une MAJ?
                continue;
            }
            $user = new Utilisateur();
            $user->setUid($entry['uid'][0]);
            $user->setNom($entry['sn'][0] ?? $entry['uid'][0]);
            $user->setPrenom($entry['givenname'][0] ?? $entry['uid'][0]);
            $user->setEmail($entry['mail'][0]);
            if (array_key_exists('supannetuid', $entry)) {
                $user->setNumeroEtudiant((int)$entry['supannetuid'][0]);
            }
            yield $user;
        }

    }

    /**
     * @param mixed $data
     * @return Utilisateur
     * @throws ErreurLdapException
     * @throws Exception
     */
    public function maj(\App\ApiResource\Utilisateur $data): Utilisateur
    {
        $entity = $this->parUid($data->uid);

        /**
         * mail/tel perso
         */
        $entity->setEmailPerso($data->emailPerso);
        $entity->setTelPerso($data->telPerso);
        $entity->setContactUrgence($data->contactUrgence);
        $entity->setNumeroAnonyme($data->numeroAnonyme);

        /**
         * Abonnements
         */
        $entity->setAbonneImmediat($data->abonneImmediat);
        $entity->setAbonneVeille($data->abonneVeille);
        $entity->setAbonneAvantVeille($data->abonneAvantVeille);
        $entity->setAbonneRecapHebdo($data->abonneRecapHebdo);

        /**
         * Services
         */
        $this->majServices($data->services, $entity);

        /**
         * Roles
         */
        $entity->setAdmin(in_array(Utilisateur::ROLE_ADMIN, $data->roles));
        $entity->setGestionnaire(in_array(Utilisateur::ROLE_GESTIONNAIRE, $data->roles));

        /**
         * Renfort : si ROLE_RENFORT, on ajoute le type événement !
         */
        if (in_array('ROLE_RENFORT', $data->roles)) {
            $typeEvenement = new TypeEvenement();
            $typeEvenement->id = \App\Entity\TypeEvenement::TYPE_RENFORT;
            $data->typesEvenements[] = $typeEvenement;
            $data->roles[] = 'ROLE_INTERVENANT';
            $this->messageBus->dispatch(new RoleUtilisateursModifiesMessage(Utilisateur::ROLE_RENFORT));
        }

        /**
         * Intervenants : si le role disparait, on archive, s'il apparait on crée, s'il réapparait on désarchive
         */
        $nouvelIntervenant = false;
        $now = $this->now();
        if (!in_array(Utilisateur::ROLE_INTERVENANT, $data->roles)) {
            if (null !== $entity->getIntervenant()) {
                $entity->getIntervenant()->setFin($now);
                $this->messageBus->dispatch(new RoleUtilisateursModifiesMessage(Utilisateur::ROLE_INTERVENANT));
            }
        } else {
            $finAnneeU = new DateTime(match (true) {
                    $now->format('m') > '08' => (int)$now->format('Y') + 1,
                    default => (int)$now->format('Y')
                } . '-08-31');

            if (null !== $data->intervenantFin) {
                $fin = $data->intervenantFin;
            } else {
                $fin = $finAnneeU;
            }
            if (null !== $data->intervenantDebut) {
                $debut = $data->intervenantDebut;
            } else {
                $debut = $now;
            }

            if (null === $entity->getIntervenant()) {
                //création
                $intervenant = new Intervenant();
                $intervenant->setDebut($debut);
                $intervenant->setFin($fin);
                $entity->setAbonneRecapHebdo(true);//abonnement par défaut!
                $entity->setIntervenant($intervenant);
                $nouvelIntervenant = true;
                $this->messageBus->dispatch(new RoleUtilisateursModifiesMessage(Utilisateur::ROLE_INTERVENANT));
            } else {
                //réactivation / modif des dates
                $intervenant = $entity->getIntervenant();
                if ($intervenant->isArchive()) {
                    //redéfinir le début aussi?
                    $intervenant->setFin($intervenant->getFin() !== $fin ? $fin : $finAnneeU);
                    $nouvelIntervenant = true;
                } else {
                    $intervenant->setDebut($debut);
                    $intervenant->setFin($fin);
                }
                $this->messageBus->dispatch(new RoleUtilisateursModifiesMessage(Utilisateur::ROLE_INTERVENANT));
            }

            $this->majTypesEvenements($data->typesEvenements ?? [], $intervenant);
            $this->majCampus($data->campus ?? [], $intervenant);
            $this->majCompetences($data->competences ?? [], $intervenant);
        }

        /**
         * Bénéficiaire ajouté directement via le rôle?
         */
        $bornesAnnee = $this->bornesAnneeDuJour($this->now());
        if (!in_array(Utilisateur::ROLE_BENEFICIAIRE, $entity->getRoles()) &&
            in_array(Utilisateur::ROLE_BENEFICIAIRE, $data->roles)) {
            //on ajoute un profil 'à remplir'
            $beneficiaire = new Beneficiaire();
            $beneficiaire->setGestionnaire($this->security->getUser());
            $beneficiaire->setProfil($this->profilBeneficiaireRepository->find(ProfilBeneficiaire::A_DETERMINER));
            $beneficiaire->setDebut($bornesAnnee['debut']);
            $beneficiaire->setFin($bornesAnnee['fin']);
            $entity->addBeneficiaire($beneficiaire);
            $entity->setAbonneRecapHebdo(true);
            $this->messageBus->dispatch(new RoleUtilisateursModifiesMessage(Utilisateur::ROLE_BENEFICIAIRE));
        }

        $this->utilisateurRepository->save($entity, true);

        if (null !== $entity->getNumeroEtudiant()) {
            $this->majInscriptionsEtIdentite($entity, $bornesAnnee['debut'], $bornesAnnee['fin']);
        }

        if ($nouvelIntervenant) {
            $this->messageBus->dispatch(new CreationIntervenantMessage($entity));
        }

        return $entity;
    }

    /**
     * @param array       $resources
     * @param Utilisateur $entity
     * @return void
     */
    protected function majServices(array $resources, Utilisateur $entity): void
    {
        if ((count($resources) > 0 && $entity->getServices()->count() == 0) ||
            (count($resources) == 0 && $entity->getServices()->count() > 0)) {
            $this->messageBus->dispatch(new RoleUtilisateursModifiesMessage(Utilisateur::ROLE_GESTIONNAIRE));
        }

        foreach ($resources as $serviceResource) {
            $service = $this->serviceRepository->find($serviceResource->id);
            if (!$entity->getServices()->contains($service)) {
                $entity->addService($service);
            }
        }
        foreach ($entity->getServices() as $serviceEntity) {
            foreach ($resources as $serviceResource) {
                if ($serviceResource->id === $serviceEntity->getId()) {
                    continue 2;
                }
            }
            $entity->removeService($serviceEntity);
        }
    }

    /**
     * @param             $resources
     * @param Intervenant $intervenant
     * @return void
     */
    private function majTypesEvenements($resources, Intervenant $intervenant): void
    {
        foreach ($resources ?? [] as $typeResource) {
            $type = $this->typeEvenementRepository->find($typeResource->id);
            if (!$intervenant->getTypesEvenements()->contains($type)) {
                $intervenant->addTypesEvenement($type);
            }
        }
        foreach ($intervenant->getTypesEvenements() as $typeEvenement) {
            foreach ($resources as $typeResource) {
                if ($typeEvenement->getId() === $typeResource->id) {
                    continue 2;
                }
            }
            $intervenant->removeTypesEvenement($typeEvenement);
        }
    }

    /**
     * @param             $resources
     * @param Intervenant $intervenant
     * @return void
     */
    private function majCampus($resources, Intervenant $intervenant): void
    {
        foreach ($resources ?? [] as $resource) {
            $campus = $this->campusRepository->find($resource->id);
            if (!$intervenant->getCampuses()->contains($campus)) {
                $intervenant->addCampus($campus);
            }
        }
        foreach ($intervenant->getCampuses() as $campus) {
            foreach ($resources as $resource) {
                if ($campus->getId() === $resource->id) {
                    continue 2;
                }
            }
            $intervenant->removeCampus($campus);
        }
    }

    /**
     * @param             $resources
     * @param Intervenant $intervenant
     * @return void
     */
    private function majCompetences($resources, Intervenant $intervenant): void
    {
        foreach ($resources ?? [] as $resource) {
            $competence = $this->competenceRepository->find($resource->id);
            if (!$intervenant->getCompetences()->contains($competence)) {
                $intervenant->addCompetence($competence);
            }
        }
        foreach ($intervenant->getCompetences() as $competence) {
            foreach ($resources as $resource) {
                if ($competence->getId() === $resource->id) {
                    continue 2;
                }
            }
            $intervenant->removeCompetence($competence);
        }
    }


    /**
     * @param Utilisateur             $utilisateur
     * @param ProfilBeneficiaire|null $profil
     * @param DateTimeInterface       $debut
     * @param DateTimeInterface|null  $fin
     * @param int|null                $id
     * @param Utilisateur|null        $gestionnaire
     * @param TypologieHandicap[]     $typologies
     * @return Beneficiaire
     * @throws BeneficiaireInconnuException
     */
    public function majBeneficiaires(Utilisateur       $utilisateur, ?ProfilBeneficiaire $profil,
                                     DateTimeInterface $debut, ?DateTimeInterface $fin,
                                     ?int              $id = null,
                                     ?Utilisateur      $gestionnaire = null,
                                     array             $typologies = [],
                                     bool              $avecAccompagnement = true): Beneficiaire
    {
        if (null === $gestionnaire) {
            $gestionnaire = $this->security->getUser();
        }

        if (null === $id) {
            $beneficiaire = new Beneficiaire();
        } else {
            foreach ($utilisateur->getBeneficiaires() as $existant) {
                if ($existant->getId() === $id) {
                    $beneficiaire = $existant;
                }
            }
            if (!isset($beneficiaire)) {
                throw new BeneficiaireInconnuException($utilisateur, $id);
            }
        }
        $beneficiaire->setDebut($debut);
        $beneficiaire->setFin($fin);
        $beneficiaire->setProfil($profil);
        $beneficiaire->setGestionnaire($gestionnaire);
        $beneficiaire->setAvecAccompagnement($avecAccompagnement);

        if (null === $id) {
            $utilisateur->addBeneficiaire($beneficiaire);
            $utilisateur->setAbonneRecapHebdo(true);//pertinent?
        }

        /**
         * Typologies
         */
        //suppressions
        foreach ($beneficiaire->getTypologies() as $existant) {
            if (!in_array($existant, $typologies)) {
                $beneficiaire->removeTypology($existant);
            }
        }
        //ajouts
        foreach ($typologies as $typo) {
            if (!$beneficiaire->getTypologies()->contains($typo)) {
                $beneficiaire->addTypology($typo);
            }
        }

        $this->utilisateurRepository->save($utilisateur, true);

        //on permet de déclencher la maj des inscriptions
        $this->majInscriptionsEtIdentite($utilisateur, $debut, $fin);


        return $beneficiaire;
    }

    /**
     * @param Utilisateur $utilisateur
     * @param int         $id
     * @return void
     * @throws BeneficiaireInconnuException
     */
    public function supprimerBeneficiaire(Utilisateur $utilisateur, int $id): void
    {
        foreach ($utilisateur->getBeneficiaires() as $beneficiaire) {
            if ($beneficiaire->getId() === $id) {
                $utilisateur->removeBeneficiaire($beneficiaire);
                $this->utilisateurRepository->save($utilisateur, true);
                return;
            }
        }
        throw new BeneficiaireInconnuException($utilisateur, $id);
    }

    /**
     * @param Utilisateur            $utilisateur
     * @param DateTime               $debut
     * @param DateTimeInterface|null $fin
     * @return void
     */
    public function majInscriptionsEtIdentite(Utilisateur $utilisateur, DateTimeInterface $debut, ?DateTimeInterface $fin): void
    {
        if (null === $utilisateur->getNumeroEtudiant() || null === $utilisateur->getId()) {
            return;
        }

        $this->logger->debug('MAJ des inscriptions pour ' . $utilisateur->getUid());

        try {
            $inscriptions = $this->scolProvider->getInscriptions($utilisateur, $debut, null);
        } catch (BackendUnavailableException) {
            $this->logger->info('Impossible de récupérer les données de scol pour ' . $utilisateur->getUid());
            return;
        }

        if (!empty($inscriptions)) {
            //on trie dans l'ordre chrono
            usort($inscriptions, fn($a, $b) => $a['debut'] <=> $b['debut']);

            $last = array_key_last($inscriptions);
            $utilisateur->setBoursier($inscriptions[$last]['boursier'] ?? false);
            $utilisateur->setStatutEtudiant($inscriptions[$last]['statut'] ?? '');
        }

        //supprimer les disparues
        foreach ($utilisateur->getInscriptions() as $existante) {
            foreach ($inscriptions as $id => $inscription) {
                if ($inscription['codeComposante'] === $existante->getFormation()->getComposante()->getCodeExterne() &&
                    $inscription['codeFormation'] === $existante->getFormation()->getCodeExterne() &&
                    $inscription['debut'] == $existante->getDebut() &&
                    $inscription['fin'] == $existante->getFin()) {
                    //trouvée, on passe son chemin
                    unset($inscriptions[$id]);
                    if (null === $existante->getFormation()->getDiplome()) {
                        //rattrapage pour bilan activité
                        $formation = $this->formationManager->getFormation(codeFormation : $inscription['codeFormation'],
                                                                           libFormation  : $inscription['libFormation'],
                                                                           codeComposante: $inscription['codeComposante'],
                                                                           libComposante : $inscription['libComposante'],
                                                                           niveau        : $inscription['niveau'],
                                                                           discipline    : $inscription['discipline'],
                                                                           diplome       : $inscription['diplome']);
                    }
                    continue 2;
                }
            }
            //pas trouvée...
            $utilisateur->removeInscription($existante);
        }
        //ajouter les nouvelles
        foreach ($inscriptions as $inscription) {
            $new = new Inscription();
            $formation = $this->formationManager->getFormation(codeFormation : $inscription['codeFormation'],
                                                               libFormation  : $inscription['libFormation'],
                                                               codeComposante: $inscription['codeComposante'],
                                                               libComposante : $inscription['libComposante'],
                                                               niveau        : $inscription['niveau'],
                                                               discipline    : $inscription['discipline'],
                                                               diplome       : $inscription['diplome']);
            $new->setDebut($inscription['debut'])
                ->setFin($inscription['fin'])
                ->setFormation($formation);

            $utilisateur->addInscription($new);
        }
        $this->utilisateurRepository->save($utilisateur, true);
    }

    /**
     * @param string $role
     * @return Utilisateur[]
     */
    public function parRole(string $role): array
    {
        return array_reduce(
            array   : $this->utilisateurRepository->findAll(),
            callback: function ($carry, Utilisateur $utilisateur) use ($role) {
                if (in_array($role, $utilisateur->getRoles())) {
                    $carry[] = $utilisateur;
                }
                return $carry ?? [];
            }
        );
    }

    /**
     * @param Demande $demande
     * @param ?int    $idProfil
     * @param string  $uidGestionnaire
     * @return Beneficiaire
     * @throws BeneficiaireInconnuException
     * @throws ErreurLdapException
     */
    public function creerBeneficiairePourDemande(Demande $demande, ?int $idProfil, string $uidGestionnaire): Beneficiaire
    {
        if (null !== $idProfil) {
            $profil = $this->profilBeneficiaireRepository->find($idProfil);
        } else {
            //le profil est défini en amont?
            if (null !== $demande->getProfilAttribue()) {
                $profil = $demande->getProfilAttribue();
            } else {
                //on a un seul profil lié au type de demande?
                $profils = $demande->getCampagne()->getTypeDemande()->getProfilsAssocies();
                if ($profils->count() !== 1) {
                    throw new RuntimeException('Impossible de déterminer le profil à attribuer');
                }
                $profil = $profils->current();
            }
        }
        $utilisateur = $demande->getDemandeur();
        $anneeCible = $demande->getCampagne()->getAnneeCible();
        $annee = match ($anneeCible) {
            null => $this->bornesAnneeDuJour($this->now()), //pas d'info, on prend l'année en cours
            default => $this->bornesAnneeDuJour(new DateTimeImmutable($anneeCible . '-09-02'))
        };

        $gestionnaire = $this->parUid($uidGestionnaire);
        $typologies = $this->demandeManager->getTypologiesHandicap($demande);

        //Accompagnement : si le type le rend optionnel, quelle réponse à la question?
        $avecAccompagnement = $this->demandeManager->demandeAvecAccompagnement($demande);

        //let's go!
        $beneficiaire = $this->majBeneficiaires(
            utilisateur       : $utilisateur,
            profil            : $profil,
            debut             : $this->now(),
            fin               : $annee['fin'],
            gestionnaire      : $gestionnaire,
            typologies        : $typologies,
            avecAccompagnement: $avecAccompagnement
        );

        $demande->setBeneficiaire($beneficiaire);

        //On copie les données récupérables dans la demande
        $this->copierDonneesDemande($demande);

        $this->utilisateurRepository->save($beneficiaire->getUtilisateur(), true);

        return $beneficiaire;
    }

    /**
     * @param Utilisateur $utilisateur
     * @param Tag         $tag
     * @return void
     * @throws UtilisateurNonBeneficiaireException
     */
    public function ajouterTag(Utilisateur $utilisateur, Tag $tag): void
    {
        $beneficiaires = $utilisateur->getBeneficiairesActifs();
        if (count($beneficiaires) == 0) {
            throw new UtilisateurNonBeneficiaireException("Pas de bénéficiaire actif pour cet utilisateur");
        }
        foreach ($beneficiaires as $beneficiaire) {
            $beneficiaire->addTag($tag);
        }
        $this->utilisateurRepository->save($utilisateur, true);
    }

    /**
     * @param Utilisateur $utilisateur
     * @param Tag         $tag
     * @return void
     */
    public function supprimerTag(Utilisateur $utilisateur, Tag $tag)
    {
        foreach ($utilisateur->getBeneficiairesActifs() as $benef) {
            $benef->removeTag($tag);
        }
        $this->utilisateurRepository->save($utilisateur, true);
    }

    /**
     * On parcourt les réponses de l'utilisateur pour cette campagne,
     * pour toutes les questions avec champ cible on copie.
     *
     * On relie également d'office toutes les PJ.
     *
     * @param Demande $demande
     * @return void
     */
    private function copierDonneesDemande(Demande $demande)
    {
        $reponses = $this->reponseRepository->getReponsesARecuperer($demande);

        $utilisateur = $demande->getDemandeur();

        foreach ($reponses as $reponse) {
            switch ($reponse->getQuestion()->getChampCible()) {
                case Question::CHAMP_CIBLE_TEL_PERSO:
                    $utilisateur->setTelPerso($reponse->getCommentaire());
                    break;
                case Question::CHAMP_CIBLE_EMAIL_PERSO:
                    $utilisateur->setEmailPerso($reponse->getCommentaire());
                    break;
                case Question::CHAMP_CONTACT_URGENCE:
                    $utilisateur->setContactUrgence($reponse->getCommentaire());
                    break;
                default:
                    if ($reponse->getPiecesJustificatives()->isEmpty()) {
                        $this->logger->warning('valeur incorrecte pour champ_cible : ' . $reponse->getQuestion()->getChampCible());
                        break;
                    }
                    //fichier(s)!
                    foreach ($reponse->getPiecesJustificatives() as $piece) {
                        $pj = new PieceJointeBeneficiaire();
                        $pj->setUtilisateurCreation($utilisateur);
                        $pj->setFichier($piece);
                        $pj->setLibelle($reponse->getQuestion()->getLibelle());
                        $pj->setDateDepot($demande->getDateDepot());
                        $utilisateur->addPiecesJointe($pj);
                    }
            }
        }

        $this->utilisateurRepository->save($utilisateur, true);
    }

    public function initNumeroAnonyme(Utilisateur $utilisateur): Utilisateur
    {
        $numero = ($this->now()->format('Y')) . str_pad($utilisateur->getId(), 4, '0', STR_PAD_LEFT);
        $utilisateur->setNumeroAnonyme((int)$numero);
        $this->utilisateurRepository->save($utilisateur, true);

        return $utilisateur;
    }


}