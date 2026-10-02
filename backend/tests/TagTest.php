<?php

namespace App\Tests;

class TagTest extends ApiTestCaseCustom
{
    public function testAdminCanCreateTag(): void
    {
        $client = $this->createClientWithCredentials('admin');

        $client->request('POST', '/tags', [
            'json' => [
                'libelle' => 'Nouveau Tag Test',
                'actif' => true,
                'categorie' => '/categories_tags/1',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json');
        $this->assertJsonContains([
            'libelle' => 'Nouveau Tag Test',
            'actif' => true,
            'categorie' => '/categories_tags/1',
        ]);
    }

    public function testAnonymousCannotCreateTag(): void
    {
        $client = static::createClient();
        $client->request('POST', '/tags', [
            'json' => [
                'libelle' => 'Tag Anonyme',
                'actif' => true,
                'categorie' => '/categories_tags/1',
            ],

            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}
