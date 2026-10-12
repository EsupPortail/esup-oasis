<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adresse postale de l'utilisateur, importée du SI scolarité.
 *
 * Cinq colonnes nullables portées par un embeddable Doctrine : un établissement
 * dont la requête ne renvoie pas les alias d'adresse les laisse simplement à NULL.
 */
final class Version20260427120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Adresse postale de l'utilisateur (embeddable), importée du SI scolarité";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD adresse_ligne1 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD adresse_ligne2 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD adresse_code_postal VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD adresse_ville VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD adresse_pays VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP adresse_ligne1');
        $this->addSql('ALTER TABLE utilisateur DROP adresse_ligne2');
        $this->addSql('ALTER TABLE utilisateur DROP adresse_code_postal');
        $this->addSql('ALTER TABLE utilisateur DROP adresse_ville');
        $this->addSql('ALTER TABLE utilisateur DROP adresse_pays');
    }
}
