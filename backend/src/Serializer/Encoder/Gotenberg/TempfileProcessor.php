<?php

namespace App\Serializer\Encoder\Gotenberg;

use Generator;
use Sensiolabs\GotenbergBundle\Exception\ProcessorException;
use Sensiolabs\GotenbergBundle\Processor\ProcessorInterface;

/**
 * @implements ProcessorInterface<resource>
 */
final class TempfileProcessor implements ProcessorInterface
{
    public function __invoke(string|null $fileName): Generator
    {
        $resource = tmpfile() ?: throw new ProcessorException('Unable to create a temporary file resource.');

        do {
            $chunk = yield;
            if (false === fwrite($resource, $chunk->getContent())) {
                throw new ProcessorException('Unable to write to the temporary file resource.');
            }
        } while (!$chunk->isLast());

        rewind($resource);

        return $resource;
    }
}