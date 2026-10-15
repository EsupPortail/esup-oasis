<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261012092548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la notion d\'inscription principale.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription ADD COLUMN principale BOOL not null default true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription drop column principale');
    }
}
