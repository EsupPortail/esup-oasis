<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\MessageHandler;

use App\ApiResource\DecisionAmenagementExamens as DecisionResource;
use App\Entity\DecisionAmenagementExamens;
use App\Message\SignatureDecisionDemandeeMessage;
use App\Repository\DecisionAmenagementExamensRepository;
use App\Serializer\DecisionAmenagementEditionNormalizer;
use App\Serializer\Encoder\PdfEncoder;
use App\Service\Signature\SignatureElectronique;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Génère le PDF de la décision et le dépose dans le circuit de sa composante. L'état EDITE et la copie au
 * dossier viendront du suivi de signature.
 */
#[AsMessageHandler(handles: SignatureDecisionDemandeeMessage::class)]
readonly class SignatureDecisionDemandeeMessageHandler
{
    public function __construct(
        private DecisionAmenagementExamensRepository $decisionAmenagementExamensRepository,
        private DecisionAmenagementEditionNormalizer $decisionAmenagementEditionNormalizer,
        private PdfEncoder $pdfEncoder,
        private SignatureElectronique $signatureElectronique,
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus,
    ) {}

    public function __invoke(SignatureDecisionDemandeeMessage $message): void
    {
        $decision = $this->decisionAmenagementExamensRepository->find($message->getIdDecision());

        // message rejoué alors que le document est déjà dans le parapheur : ne pas le déposer deux fois
        if (null === $decision
            || DecisionAmenagementExamens::ETAT_EN_SIGNATURE !== $decision->getEtat()
            || null !== $decision->getIdDocumentParapheur()) {
            return;
        }

        // circuit retiré de la composante, ou parapheur désactivé, depuis la demande : l'administrateur l'enverra
        $circuit = $this->signatureElectronique->circuitPour($decision);
        if (null === $circuit) {
            $this->logger->warning(
                'Décision {id} : plus de circuit de signature au moment du dépôt, elle repasse à l\'état validé.',
                ['id' => $decision->getId()],
            );
            $decision->setEtat(DecisionAmenagementExamens::ETAT_VALIDE);
            $this->decisionAmenagementExamensRepository->save($decision, true);

            return;
        }

        $normalized = $this->decisionAmenagementEditionNormalizer->normalize(new DecisionResource($decision));
        // le gabarit peut s'adapter au document signé, par exemple sans signature scannée
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
    }
}
