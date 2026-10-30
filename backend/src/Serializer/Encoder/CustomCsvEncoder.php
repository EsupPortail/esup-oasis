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

namespace App\Serializer\Encoder;

use Symfony\Component\Serializer\Encoder\CsvEncoder;

class CustomCsvEncoder extends CsvEncoder
{
    public function encode(mixed $data, string $format, array $context = []): string
    {
        $context[parent::DELIMITER_KEY] = ';';
        $context[parent::NO_HEADERS_KEY] = true;
        $context[parent::OUTPUT_UTF8_BOM_KEY] = true;
        $context[parent::ESCAPE_FORMULAS_KEY] = true;
        return parent::encode($data, $format, $context);
    }

    public function supportsEncoding(string $format): bool
    {
        return 'customcsv' === $format;
    }
}
