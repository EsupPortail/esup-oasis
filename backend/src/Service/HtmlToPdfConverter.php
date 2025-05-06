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

namespace App\Service;

use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class HtmlToPdfConverter
{
    public function __construct(private HttpClientInterface $client,
                                private string              $apiUri,
                                private string              $appId,
                                private string              $apiKey)
    {
    }

    /**
     * @param string $htmlContent
     * @param string|null $header
     * @param string|null $footer
     * @param int $marginTop
     * @param int $marginBottom
     * @param int $marginLeft
     * @param int $marginRight
     * @return string
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function convert(string $htmlContent, ?string $header = null, ?string $footer = null,
                            int    $marginTop = 0, int $marginBottom = 0, int $marginLeft = 0, int $marginRight = 0): string
    {
        $response = $this->client->request(
            method: 'POST',
            url: $this->apiUri . '/connect',
            options: [
                'json' => ['appId' => $this->appId, 'apiKey' => $this->apiKey],
            ]
        );
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Service de génération de PDF indisponible');
        }
        $jwt = $response->getContent();

        $json = [
            'htmlContent' => $htmlContent,
            'marginTop' => $marginTop,
            'marginBottom' => $marginBottom,
            'marginLeft' => $marginLeft,
            'marginRight' => $marginRight,
        ];

        if (null !== $header) {
            $json['header'] = $header;
        }

        if (null !== $footer) {
            $json['footer'] = $footer;
        }

        $response = $this->client->request(
            method: 'POST',
            url: $this->apiUri . '/conversions',
            options: [
                'json' => $json,
                'headers' => [
                    'Authorization' => 'Bearer ' . $jwt,
                    'accept' => 'application/pdf',
                    'Content-Type' => 'application/ld+json',
                ],
            ]
        );

        return $response->getContent();
    }
}
