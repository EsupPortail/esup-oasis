<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002090100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Exigence de l'avis médical par profil de bénéficiaire";
    }

    public function up(Schema $schema): void
    {
        // décochée partout : rien n'est exigé tant qu'un administrateur ne l'active pas
        $this->addSql('ALTER TABLE profil_beneficiaire ADD avis_medical_requis BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profil_beneficiaire DROP avis_medical_requis');
    }
}
