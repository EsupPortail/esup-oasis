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
}
