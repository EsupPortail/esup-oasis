<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute le type de diplôme (cod_tpd_etb) et l'indicateur santé (tem_sante) sur
 * l'inscription, afin de séparer les familles de diplômes lors de la dérivation
 * du niveau d'études : seuls les diplômes LMD donnent lieu à un niveau L/M/D,
 * les formations de santé et les autres familles (BUT/DUT/ingénieur/DU) restant
 * sans niveau applicable (cf. NiveauResolver).
 */
final class Version20260827120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le type de diplôme et l\'indicateur santé sur inscription';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription ADD code_type_diplome VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD sante BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription DROP code_type_diplome');
        $this->addSql('ALTER TABLE inscription DROP sante');
    }
}
