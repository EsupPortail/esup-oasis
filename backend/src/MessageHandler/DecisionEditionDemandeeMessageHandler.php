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

namespace App\MessageHandler;

use App\ApiResource\DecisionAmenagementExamens as DecisionResource;
use App\ApiResource\Utilisateur;
use App\Entity\DecisionAmenagementExamens;
use App\Message\DecisionEditionDemandeeMessage;
use App\Message\RessourceModifieeMessage;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Serializer\DecisionAmenagementEditionNormalizer;
use App\Serializer\Encoder\PdfEncoder;
use App\Service\Decision\ArchivageDecision;
use App\Service\MailService;
use App\Service\Signature\SignatureElectronique;
use App\State\Utilisateur\UtilisateurManager;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler(handles: DecisionEditionDemandeeMessage::class)]
readonly class DecisionEditionDemandeeMessageHandler
{
    public function __construct(
        private DecisionAmenagementExamensRepository $decisionAmenagementExamensRepository,
        private DecisionAmenagementEditionNormalizer $decisionAmenagementEditionNormalizer,
        private UtilisateurManager $utilisateurManager,
        private PdfEncoder $pdfEncoder,
        private MailService $mailService,
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus,
        private ArchivageDecision $archivageDecision,
        private SignatureElectronique $signatureElectronique,
    ) {}

    public function __invoke(DecisionEditionDemandeeMessage $message): void
    {
        $decision = $this->decisionAmenagementExamensRepository->find($message->getIdDecision());
        if (null === $decision) {
            return;
        }

        // message rejoué alors que le document est déjà dans le parapheur : ne pas le déposer deux fois
        if ($this->signatureElectronique->estEnCours($decision)) {
            return;
        }

        $resource = new DecisionResource($decision);

        $normalized = $this->decisionAmenagementEditionNormalizer->normalize($resource);

        // composante reliée à un circuit : la décision part en signature au lieu de l'e-mail,
        // l'état EDITE et la copie au dossier viendront du suivi de signature
        $circuit = $this->signatureElectronique->circuitPour($decision);
        if (null !== $circuit) {
            $normalized['signature_electronique'] = true;
            try {
                $pdf = $this->pdfEncoder->encode($normalized, 'pdf');
                $this->signatureElectronique->deposer($decision, $pdf, $circuit, $message->getUidDemandeur());
            } catch (RuntimeException $e) {
                $this->logger->error($e->getMessage());
                $this->logger->info($e->getTraceAsString());
                // renvoyé dans sa file : un RedispatchMessage serait traité aussitôt, sans le délai
                $this->messageBus->dispatch($message, [new DelayStamp(3600000)]); //on réessaye dans une heure
            }
            return;
        }

        try {
            $pdf = $this->pdfEncoder->encode($normalized, 'pdf');
            $this->mailService->envoyerDecision($decision, $pdf);
            $decision->setEtat(DecisionAmenagementExamens::ETAT_EDITE);
            $this->messageBus->dispatch(new RessourceModifieeMessage(new Utilisateur($decision->getBeneficiaire())));
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->info($e->getTraceAsString());
            $delay = new DelayStamp(3600000); //on réessaye dans une heure
            $this->messageBus->dispatch(new RedispatchMessage($message), [$delay]);
            return;
        }

        //on stocke une copie dans le dossier de l'étudiant
        try {
            $this->archivageDecision->archiver(
                decision: $decision,
                pdf: $pdf,
                auteur: $this->utilisateurManager->parUid($message->getUidDemandeur()),
            );
        } catch (Exception) {
            $this->logger->error('Erreur d\'enregistrement de la copie pdf de la décision');
        }

        $this->decisionAmenagementExamensRepository->save($decision, true);
    }
}
