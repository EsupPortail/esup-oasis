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

namespace App\Command;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:init-db', description: 'Add a short description for your command')]
class InitDonneesCommand extends Command
{
    protected array $fixtures;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PurgerLoader $loader,
    ) {
        $this->fixtures = [
            'campus',
            'competences',
            'typologies_handicap',
            'profils_beneficiaires',
            'services',
            'types_equipements',
            'types_evenements',
            'utilisateurs_admins',
        ];

        parent::__construct('app:init-db');
    }

    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        //check if necessary
        $users = $this->entityManager->find(Utilisateur::class, 1);
        if (null !== $users) {
            $io->warning('Base de données non vide, rien à faire');
            return Command::SUCCESS;
        }
        //$fixtureLoader = $this->get('fidry_alice_data_fixtures.loader.doctrine');

        $fixtures = array_map(fn($path) => __DIR__ . '/../../fixtures/deploiement/' . $path . '.yaml', $this->fixtures);
        $this->loader->load(fixturesFiles: $fixtures, purgeMode: PurgeMode::createNoPurgeMode());

        $io->success('Chargement réussi');

        return Command::SUCCESS;
    }
}
