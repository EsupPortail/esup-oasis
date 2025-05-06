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

namespace App\Service\FileStorage;

use Exception;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * cf https://doc.nuxeo.com/client-php/automation/
 * ou https://doc.nuxeo.com/nxdoc/document-resources-endpoints/
 *
 * ==> à la dsi plutôt atom cmis - mais "The Browser Binding (JSON) endpoint is recommended, as it is faster and has
 * more features than the other two endpoints."
 *
 * https://doc.nuxeo.com/nxdoc/cmis/
 */
class NuxeoStorageProvider implements StorageProviderInterface
{
    private bool $online;

    public function __construct(private readonly HttpClientInterface       $client, private readonly string $apiUrl,
                                private readonly string                    $user, #[SensitiveParameter] private readonly string $password,
                                private readonly string                    $espace,
                                private readonly LoggerInterface           $logger,
                                private readonly FileSystemStorageProvider $fallbackProvider)
    {
        $this->online = '' !== $this->apiUrl;
    }

    #[Override] public function copy(UploadedFile $file): array
    {
        return $this->store(fopen($file->getPathname(), 'r'), $file->getClientOriginalName(), $file->getClientMimeType());
    }

    #[Override] public function get(array $metadata): string
    {
        if (!$this->online) {
            return $this->fallbackProvider->get($metadata);
        }

        //simple GET
        $response = $this->client->request(
            method: 'GET',
            url: $this->apiUrl . '/id/' . $metadata['uid'] . '/@blob/file:content',
            options: [
                'auth_basic' => [$this->user, $this->password],
            ]
        );
        return $response->getContent();
    }

    #[Override] public function delete(array $metadata): void
    {
        if (!$this->online) {
            $this->fallbackProvider->delete($metadata);
            return;
        }

        if (!array_key_exists('uid', $metadata)) {
            //on a une vieille référence filesystem, qu'est-ce qu'on fait de ça?
            throw new RuntimeException('Erreur de suppression - fichier sans uid [' . json_encode($metadata) . ']');
        }

        $response = $this->client->request(
            method: 'DELETE',
            url: $this->apiUrl . '/id/' . $metadata['uid'],
            options: [
                'auth_basic' => [$this->user, $this->password],
            ]
        );

        if (!in_array($response->getStatusCode(), [204, 404])) {
            throw new RuntimeException('Erreur de suppression du fichier ' . $metadata['uid']);
        }
    }

    /**
     * @param mixed $contents
     * @param string $filename
     * @param string $mimeType
     * @param string $description
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws StorageProviderError
     * @throws TransportExceptionInterface
     */
    #[Override] public function store(mixed $contents, string $filename, string $mimeType, string $description = 'pj envoyée par appliphase'): array
    {
        if (!$this->online) {
            return $this->fallbackProvider->store($contents, $filename, $mimeType);
        }

        /**
         * https://doc.nuxeo.com/nxdoc/howto-upload-file-nuxeo-using-rest-api/
         *
         * 2 étapes :
         * - init d'un "batch"
         * - upload du fichier dans ce batch
         * - création du document en lien ave cle fichier uploadé
         */
        try {
            $batch = $this->client->request(
                method: 'POST',
                url: $this->apiUrl . '/upload/',
                options: [
                    'auth_basic' => [$this->user, $this->password],
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]
            );
            $result = $this->client->request(
                method: 'POST',
                url: $this->apiUrl . '/upload/' . $batch->toArray()['batchId'] . '/0',
                options: [
                    'auth_basic' => [$this->user, $this->password],
                    'headers' => [
                        'X-File-Name' => $filename,
                        'X-File-Type' => $mimeType,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $contents,
                ]
            );
            $result = $this->client->request(
                method: 'POST',
                url: $this->apiUrl . $this->espace,
                options: [
                    'auth_basic' => [$this->user, $this->password],
                    'headers' => [
                        'X-File-Name' => $filename,
                        'Content-Type' => 'application/json',
                    ],
                    'json' =>
                        [
                            "entity-type" => "document",
                            "name" => $filename,
                            "type" => "File",
                            "properties" => [
                                "dc:description" => $description,
                                "dc:title" => $filename,
                                "file:content" => [
                                    "upload-batch" => $batch->toArray()['batchId'],
                                    "upload-fileId" => $result->toArray()['fileIdx'],
                                ],
                            ],
                        ],
                ]
            );
        } catch (Exception $e) {
            $this->logger->error("Enregistrement dans Nuxeo échoué");
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());
            throw new StorageProviderError("Erreur d'enregistrement, veuillez réessayer plus tard.");
        }

        return array_filter(
            $result->toArray(),
            fn($key) => in_array($key, ['uid', 'path']), ARRAY_FILTER_USE_KEY
        );
    }
}