<?php /** @noinspection PhpUndefinedVariableInspection */

/*
 * Copyright (c) 2024. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

declare(strict_types=1);

namespace App\Command;

use App\Repository\AmenagementRepository;
use App\Repository\EntretienRepository;
use App\Repository\FichierRepository;
use App\Repository\ReponseRepository;
use App\Repository\UtilisateurRepository;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:anonymizer', description: 'anonymisation des données')]
class AnonymizerCommand extends Command
{

    public function __construct(private readonly UtilisateurRepository $utilisateurRepository,
                                private readonly FichierRepository     $fichierRepository,
                                private readonly AmenagementRepository $amenagementRepository,
                                private readonly EntretienRepository   $entretienRepository,
                                private readonly ReponseRepository     $reponseRepository,
                                ?string                                $name = null)
    {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create();

        //utilisateurs
        foreach ($this->utilisateurRepository->findAll() as $utilisateur) {
            if (!$utilisateur->isAdmin()) {
                $utilisateur->setNom($faker->lastName());
                $utilisateur->setPrenom($faker->firstName());
                $utilisateur->setDateNaissance($faker->dateTimeThisCentury());
                $utilisateur->setEmail($utilisateur->getPrenom() . '.' . $utilisateur->getNom() . '@etablissement.fr');
                $utilisateur->setEmailPerso($utilisateur->getPrenom() . '.' . $utilisateur->getNom() . '@monmailperso.fr');
                $utilisateur->setTelPerso('123456789');
                $utilisateur->setContactUrgence("un contact en cas d'urgence");
            }
            $this->utilisateurRepository->save($utilisateur);
        }

        if ($utilisateur ?? false) {
            $this->utilisateurRepository->save($utilisateur, true);
        }

        //noms de fichiers
        foreach ($this->fichierRepository->findAll() as $fichier) {
            $nom = $fichier->getNom();
            $pathInfo = pathinfo($nom);
            $fichier->setNom(match (true) {
                null !== $fichier->getBilan() => 'bilan' . '.' . $pathInfo['extension'],
                null !== $fichier->getAvisEse() => 'avis' . '.' . $pathInfo['extension'],
                null !== $fichier->getDecisionAmenagementExamens() => 'decision' . '.' . $pathInfo['extension'],
                null !== $fichier->getEntretien() => 'entretien' . '.' . $pathInfo['extension'],
                null !== $fichier->getPieceJointeBeneficiaire() => 'piece' . '.' . $pathInfo['extension'],
                default => $nom
            });
            $this->fichierRepository->save($fichier);
        }

        if ($fichier ?? false) {
            $this->fichierRepository->save($fichier, true);
        }

        //commentaires des aménagements
        foreach ($this->amenagementRepository->findAll() as $amenagement) {
            $amenagement->setCommentaire($faker->text());
            $this->amenagementRepository->save($amenagement);
        }

        if ($amenagement ?? false) {
            $this->amenagementRepository->save($amenagement, true);
        }

        //commentaire des entretiens
        foreach ($this->entretienRepository->findAll() as $entretien) {
            $entretien->setCommentaire($faker->text());
            $this->entretienRepository->save($entretien);
        }

        if ($entretien ?? false) {
            $this->entretienRepository->save($entretien, true);
        }

        //réponses aux questions des demandes
        foreach ($this->reponseRepository->findAll() as $reponse) {
            $reponse->setCommentaire(match ($reponse->getQuestion()->getTypeReponse()) {
                'text' => $faker->text(80),
                'date' => $faker->dateTimeThisCentury()->format('Y-m-d'),
                'numeric' => (string)$faker->randomNumber(),
                'textarea' => $faker->text(500),
                default => $reponse->getCommentaire()
            });
            $this->reponseRepository->save($reponse);
        }

        if ($reponse ?? false) {
            $this->reponseRepository->save($reponse, true);
        }

        return Command::SUCCESS;
    }
}
