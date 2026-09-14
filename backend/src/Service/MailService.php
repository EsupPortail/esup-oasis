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

namespace App\Service;

use App\Entity\DecisionAmenagementExamens;
use App\Entity\Evenement;
use App\Entity\Parametre;
use App\Entity\PeriodeRH;
use App\Entity\ProfilBeneficiaire;
use App\Entity\TypeDemande;
use App\Entity\Utilisateur;
use App\Entity\ValeurParametre;
use App\Message\ErreurTechniqueMessage;
use App\Repository\EvenementRepository;
use App\Repository\ParametreRepository;
use App\Repository\UtilisateurRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class MailService
{
    public function __construct(
        private MailerInterface $mailer,
        private ParametreService $parametreService,
        private UtilisateurRepository $utilisateurRepository,
        private LoggerInterface $logger,
        private EvenementRepository $evenementRepository,
    ) {}

    /**
     * @param Evenement[] $evenements
     * @return void
     */
    public function envoyerRappelIntervenant(array $evenements): void
    {
        $utilisateurConcerne = $evenements[0]->getIntervenant()->getUtilisateur();
        $nomAffichage = $this->nomAffichage($utilisateurConcerne);

        $this->envoyerRappelIndividuel($utilisateurConcerne, $nomAffichage, 'mail/rappelIntervenant.html.twig', [
            'evenements' => $evenements,
            'intervenant' => $utilisateurConcerne,
        ]);
    }

    /**
     * @param Utilisateur $beneficiaire
     * @param Evenement[] $evenements
     * @return void
     */
    public function envoyerRappelBeneficiaire(Utilisateur $beneficiaire, array $evenements): void
    {
        $nomAffichage = $this->nomAffichage($beneficiaire);

        $this->envoyerRappelIndividuel($beneficiaire, $nomAffichage, 'mail/rappelBeneficiaire.html.twig', [
            'evenements' => $evenements,
            'beneficiaire' => $beneficiaire,
        ]);
    }

    /**
     * @param Utilisateur[] $traites
     * @param Utilisateur[] $nonTraites
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function envoyerRapportMajInscriptions(array $traites, array $nonTraites): void
    {
        $destinataires = $this->utilisateurRepository->findBy([
            'destinataireTechnique' => true,
        ]);

        $subject = '[' . $this->getNomApplication() . ']  Traitement de MAJ des inscriptions';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to(...array_map(
                fn($destinataire) => new Address($destinataire->getEmail(), $this->nomAffichage($destinataire)),
                $destinataires,
            ))
            ->htmlTemplate('mail/rapportMajInscriptions.html.twig')
            ->context(['traites' => $traites, 'nonTraites' => $nonTraites, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi du rapport de MAJ des inscriptions");
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    /**
     * @param PeriodeRH|null $periodeRH
     * @return void
     */
    public function envoyerRappelsEnvoiRH(?PeriodeRH $periodeRH): void
    {
        $destinataires = array_reduce(
            array: $this->evenementRepository->findAllNotLockedBefore($periodeRH->getFin()),
            callback: function ($carry, Evenement $evenement) {
                if (null === $evenement->getIntervenant()) {
                    return $carry;
                }
                $email = $evenement->getIntervenant()->getUtilisateur()->getEmail();
                if (!in_array($email, $carry)) {
                    $carry[] = $email;
                }
                return $carry;
            },
            initial: [],
        );

        //copie aux admins tech dans le doute.
        $destinatairesTechniques = array_map(
            fn($utilisateur) => new Address($utilisateur->getEmail(), $this->nomAffichage($utilisateur)),
            $this->utilisateurRepository->findBy([
                'destinataireTechnique' => true,
            ]),
        );

        $subject = '[' . $this->getNomApplication() . ']  La fin de période approche';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->bcc(...$destinataires, ...$destinatairesTechniques)
            ->htmlTemplate('mail/rappelEnvoiRH.html.twig')
            ->context(['periode' => $periodeRH, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
            $this->logger->info('Mail envoi RH J-4 envoyé. Destinataires : ' . implode($destinataires));
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi du rappel aux renforts");
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    /**
     * Rappel envoyé aux gestionnaires : validez/annulez les interventions de vos renforts!
     *
     * @param PeriodeRH|null $periodeRH
     * @return void
     */
    public function envoyerRappelValidationInterventionsRenforts(?PeriodeRH $periodeRH): void
    {
        $destinataires = array_map(
            fn($utilisateur) => new Address($utilisateur->getEmail(), $this->nomAffichage($utilisateur)),
            array_filter(
                $this->utilisateurRepository->findAll(),
                fn(Utilisateur $utilisateur) => $utilisateur->isGestionnaire(),
            ),
        );

        //copie aux admins tech dans le doute.
        $destinatairesTechniques = array_map(
            fn($utilisateur) => new Address($utilisateur->getEmail(), $this->nomAffichage($utilisateur)),
            $this->utilisateurRepository->findBy([
                'destinataireTechnique' => true,
            ]),
        );

        $subject = '[' . $this->getNomApplication() . ']  La fin de période approche';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->bcc(...$destinataires, ...$destinatairesTechniques)
            ->htmlTemplate('mail/rappelValidationInterventionsRenforts.html.twig')
            ->context(['periode' => $periodeRH, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
            $this->logger->info(
                'Mail envoi validation renforts J-4 envoyé. Destinataires : '
                    . implode(', ', array_map(fn(Address $des) => $des->getAddress(), $destinataires)),
            );
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi du rappel de validation des interventions des renforts");
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    /**
     * @param Utilisateur|null $utilisateurConcerne
     * @return string
     */
    protected function nomAffichage(?Utilisateur $utilisateurConcerne): string
    {
        return ucfirst($utilisateurConcerne->getPrenom()) . ' ' . ucfirst($utilisateurConcerne->getNom());
    }

    /**
     * @return string
     */
    protected function getEmailExpediteur(): string
    {
        $expediteur = $this->parametreService->valeur(Parametre::EXPEDITEUR_EMAILS);

        return $expediteur ?? 'noreply@u-bordeaux.fr';
    }

    protected function getNomApplication(): string
    {
        return $this->parametreService->valeur(Parametre::APP_TITRE) ?? 'Oasis';
    }

    protected function getNomServiceAvecArticle(): string
    {
        $nomService = $this->parametreService->valeur(Parametre::APP_SERVICE_DENOMINATION) ?? 'service Phase';
        $articleService = $this->parametreService->valeur(Parametre::APP_SERVICE_ARTICLE) ?? 'le';

        return match ($articleService) {
            "l'" => $articleService,
            default => $articleService . ' ',
        } . $nomService;
    }

    /**
     * @param Utilisateur|null $utilisateurConcerne
     * @param string $nomAffichage
     * @param string $template
     * @param array $context
     * @return void
     */
    protected function envoyerRappelIndividuel(
        ?Utilisateur $utilisateurConcerne,
        string $nomAffichage,
        string $template,
        array $context,
    ): void {
        $subject = '[' . $this->getNomApplication() . ']  Récapitulatif de vos événements de la semaine';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to(new Address($utilisateurConcerne->getEmail(), $nomAffichage))
            ->htmlTemplate($template)
            ->context([...$context, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi du rappel à " . $utilisateurConcerne->getUid());
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerMailTest(): void
    {
        $destinataires = $this->utilisateurRepository->findBy([
            'destinataireTechnique' => true,
        ]);

        $subject = '[' . $this->getNomApplication() . ']  Test de mail';

        $email = new Email()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to(...array_map(
                fn($destinataire) => new Address($destinataire->getEmail(), $this->nomAffichage($destinataire)),
                $destinataires,
            ))
            ->text('Simple mail de test');

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi du mail de test");
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    /**
     * Mail de bienvenue sur la plateforme pour les intervenants
     *
     * @param Utilisateur $intervenant
     * @return void
     */
    public function envoyerMessageBienvenue(Utilisateur $intervenant): void
    {
        $destinataire = new Address($intervenant->getEmail(), $this->nomAffichage($intervenant));

        $nomApplication = $this->getNomApplication();
        $subject = '[' . $nomApplication . ']  Bienvenue sur la plateforme ' . $nomApplication;

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to($destinataire)
            ->htmlTemplate('mail/bienvenueIntervenant.html.twig')
            ->context(['intervenant' => $intervenant, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi du mail de bienvenue à " . $intervenant->getUid());
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    /**
     * @param Utilisateur $demandeur
     * @param TypeDemande $typeDemande
     * @return void
     */
    public function envoyerConfirmationDemandeReceptionnee(Utilisateur $demandeur, TypeDemande $typeDemande): void
    {
        $destinataire = new Address($demandeur->getEmail(), $this->nomAffichage($demandeur));

        $subject = '[' . $this->getNomApplication() . '] Demande réceptionnée';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to($destinataire)
            ->htmlTemplate('mail/confirmationDemandeReceptionnee.html.twig')
            ->context(['typeDemande' => $typeDemande, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                "Erreur lors de l'envoi de l'accusé de réception de la demande de " . $demandeur->getUid(),
            );
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerMessageDemandeNonConforme(
        Utilisateur $demandeur,
        TypeDemande $typeDemande,
        ?string $commentaire,
    ): void {
        $destinataire = new Address($demandeur->getEmail(), $this->nomAffichage($demandeur));

        $subject = '[' . $this->getNomApplication() . '] Demande non conforme';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to($destinataire)
            ->htmlTemplate('mail/demandeIncomplete.html.twig')
            ->context(['typeDemande' => $typeDemande, 'commentaire' => $commentaire, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                "Erreur lors de l'envoi de la notification de non conformité de la demande de " . $demandeur->getUid(),
            );
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerConfirmationDemandeValidee(Utilisateur $demandeur, TypeDemande $typeDemande): void
    {
        $destinataire = new Address($demandeur->getEmail(), $this->nomAffichage($demandeur));

        $subject = '[' . $this->getNomApplication() . '] Demande validée';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to($destinataire)
            ->htmlTemplate('mail/demandeValidee.html.twig')
            ->context(['typeDemande' => $typeDemande, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                "Erreur lors de l'envoi de la notification de validité de la demande de " . $demandeur->getUid(),
            );
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerNotificationRefusDemande(
        Utilisateur $demandeur,
        TypeDemande $typeDemande,
        ?string $commentaire,
    ): void {
        $destinataire = new Address($demandeur->getEmail(), $this->nomAffichage($demandeur));

        $subject = '[' . $this->getNomApplication() . '] Demande refusée';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to($destinataire)
            ->htmlTemplate('mail/demandeRefusee.html.twig')
            ->context(['typeDemande' => $typeDemande, 'commentaire' => $commentaire, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                "Erreur lors de l'envoi de la notification de refus de la demande de " . $demandeur->getUid(),
            );
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerConfirmationDemandeStatutValide(
        Utilisateur $demandeur,
        TypeDemande $typeDemande,
        ProfilBeneficiaire $profil,
        bool $avecAccompagnement,
    ): void {
        $destinataire = new Address($demandeur->getEmail(), $this->nomAffichage($demandeur));

        $subject = '[' . $this->getNomApplication() . '] Statut validé';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to($destinataire)
            ->htmlTemplate('mail/demandeStatutValide.html.twig')
            ->context([
                'typeDemande' => $typeDemande,
                'profil' => $profil,
                'accompagnement' => $avecAccompagnement,
                ...$this->appEnvs(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                "Erreur lors de l'envoi de la notification de statut validé de la demande de " . $demandeur->getUid(),
            );
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerCharteAValider(Utilisateur $demandeur, TypeDemande $typeDemande): void
    {
        $destinataire = new Address($demandeur->getEmail(), $this->nomAffichage($demandeur));

        $subject = '[' . $this->getNomApplication() . '] Veuillez valider la charte';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to($destinataire)
            ->htmlTemplate('mail/demandeCharteAValider.html.twig')
            ->context(['typeDemande' => $typeDemande, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                "Erreur lors de l'envoi de la notification de charte à valider de la demande de "
                    . $demandeur->getUid(),
            );
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerPrendreContact(Utilisateur $demandeur, TypeDemande $typeDemande): void
    {
        $destinataire = new Address($demandeur->getEmail(), $this->nomAffichage($demandeur));

        $leService = $this->getNomServiceAvecArticle();

        $subject = '[' . $this->getNomApplication() . '] Veuillez prendre contact avec ' . $leService;

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to($destinataire)
            ->htmlTemplate('mail/demandePrendreContact.html.twig')
            ->context(['typeDemande' => $typeDemande, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                "Erreur lors de l'envoi de la demande de prise de contact pour la demande de " . $demandeur->getUid(),
            );
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerMailErreurTechnique(ErreurTechniqueMessage $message): void
    {
        $destinataires = $this->utilisateurRepository->findBy([
            'destinataireTechnique' => true,
        ]);

        $subject = '[' . $this->getNomApplication() . '] Erreur technique';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to(...array_map(
                fn($destinataire) => new Address($destinataire->getEmail(), $this->nomAffichage($destinataire)),
                $destinataires,
            ))
            ->htmlTemplate('mail/erreurTechnique.html.twig')
            ->context([
                'message' => $message->getMessage(),
                'exceptionMessage' => $message->getExceptionMessage(),
                'trace' => $message->getTrace(),
                ...$this->appEnvs(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi du mail d'erreur technique");
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerDecision(DecisionAmenagementExamens $decision, string $pdf): void
    {
        $destinataire = $decision->getBeneficiaire();
        $destCopie = $destinataire->getBeneficiaires()->current()->getGestionnaire();

        $destBcc = $this->parametreService->valeur(Parametre::DESTINATAIRES_COPIE_DECISIONS, true);

        $subject = '[' . $this->getNomApplication() . ']  Décision d\'aménagements d\'examens';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to(new Address($destinataire->getEmail(), $this->nomAffichage($destinataire)))
            ->cc(new Address($destCopie->getEmail(), $this->nomAffichage($destCopie)))
            ->bcc(...$destBcc)
            ->htmlTemplate('mail/decisionAmenagement.html.twig')
            ->addPart(new DataPart($pdf, 'decision.pdf', 'application/pdf'))
            ->context(['decision' => $decision, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi du mail de décision d'aménagements");
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    public function envoyerRapportNettoyage(int $count, int $removed, int $errors): void
    {
        $destinataires = $this->utilisateurRepository->findBy([
            'destinataireTechnique' => true,
        ]);

        $subject = '[' . $this->getNomApplication() . '] Erreur technique';

        $email = new TemplatedEmail()
            ->from($this->getEmailExpediteur())
            ->subject($subject)
            ->to(...array_map(
                fn($destinataire) => new Address($destinataire->getEmail(), $this->nomAffichage($destinataire)),
                $destinataires,
            ))
            ->htmlTemplate('mail/rapportNettoyage.html.twig')
            ->context(['nb' => $count, 'removed' => $removed, 'errors' => $errors, ...$this->appEnvs()]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Erreur lors de l'envoi du raport de nettoyage");
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    private function appEnvs(): array
    {
        return $this->parametreService->getAppEnv();
    }
}
