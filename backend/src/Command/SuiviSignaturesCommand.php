<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

namespace App\Command;

use App\Service\Signature\SuiviSignatureService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:signature:suivi',
    description: 'Interroge le parapheur sur les décisions en cours de signature et récupère celles qui sont signées',
)]
class SuiviSignaturesCommand extends Command
{
    private const int LIMITE_PAR_DEFAUT = 50;

    public function __construct(
        private readonly SuiviSignatureService $suiviSignatureService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'limite',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Nombre maximum de décisions examinées à ce passage',
            default: self::LIMITE_PAR_DEFAUT,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limite = (int) $input->getOption('limite');
        if ($limite < 1) {
            $io->error('La limite doit être un entier positif.');

            return Command::INVALID;
        }

        $rapport = $this->suiviSignatureService->traiterLot($limite);

        if (0 === $rapport->examinees) {
            $io->info('Aucune décision en cours de signature.');

            return Command::SUCCESS;
        }

        $io->table(
            ['Examinées', 'Signées', 'Refusées', 'Closes sans signature', 'Erreurs'],
            [[
                $rapport->examinees,
                $rapport->signees,
                $rapport->refusees,
                $rapport->closes,
                $rapport->erreurs,
            ]],
        );

        // erreurs déjà journalisées et reprises au passage suivant : la commande réussit
        if ($rapport->erreurs > 0) {
            $io->warning($rapport->erreurs . ' décision(s) à reprendre au prochain passage.');
        }

        return Command::SUCCESS;
    }
}
