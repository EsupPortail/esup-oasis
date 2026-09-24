<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la colonne `code_etape` sur `inscription`, qui conserve le code étape du SI
 * scolarité pour chaque inscription. Elle permet d'afficher le cursus sur la fiche du
 * bénéficiaire et, à défaut d'autre source, d'en déduire le niveau d'études lorsque le
 * code l'encode.
 */
final class Version20260427150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne code_etape sur inscription';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription ADD code_etape VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription DROP code_etape');
    }
}
