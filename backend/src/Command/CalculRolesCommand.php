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

namespace App\Command;

use App\Entity\Parametre;
use App\Message\ModificationUtilisateurMessage;
use App\Repository\ParametreRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:calcul-roles',
    description: 'Initialisation de la version stockée du champ calculé roles des utilisateurs',
)]
class CalculRolesCommand extends Command
{
    public function __construct(private readonly UtilisateurRepository $utilisateurRepository,
                                private readonly ParametreRepository   $parametreRepository,
                                private readonly MessageBusInterface   $messageBus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $paramRolesAJour = $this->parametreRepository->findOneBy([
            'cle' => Parametre::ROLES_A_JOUR,
        ]);

        if ($paramRolesAJour->getValeurCourante()->getValeur() !== 'false') {
            $io->info('Roles déjà à jour.');

            return Command::SUCCESS;
        }

        foreach ($this->utilisateurRepository->findAll() as $utilisateur) {
            $this->utilisateurRepository->save($utilisateur);
            $this->messageBus->dispatch(new ModificationUtilisateurMessage($utilisateur));
        }

        $this->utilisateurRepository->save($utilisateur ?? null, true);

        $paramRolesAJour->getValeurCourante()->setValeur('true');
        $this->parametreRepository->save($paramRolesAJour, true);

        $io->success('Roles initialisés.');

        return Command::SUCCESS;
    }
}
