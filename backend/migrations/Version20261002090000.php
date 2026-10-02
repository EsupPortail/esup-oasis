<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Observations et date de l'avis médical sur la décision d'aménagements";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE decision_amenagement_examens ADD observations TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE decision_amenagement_examens ADD date_avis_medecin DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE decision_amenagement_examens DROP date_avis_medecin');
        $this->addSql('ALTER TABLE decision_amenagement_examens DROP observations');
    }
}
