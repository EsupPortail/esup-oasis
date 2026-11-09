<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

namespace App\Service\Signature;

use App\Entity\DecisionAmenagementExamens;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * Parapheur factice pour le développement et les tests, absent en production. Ses documents sont
 * gardés dans un répertoire, partagé par l'API et le worker, ou en mémoire sans répertoire. La
 * commande app:signature:factice les fait avancer à la place des signataires.
 */
#[When(env: 'dev')]
#[When(env: 'test')]
class ParapheurFactice extends AbstractParapheur
{
    public const string ID = 'factice';

    /** @var array<string, array{pdf: string, circuit: string, libelle: string, destinataire: ?string, etat: string, dateSignature: ?string}> */
    private array $documents = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/parapheur-factice')]
        private readonly ?string $repertoire = null,
    ) {}

    public function getProviderId(): string
    {
        return self::ID;
    }

    public function deposer(string $pdf, string $circuit, string $libelle, ?string $destinataire): string
    {
        $documentId = uniqid('factice-', true);

        $this->enregistrer($documentId, [
            'pdf' => base64_encode($pdf),
            'circuit' => $circuit,
            'libelle' => $libelle,
            'destinataire' => $destinataire,
            'etat' => DecisionAmenagementExamens::ETAT_SIGNATURE_EN_SIGNATURE,
            'dateSignature' => null,
        ]);

        return $documentId;
    }

    public function suivre(string $documentId): SuiviSignature
    {
        $document = $this->document($documentId);

        return new SuiviSignature(
            $document['etat'],
            null === $document['dateSignature'] ? null : new DateTimeImmutable($document['dateSignature']),
        );
    }

    public function telecharger(string $documentId): string
    {
        // un vrai parapheur renvoie le PDF signé, ici le document déposé tel quel
        return base64_decode($this->document($documentId)['pdf']);
    }

    public function marquerSignee(string $documentId, ?DateTimeImmutable $dateSignature = null): void
    {
        $document = $this->document($documentId);
        $document['etat'] = DecisionAmenagementExamens::ETAT_SIGNATURE_SIGNEE;
        $document['dateSignature'] = ($dateSignature ?? new DateTimeImmutable())->format(DateTimeInterface::ATOM);
        $this->enregistrer($documentId, $document);
    }

    public function marquerRefusee(string $documentId): void
    {
        $document = $this->document($documentId);
        $document['etat'] = DecisionAmenagementExamens::ETAT_SIGNATURE_REFUSEE;
        $this->enregistrer($documentId, $document);
    }

    /**
     * @return array<string, array{circuit: string, libelle: string, destinataire: ?string, etat: string, dateSignature: ?string}>
     */
    public function documents(): array
    {
        $ids = null === $this->repertoire
            ? array_keys($this->documents)
            : array_map(fn(string $fichier) => basename($fichier, '.json'), glob($this->repertoire . '/factice-*.json') ?: []);

        $documents = [];
        foreach ($ids as $documentId) {
            $documents[$documentId] = array_diff_key($this->document($documentId), ['pdf' => true]);
        }

        return $documents;
    }

    /**
     * @return array{pdf: string, circuit: string, libelle: string, destinataire: ?string, etat: string, dateSignature: ?string}
     */
    private function document(string $documentId): array
    {
        if (null === $this->repertoire) {
            return $this->documents[$documentId] ?? throw new DocumentInconnuException($documentId);
        }

        // l'identifiant vient aussi de la ligne de commande : jamais de chemin hors du répertoire
        if (1 !== preg_match('/^factice-[0-9a-f.]+$/', $documentId) || !is_file($this->chemin($documentId))) {
            throw new DocumentInconnuException($documentId);
        }

        return json_decode(file_get_contents($this->chemin($documentId)), true, flags: JSON_THROW_ON_ERROR);
    }

    private function enregistrer(string $documentId, array $document): void
    {
        if (null === $this->repertoire) {
            $this->documents[$documentId] = $document;

            return;
        }

        if (!is_dir($this->repertoire)) {
            mkdir($this->repertoire, recursive: true);
        }
        file_put_contents($this->chemin($documentId), json_encode($document, JSON_THROW_ON_ERROR), LOCK_EX);
    }

    private function chemin(string $documentId): string
    {
        return $this->repertoire . '/' . $documentId . '.json';
    }
}
