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
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class FileSystemStorageProvider implements StorageProviderInterface
{

    function __construct(private string $basePath)
    {

    }

    #[Override] public function copy(UploadedFile $file): array
    {
        if (!file_exists($file->getRealPath())) {
            throw new FileNotFoundException();
        }

        return $this->store($file->getContent(), $file->getFilename(), $file->getMimeType());
    }

    #[Override] public function get(array $metadata): File
    {
        return new File($metadata['cheminComplet']);
    }

    public function delete(array $metadata): void
    {
        if (file_exists($metadata['cheminComplet'] ?? '')) {
            unlink($metadata['cheminComplet']);
        }
    }

    /**
     * @param string $contents
     * @param string $filename
     * @param string $mimeType
     * @param string $description
     * @return array
     * @throws Exception
     */
    #[Override] public function store(mixed $contents, string $filename, string $mimeType, string $description = 'pj envoyée par appliphase'): array
    {
        $uniqId = uniqid($filename);
        $filepath = $this->basePath . '/' . $uniqId;

        if (!file_put_contents(filename: $filepath, data: $contents)) {
            throw new Exception('impossible de persister le fichier');
        }

        return [
            'base' => $this->basePath,
            'nom' => $uniqId,
            'cheminComplet' => $filepath,
        ];

    }
}