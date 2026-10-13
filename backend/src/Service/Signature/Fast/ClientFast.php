<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\Signature\Fast;

use App\Service\Signature\DocumentInconnuException;
use App\Service\Signature\ParapheurException;
use SoapClient;
use SoapFault;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Appels SOAP à FAST-Parapheur, authentifiés par le certificat client de l'établissement (mTLS).
 * Le client SOAP n'est construit qu'au premier appel : sans certificat, rien ne se passe au démarrage.
 */
class ClientFast
{
    private const int DELAI_CONNEXION = 30;
    // lecture de la réponse, bornée par default_socket_timeout : un dépôt volumineux doit aboutir
    private const int DELAI_REPONSE = 120;

    private ?SoapClient $client;

    public function __construct(
        #[Autowire('%env(default::FAST_URL)%')]
        private readonly ?string $url,
        #[Autowire('%env(default::FAST_SIREN)%')]
        private readonly ?string $siren,
        #[Autowire('%env(default::FAST_CERTIFICAT)%')]
        private readonly ?string $certificat,
        #[Autowire('%env(default::FAST_CERTIFICAT_MOT_DE_PASSE)%')]
        private readonly ?string $motDePasse,
        #[Autowire('%env(default::FAST_AUTORITE)%')]
        private readonly ?string $autorite,
        ?SoapClient $client = null,
    ) {
        $this->client = $client;
    }

    /**
     * Dépose le PDF dans un circuit ; avec un destinataire, FAST lui enverra le document signé.
     *
     * @return string identifiant du document dans FAST
     *
     * @throws ParapheurException
     */
    public function deposer(string $pdf, string $nomFichier, string $circuit, string $libelle, ?string $destinataire): string
    {
        $depot = [
            'label' => $libelle,
            'comment' => '',
            'subscriberId' => $this->siren,
            'circuitId' => $circuit,
            'dataFileVO' => ['dataHandler' => $pdf, 'filename' => $nomFichier],
        ];
        // sans destinataire, la propriété est omise : un circuit sans envoi au destinataire la refuse
        if (null !== $destinataire) {
            $depot['email_destinataire'] = $destinataire;
        }
        $reponse = $this->appeler('upload', $depot);

        $documentId = trim((string) ($reponse->return ?? ''));
        if ('' === $documentId) {
            throw new ParapheurException('FAST n\'a pas renvoyé d\'identifiant de document au dépôt.');
        }

        return $documentId;
    }

    /**
     * Historique du document, dans l'ordre renvoyé par FAST.
     *
     * @return array<int, array{stateName: string, date: string}>
     *
     * @throws ParapheurException
     */
    public function historique(string $documentId): array
    {
        $reponse = $this->appeler('history', ['documentId' => $documentId]);
        $entrees = $reponse->return ?? [];

        return array_map(
            fn(object $entree) => ['stateName' => (string) ($entree->stateName ?? ''), 'date' => (string) ($entree->date ?? '')],
            is_array($entrees) ? $entrees : [$entrees],
        );
    }

    /**
     * @return string contenu du PDF signé
     *
     * @throws ParapheurException
     */
    public function telecharger(string $documentId): string
    {
        // ni fiche de circulation ni accusé : le document seul
        $reponse = $this->appeler('download', ['documentId' => $documentId, 'fdc' => 'false', 'acquit' => 'false']);

        $contenu = $reponse->return->content ?? null;
        if (!is_string($contenu) || '' === $contenu) {
            throw new ParapheurException(sprintf('FAST n\'a pas renvoyé le contenu du document "%s".', $documentId));
        }

        return $contenu;
    }

    /**
     * Circuits de signature de l'abonné : de quoi vérifier la connexion et trouver les identifiants à
     * renseigner sur les composantes.
     *
     * @return array<int, array{circuitId: string, circuitName: string, circuitType: string}>
     *
     * @throws ParapheurException
     */
    public function circuits(): array
    {
        $reponse = $this->appeler('getCircuits', ['siren' => $this->siren]);
        $circuits = $reponse->return ?? [];

        return array_map(
            fn(object $circuit) => [
                'circuitId' => (string) ($circuit->circuitId ?? ''),
                'circuitName' => (string) ($circuit->circuitName ?? ''),
                'circuitType' => (string) ($circuit->circuitType ?? ''),
            ],
            is_array($circuits) ? $circuits : [$circuits],
        );
    }

    /**
     * @throws ParapheurException
     */
    private function appeler(string $operation, array $arguments): object
    {
        $delai = ini_set('default_socket_timeout', (string) self::DELAI_REPONSE);
        try {
            $reponse = $this->client()->__soapCall($operation, [$arguments]);
        } catch (SoapFault $e) {
            $message = sprintf('FAST %s : %s', $operation, $e->getMessage());
            // FAST ne distingue un document inconnu que par le message ; au dépôt, ce serait un circuit inconnu
            if (isset($arguments['documentId'])
                && preg_match('/inconnu|introuvable|n.existe pas|does not exist|not found/i', $e->getMessage())) {
                throw new DocumentInconnuException($arguments['documentId']);
            }

            throw new ParapheurException($message, previous: $e);
        } finally {
            if (false !== $delai) {
                ini_set('default_socket_timeout', $delai);
            }
        }

        return is_object($reponse) ? $reponse : new \stdClass();
    }

    /**
     * @throws ParapheurException si la configuration ne permet pas d'appeler FAST
     */
    private function client(): SoapClient
    {
        if (null !== $this->client) {
            return $this->client;
        }

        foreach (['FAST_URL' => $this->url, 'FAST_SIREN' => $this->siren, 'FAST_CERTIFICAT' => $this->certificat] as $variable => $valeur) {
            if (null === $valeur || '' === trim($valeur)) {
                throw new ParapheurException(sprintf('Parapheur FAST : la variable %s n\'est pas renseignée.', $variable));
            }
        }
        if (!is_readable($this->certificat)) {
            throw new ParapheurException(sprintf('Parapheur FAST : certificat client illisible (%s).', $this->certificat));
        }

        $ssl = ['local_cert' => $this->certificat, 'verify_peer' => true, 'verify_peer_name' => true];
        if (null !== $this->motDePasse && '' !== $this->motDePasse) {
            $ssl['passphrase'] = $this->motDePasse;
        }
        if (null !== $this->autorite && '' !== trim($this->autorite)) {
            $ssl['cafile'] = $this->autorite;
        }

        try {
            return $this->client = new SoapClient($this->url . '?wsdl', [
                'location' => $this->url,
                'soap_version' => SOAP_1_1,
                'stream_context' => stream_context_create(['ssl' => $ssl]),
                'connection_timeout' => self::DELAI_CONNEXION,
                'cache_wsdl' => WSDL_CACHE_DISK,
                'exceptions' => true,
            ]);
        } catch (SoapFault $e) {
            throw new ParapheurException('Parapheur FAST injoignable : ' . $e->getMessage(), previous: $e);
        }
    }
}
