<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204230722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout champs decision sur la table des aménagements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE type_amenagement ADD decision BOOLEAN NOT NULL DEFAULT false
        SQL);
        $this->addSql("update type_amenagement set decision = true where examens = true");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE type_amenagement DROP decision
        SQL);
    }
}
