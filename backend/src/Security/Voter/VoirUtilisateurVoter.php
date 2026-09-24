<?php

namespace App\Security\Voter;

use App\ApiResource\Demande;
use App\ApiResource\Utilisateur;
use App\Entity\ApplicationCliente;
use App\Entity\Beneficiaire;
use App\Entity\Evenement;
use App\Entity\InterventionForfait;
use App\Entity\MembreCommission;
use App\Repository\DemandeRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VoirUtilisateurVoter extends Voter
{
    public function __construct(
        private readonly Security $security,
        private readonly DemandeRepository $demandeRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Utilisateur::VOIR_UTILISATEUR;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        assert($attribute === Utilisateur::VOIR_UTILISATEUR);
        assert($subject instanceof Utilisateur);

        // les applications clientes voient tout le monde
        if ($this->security->isGranted(ApplicationCliente::ROLE_APPLICATION_CLIENTE)) {
            return true;
        }

        // on se voit soi-même
        if ($subject->uid === $token->getUserIdentifier()) {
            return true;
        }

        // les planificateurs voient tout le monde
        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_PLANIFICATEUR)) {
            return true;
        }

        // Tout le monde accédant à la plateforme peut voir les infos des gestionnaires - à affiner ?
        if (in_array(\App\Entity\Utilisateur::ROLE_GESTIONNAIRE, $subject->roles)) {
            return true;
        }

        $utilisateurCourant = $this->security->getUser();

        assert($utilisateurCourant instanceof \App\Entity\Utilisateur);

        // Référents de composantes
        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_REFERENT_COMPOSANTE)) {
            //on vérifie si l'utilisateur est inscrit sur la composante gérée
            $composantesUtilisateurCourant = $utilisateurCourant->getComposantes();
            $composantesEtudiant = array_reduce(
                $subject->inscriptions,
                fn($acc, $inscription) => [...$acc, $inscription->formation->composante->id],
                [],
            );
            foreach ($composantesUtilisateurCourant as $composanteUtilisateurCourant) {
                if (in_array($composanteUtilisateurCourant->getId(), $composantesEtudiant)) {
                    return true;
                }
            }
        }

        // Membres de commission - voient les étudiants de leurs commissions
        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_MEMBRE_COMMISSION)) {
            $commissionsAutorisees = array_map(fn(MembreCommission $commission) => $commission
                ->getCommission()
                ->getId(), $utilisateurCourant->getMembreCommissions()->toArray());

            $demandesEtudiant = $this->demandeRepository->findByUid($subject->uid);

            if (array_any(array: $demandesEtudiant, callback: fn($demande) => in_array(
                needle: $demande->getCampagne()->getCommission()?->getId(),
                haystack: $commissionsAutorisees,
            ))) {
                return true;
            }
        }

        //Intervenants : voient les bénéficiaires de leurs interventions/événements
        if ($this->security->isGranted(\App\Entity\Utilisateur::ROLE_INTERVENANT)) {
            $interventions = $utilisateurCourant->getIntervenant()->getInterventions();
            foreach ($interventions as $evenement) {
                assert($evenement instanceof Evenement);
                if (array_any(
                    $evenement->getBeneficiaires()->toArray(),
                    fn(Beneficiaire $benef) => $benef->getUtilisateur()->getUid() === $subject->uid,
                )) {
                    return true;
                }
            }
            $interventionsForfait = $utilisateurCourant->getIntervenant()->getInterventionsForfait();
            foreach ($interventionsForfait as $evenement) {
                assert($evenement instanceof InterventionForfait);
                if (array_any(
                    $evenement->getBeneficiaires()->toArray(),
                    fn(Beneficiaire $benef) => $benef->getUtilisateur()->getUid() === $subject->uid,
                )) {
                    return true;
                }
            }
        }

        // demandeur : on ne peut voir que les gens liés à ses événements (intervenant, enseignant...)
        $vote?->addReason('Un simple demandeur ne peut pas voir tous les autres utilisateurs.');
        return false;
    }
}
