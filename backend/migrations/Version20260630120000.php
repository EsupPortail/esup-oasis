<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Situation sociale de l'utilisateur, importée du SI scolarité.
 *
 * Code et libellé (Apogée : cod_soc / lib_soc), projetés depuis l'inscription la
 * plus récente. Colonnes nullables : sans les alias correspondants, rien n'est écrit.
 */
final class Version20260630120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Situation sociale de l'utilisateur (code et libellé), importée du SI scolarité";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD code_situation_sociale VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD libelle_situation_sociale VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP code_situation_sociale');
        $this->addSql('ALTER TABLE utilisateur DROP libelle_situation_sociale');
    }
}
