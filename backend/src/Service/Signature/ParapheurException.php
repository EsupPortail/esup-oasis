<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\Signature;

use RuntimeException;

/**
 * Échec d'un appel au parapheur. Chaque implémentation y enveloppe ses propres erreurs (SOAP, HTTP…),
 * pour que le dépôt soit rejoué plutôt que perdu.
 */
class ParapheurException extends RuntimeException {}
