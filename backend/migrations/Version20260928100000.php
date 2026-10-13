<?php

/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 */

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928100000 extends AbstractMigration
{
    private const string FREQUENCE_SUIVI_SIGNATURES = 'FREQUENCE_SUIVI_SIGNATURES';

    public function getDescription(): string
    {
        return "Signature électronique des décisions d'aménagements";
    }

    public function up(Schema $schema): void
    {
        // état de la signature sur la décision
        $this->addSql('ALTER TABLE decision_amenagement_examens ADD etat_signature VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE decision_amenagement_examens ADD id_document_parapheur VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE decision_amenagement_examens ADD derniere_verification_signature TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE decision_amenagement_examens ADD date_signature TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE decision_amenagement_examens ADD uid_demandeur_signature VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DECISION_DOCUMENT_PARAPHEUR ON decision_amenagement_examens (id_document_parapheur)');

        // circuit de signature de chaque composante, renseigné dans l'administration
        $this->addSql('ALTER TABLE composante ADD circuit_signature VARCHAR(255) DEFAULT NULL');

        // fréquence du suivi des signatures, réglable dans l'administration ; idempotent
        $cle = self::FREQUENCE_SUIVI_SIGNATURES;
        $this->addSql("insert into parametre(id, cle, fichier)
                            select nextval('parametre_id_seq'), '$cle', false
                            where not exists (select 1 from parametre where cle = '$cle')");
        $this->addSql("insert into valeur_parametre(id, parametre_id, valeur, debut)
                            select nextval('valeur_parametre_id_seq'), p.id, '1 hour', now()
                            from parametre p
                            where p.cle = '$cle'
                              and not exists (
                                  select 1 from valeur_parametre vp where vp.parametre_id = p.id
                              )");
    }

    public function down(Schema $schema): void
    {
        $cle = self::FREQUENCE_SUIVI_SIGNATURES;
        $this->addSql("delete from valeur_parametre
                            where parametre_id in (select id from parametre where cle = '$cle')");
        $this->addSql("delete from parametre where cle = '$cle'");

        $this->addSql('ALTER TABLE composante DROP circuit_signature');

        $this->addSql('DROP INDEX UNIQ_DECISION_DOCUMENT_PARAPHEUR');
        $this->addSql('ALTER TABLE decision_amenagement_examens DROP uid_demandeur_signature');
        $this->addSql('ALTER TABLE decision_amenagement_examens DROP date_signature');
        $this->addSql('ALTER TABLE decision_amenagement_examens DROP derniere_verification_signature');
        $this->addSql('ALTER TABLE decision_amenagement_examens DROP id_document_parapheur');
        $this->addSql('ALTER TABLE decision_amenagement_examens DROP etat_signature');
    }
}
