<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute trois colonnes sur `inscription` :
 *
 * - `nombre_inscriptions_etape` : compteur natif du SI scolarité du nombre
 *   d'inscriptions administratives à l'étape, base du signalement de redoublement ;
 * - `code_cursus_amenage` et `libelle_cursus_amenage` : code SISE et libellé du cursus
 *   aménagé, dont la présence neutralise ce signalement.
 */
final class Version20260630130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le compteur d\'inscriptions à l\'étape et le cursus aménagé sur inscription';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription ADD nombre_inscriptions_etape INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD code_cursus_amenage VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD libelle_cursus_amenage VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription DROP nombre_inscriptions_etape');
        $this->addSql('ALTER TABLE inscription DROP code_cursus_amenage');
        $this->addSql('ALTER TABLE inscription DROP libelle_cursus_amenage');
    }
}
