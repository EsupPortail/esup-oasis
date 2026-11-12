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

namespace App\Tests;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class TelechargementTest extends ApiTestCaseCustom
{
    public function testDemandeurCanUploadFile(): array
    {
        $client = $this->createClientWithCredentials('demandeur2');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, 'contenu de test');

        $uploadedFile = new UploadedFile($tempFile, 'test.txt', 'text/plain', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => [
                    'file' => $uploadedFile,
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@type' => 'Telechargement',
            'nom' => 'test.txt',
            'typeMime' => 'text/plain',
            'proprietaire' => '/utilisateurs/demandeur2',
        ]);

        $data = $client->getResponse()->toArray();
        $this->assertArrayHasKey('@id', $data);
        $this->assertArrayHasKey('urlContenu', $data);

        return [
            'iri' => $data['@id'],
            'downloadUrl' => $data['urlContenu'],
        ];
    }

    /**
     * @depends testDemandeurCanUploadFile
     */
    public function testDemandeurCanGetOwnFile(array $data): void
    {
        $iri = $data['iri'];
        $client = $this->createClientWithCredentials('demandeur2');
        $client->request('GET', $iri);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $iri,
            'nom' => 'test.txt',
        ]);
    }

    /**
     * @depends testDemandeurCanUploadFile
     */
    public function testDemandeurCanDownloadOwnFile(array $data): void
    {
        $url = $data['downloadUrl'];
        $client = $this->createClientWithCredentials('demandeur2');
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $headers = $client->getResponse()->getHeaders();
        $this->assertStringContainsString('text/plain', $headers['content-type'][0]);
        $this->assertStringContainsString('inline', $headers['content-disposition'][0]);
        $this->assertStringContainsString('test.txt', $headers['content-disposition'][0]);
        $this->assertEquals('contenu de test', $client->getResponse()->getContent());
    }

    /**
     * @depends testDemandeurCanUploadFile
     */
    public function testOtherUserCannotGetFile(array $data): void
    {
        $iri = $data['iri'];
        $client = $this->createClientWithCredentials('intervenant');
        $client->request('GET', $iri);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @depends testDemandeurCanUploadFile
     */
    public function testOtherUserCannotDownloadFile(array $data): void
    {
        $url = $data['downloadUrl'];
        $client = $this->createClientWithCredentials('intervenant');
        $client->request('GET', $url);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedCannotUpload(): void
    {
        $client = static::createClient();

        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, 'contenu de test');

        $uploadedFile = new UploadedFile($tempFile, 'test.txt', 'text/plain', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => [
                    'file' => $uploadedFile,
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * Vérifie la validation des types de fichiers (MIME vs Extension) et la protection contre le path traversal
     */
    public function testFileUploadSecurity(): void
    {
        $client = $this->createClientWithCredentials('demandeur2');

        // 1. Incohérence MIME/Extension (JPEG déguisé en PDF)
        $tempFile = tempnam(sys_get_temp_dir(), 'test_sec');
        file_put_contents($tempFile, "\xFF\xD8\xFF\xE0" . str_repeat('A', 100)); // Header JPEG
        $uploadedFile = new UploadedFile($tempFile, 'malicieux.pdf', 'image/jpeg', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['hydra:description' => 'Type de fichier IMAGE incohérent avec l\'extension pdf.']);

        // 2. Type non autorisé
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test_sec2');
        file_put_contents($tempFile2, '{"toto": "titi"}');
        $uploadedFile2 = new UploadedFile($tempFile2, 'script.json', 'application/json', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile2],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['hydra:description' => 'Type de fichier non autorisé : json.']);

        // 3. Tentative de Path Traversal dans le nom du fichier
        $tempFile3 = tempnam(sys_get_temp_dir(), 'test_sec3');
        file_put_contents($tempFile3, 'contenu texte inoffensif');
        $uploadedFile3 = new UploadedFile($tempFile3, '../../../var/log/traversal.txt', 'text/plain', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'files' => ['file' => $uploadedFile3],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $createdId = basename($client->getResponse()->toArray()['@id']);
        $em = static::getContainer()->get('doctrine')->getManager();
        $fichier = $em->getRepository(\App\Entity\Fichier::class)->find($createdId);
        $metadata = $fichier->getMetadata();
        self::assertStringNotContainsString('..', $metadata['nom']);
        self::assertSame(realpath($metadata['base']), dirname(realpath($metadata['cheminComplet'])));
    }

    /**
     * Vérifie la présence des en-têtes de sécurité lors du téléchargement de fichiers (MediaDownloadController).
     */
    public function testMediaDownloadSecurityHeaders(): void
    {
        $client = $this->createClientWithCredentials('gestionnaire');
        $token = $this->getTokenForUser('gestionnaire');

        // Upload d'un fichier TIFF (autorisé à l'upload mais servi en attachment car non supporté inline dans les navigateurs)
        $tempFileTif = tempnam(sys_get_temp_dir(), 'test_dl_tif') . '.tif';
        file_put_contents($tempFileTif, "II*\x00\x08\x00\x00\x00\x00\x00");
        $uploadedTif = new UploadedFile($tempFileTif, 'image.tif', 'image/tiff', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'multipart/form-data',
            ],
            'extra' => ['files' => ['file' => $uploadedTif]],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $fileTifId = basename($client->getResponse()->toArray()['@id']);

        // Upload d'un fichier image png (doit être en inline)
        $tempFilePng = tempnam(sys_get_temp_dir(), 'test_dl_png');
        file_put_contents($tempFilePng, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        $uploadedPng = new UploadedFile($tempFilePng, 'pixel.png', 'image/png', null, true);

        $client->request('POST', '/telechargements', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'multipart/form-data',
            ],
            'extra' => ['files' => ['file' => $uploadedPng]],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $filePngId = basename($client->getResponse()->toArray()['@id']);

        // Téléchargement du fichier TIFF
        $client->request('GET', '/fichiers/' . $fileTifId, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('x-content-type-options', 'nosniff');
        $this->assertResponseHeaderSame('content-security-policy', "default-src 'none'; sandbox");
        $headersTif = $client->getResponse()->getHeaders();
        $this->assertStringStartsWith('attachment;', $headersTif['content-disposition'][0]);

        // Téléchargement du fichier PNG
        $client->request('GET', '/fichiers/' . $filePngId, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('x-content-type-options', 'nosniff');
        $this->assertResponseHeaderSame('content-security-policy', "default-src 'none'; sandbox");
        $headersPng = $client->getResponse()->getHeaders();
        $this->assertStringStartsWith('inline;', $headersPng['content-disposition'][0]);
    }
}
