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

namespace App\MessageHandler;

use App\Entity\DecisionAmenagementExamens;
use App\Entity\Fichier;
use App\Entity\PieceJointeBeneficiaire;
use App\Message\DecisionEditionDemandeeMessage;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Repository\PieceJointeBeneficiaireRepository;
use App\Serializer\DecisionAmenagementEditionNormalizer;
use App\Serializer\Encoder\PdfEncoder;
use App\Service\FileStorage\StorageProviderInterface;
use App\Service\MailService;
use App\State\TransformerService;
use App\State\Utilisateur\UtilisateurManager;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler(handles: DecisionEditionDemandeeMessage::class)]
readonly class DecisionEditionDemandeeMessageHandler
{
    use ClockAwareTrait;

    public function __construct(private DecisionAmenagementExamensRepository $decisionAmenagementExamensRepository,
                                private DecisionAmenagementEditionNormalizer $decisionAmenagementEditionNormalizer,
                                private PieceJointeBeneficiaireRepository    $pieceJointeBeneficiaireRepository,
                                private UtilisateurManager                   $utilisateurManager,
                                private TransformerService                   $transformerService,
                                private PdfEncoder                           $pdfEncoder,
                                private MailService                          $mailService,
                                private LoggerInterface                      $logger,
                                private StorageProviderInterface             $storageProvider,
                                private MessageBusInterface                  $messageBus)
    {

    }

    public function __invoke(DecisionEditionDemandeeMessage $message): void
    {
        $decision = $this->decisionAmenagementExamensRepository->find(($message->getIdDecision()));
        if (null === $decision) {
            return;
        }

        $resource = $this->transformerService->transform($decision,
            \App\ApiResource\DecisionAmenagementExamens::class);

        $normalized = $this->decisionAmenagementEditionNormalizer->normalize($resource);
        try {
            $pdf = $this->pdfEncoder->encode($normalized, 'pdf');
            $this->mailService->envoyerDecision($decision, $pdf);
            $decision->setEtat(DecisionAmenagementExamens::ETAT_EDITE);
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->info($e->getTraceAsString());
            $delay = new DelayStamp(3600000); //on réessaye dans une heure
            $this->messageBus->dispatch(new RedispatchMessage($message), [$delay]);
            return;
        }


        //on stocke une copie dans le dossier de l'étudiant
        try {
            $filename = 'decision-' . $decision->getId() . '.pdf';
            $mimeType = 'application/pdf';
            $dateEnvoi = $this->now();
            $description = "Décision d'aménagements au " . $dateEnvoi->format('d/m/Y');
            $metadata = $this->storageProvider->store(
                contents: $pdf,
                filename: $filename,
                mimeType: $mimeType,
                description: $description
            );
            $fichier = new Fichier();
            $fichier->setNom($filename);
            $fichier->setProprietaire($decision->getBeneficiaire());
            $fichier->setMetadata($metadata);
            $fichier->setTypeMime($mimeType);
            $decision->setFichier($fichier);
            $pieceJointe = new PieceJointeBeneficiaire();
            $pieceJointe->setFichier($fichier);
            $pieceJointe->setBeneficiaire($decision->getBeneficiaire());
            $pieceJointe->setUtilisateurCreation($this->utilisateurManager->parUid($message->getUidDemandeur()));
            $pieceJointe->setDateDepot($dateEnvoi);
            $pieceJointe->setLibelle($description);
            $this->pieceJointeBeneficiaireRepository->save($pieceJointe, true);
        } catch (Exception) {
            $this->logger->error('Erreur d\'enregistrement de la copie pdf de la décision');
        }

        $decision->setEtat(DecisionAmenagementExamens::ETAT_EDITE);
        $this->decisionAmenagementExamensRepository->save($decision, true);
    }

}