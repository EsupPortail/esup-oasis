<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230706074245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des typologies de handicap';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE typologie_handicap_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE beneficiaire_typologie_handicap (beneficiaire_id INT NOT NULL, typologie_handicap_id INT NOT NULL, PRIMARY KEY(beneficiaire_id, typologie_handicap_id))');
        $this->addSql('CREATE INDEX IDX_FCA652665AF81F68 ON beneficiaire_typologie_handicap (beneficiaire_id)');
        $this->addSql('CREATE INDEX IDX_FCA65266F4F9B0 ON beneficiaire_typologie_handicap (typologie_handicap_id)');
        $this->addSql('CREATE TABLE typologie_handicap (id INT NOT NULL, libelle VARCHAR(255) NOT NULL, actif BOOLEAN DEFAULT true NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE beneficiaire_typologie_handicap ADD CONSTRAINT FK_FCA652665AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE beneficiaire_typologie_handicap ADD CONSTRAINT FK_FCA65266F4F9B0 FOREIGN KEY (typologie_handicap_id) REFERENCES typologie_handicap (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE intervenant ALTER debut DROP DEFAULT');
        $this->addSql('ALTER TABLE intervenant ALTER fin DROP DEFAULT');
        $this->addSql('ALTER TABLE profil_beneficiaire ADD avec_typologie BOOLEAN DEFAULT false NOT NULL');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE typologie_handicap_id_seq CASCADE');
        $this->addSql('ALTER TABLE beneficiaire_typologie_handicap DROP CONSTRAINT FK_FCA652665AF81F68');
        $this->addSql('ALTER TABLE beneficiaire_typologie_handicap DROP CONSTRAINT FK_FCA65266F4F9B0');
        $this->addSql('DROP TABLE beneficiaire_typologie_handicap');
        $this->addSql('DROP TABLE typologie_handicap');
        $this->addSql('ALTER TABLE intervenant ALTER debut SET DEFAULT CURRENT_DATE');
        $this->addSql('ALTER TABLE intervenant ALTER fin SET DEFAULT CURRENT_DATE');
        $this->addSql('ALTER TABLE profil_beneficiaire DROP avec_typologie');
    }
}
