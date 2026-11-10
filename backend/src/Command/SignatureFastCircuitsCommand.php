<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Command;

use App\Service\Signature\Fast\ClientFast;
use App\Service\Signature\ParapheurException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:signature:fast:circuits',
    description: 'Vérifie la connexion à FAST-Parapheur et liste les circuits de signature de l\'abonné',
)]
class SignatureFastCircuitsCommand extends Command
{
    public function __construct(
        private readonly ClientFast $client,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $circuits = $this->client->circuits();
        } catch (ParapheurException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if ([] === $circuits) {
            $io->warning('Connexion établie, mais aucun circuit n\'est défini pour cet abonné.');

            return Command::SUCCESS;
        }

        // l'identifiant est la valeur à renseigner sur la composante
        $io->table(
            ['Identifiant', 'Nom', 'Type'],
            array_map(fn(array $c) => [$c['circuitId'], $c['circuitName'], $c['circuitType']], $circuits),
        );

        return Command::SUCCESS;
    }
}
