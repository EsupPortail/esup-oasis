<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Parametre;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923151300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout paramètre LIEU_COURRIER';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("insert into parametre(id, cle) 
                            values(nextval('parametre_id_seq'), '"
        . Parametre::LIEU_COURRIER
        . "')");

        $this->addSql("insert into valeur_parametre(id, parametre_id, valeur, debut)
                            select nextval('valeur_parametre_id_seq'), id, 'Une ville', now()
                            from parametre
                            where cle = '" . Parametre::LIEU_COURRIER . "'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "delete from valeur_parametre where parametre_id = (select id from parametre where cle = '"
            . Parametre::LIEU_COURRIER
            . "')",
        );

        $this->addSql("delete from parametre where cle = '" . Parametre::LIEU_COURRIER . "'");
    }
}
