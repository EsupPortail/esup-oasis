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

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface StorageProviderInterface
{
    /**
     * @param File $file
     * @return array
     *
     * Copie le contenu pointé par $file sur le stockage persistent
     * et retourne un tableau de metadata permettant de le retrouver.
     */
    public function copy(UploadedFile $file): array;

    /**
     * @param mixed $contents
     * @param string $filename
     * @param string $mimeType
     * @param string $description
     * @return array
     * @throws StorageProviderError
     */
    public function store(mixed $contents, string $filename, string $mimeType, string $description): array;

    public function get(array $metadata): File|string;

    public function delete(array $metadata);
}