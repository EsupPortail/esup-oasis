<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Command;

use App\Service\Signature\DocumentInconnuException;
use App\Service\Signature\ParapheurFactice;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'test')]
#[AsCommand(
    name: 'app:signature:factice',
    description: 'Fait avancer les documents du parapheur factice à la place des signataires (PARAPHEUR=factice)',
)]
class SignatureFacticeCommand extends Command
{
    public function __construct(
        private readonly ParapheurFactice $parapheur,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'lister, signer ou refuser');
        $this->addArgument('document', InputArgument::OPTIONAL, 'Identifiant du document à signer ou refuser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $documentId = $input->getArgument('document');

        if ('lister' === $action) {
            $documents = $this->parapheur->documents();
            if ([] === $documents) {
                $io->info('Aucun document dans le parapheur factice.');

                return Command::SUCCESS;
            }

            $io->table(
                ['Document', 'État', 'Circuit', 'Destinataire', 'Libellé'],
                array_map(
                    fn(string $id, array $document) => [$id, $document['etat'], $document['circuit'], $document['destinataire'], $document['libelle']],
                    array_keys($documents),
                    $documents,
                ),
            );

            return Command::SUCCESS;
        }

        if (!in_array($action, ['signer', 'refuser'], true) || null === $documentId) {
            $io->error('Usage : lister, signer <document> ou refuser <document>.');

            return Command::INVALID;
        }

        try {
            match ($action) {
                'signer' => $this->parapheur->marquerSignee($documentId),
                'refuser' => $this->parapheur->marquerRefusee($documentId),
            };
        } catch (DocumentInconnuException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        // le suivi planifié, ou app:signature:suivi, reporte l'état sur la décision
        $io->success(sprintf('Document %s : %s.', $documentId, 'signer' === $action ? 'signé' : 'refusé'));

        return Command::SUCCESS;
    }
}
