<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute le cycle du diplôme et l'année dans le diplôme sur l'inscription,
 * afin de dériver le niveau d'études (L1/L2/M1…) à partir des données Apogée
 * nationales (cycle + cod_sis_daa) plutôt que du seul préfixe du code étape.
 */
final class Version20260724141321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute inscription.cycle et inscription.annee_dans_diplome (dérivation du niveau d\'études).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription ADD cycle INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD annee_dans_diplome INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription DROP cycle');
        $this->addSql('ALTER TABLE inscription DROP annee_dans_diplome');
    }
}
