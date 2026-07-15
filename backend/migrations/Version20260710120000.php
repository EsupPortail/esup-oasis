<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout des paramètres MENU_* permettant à chaque établissement de personnaliser
 * les libellés du menu principal et de ses sous-menus.
 */
final class Version20260710120000 extends AbstractMigration
{
    private const array LIBELLES_MENU = [
        'MENU_DEMANDEURS' => 'Demandeurs',
        'MENU_BENEFICIAIRES' => 'Bénéficiaires',
        'MENU_BENEFICIAIRES_LISTE' => 'Bénéficiaires',
        'MENU_BENEFICIAIRES_AMENAGEMENT_PAR_BENEF' => 'Aménagements par bénéf.',
        'MENU_BENEFICIAIRES_AMENAGEMENT' => 'Aménagements',
        'MENU_INTERVENANTS' => 'Intervenants',
        'MENU_PLANNING_PLANIF' => 'Planning',
        'MENU_PLANNING_INTERVENTIONS' => 'Planning des interventions',
        'MENU_PLANNING_INTERVENTIONS_FORFAIT' => 'Interventions au forfait (prise de notes)',
        'MENU_PLANNING_VALIDATION_RENFORTS' => 'Validation des interventions des renforts',
        'MENU_PLANNING_MES_INTERVENTIONS' => 'Vos interventions (renfort)',
        'MENU_PLANNING_BENEF_INTERV' => 'Planning',
        'MENU_DEMANDES_DEMANDEUR' => 'Demandes',
        'MENU_DEMANDES_COMMISSION' => 'Demandes',
        'MENU_SERVICES_FAITS' => 'Services faits',
        'MENU_REFERENT_AMENAGEMENTS' => 'Aménagements',
        'MENU_NOTIFICATIONS' => 'Notifications',
        'MENU_UTILISATEUR_MON_PROFIL' => 'Mon profil',
        'MENU_UTILISATEUR_ADMINISTRATION' => 'Administration',
        'MENU_UTILISATEUR_BILANS' => 'Bilans',
        'MENU_UTILISATEUR_DECONNEXION' => 'Se déconnecter',
        'MENU_UTILISATEUR_RECUP_IDENTITE' => 'Récupérer mon identité',
        'MENU_THEME' => 'Thème',
        'MENU_THEME_CLAIR' => 'Mode clair',
        'MENU_THEME_SOMBRE' => 'Mode sombre',
        'MENU_THEME_SYSTEME' => 'Identique au système',
        'MENU_A11Y' => 'Accessibilité',
        'MENU_A11Y_CONTRASTE' => 'Contraste',
        'MENU_A11Y_POLICE_LEXEND' => 'Police : Lexend',
        'MENU_A11Y_POLICE_ARIAL' => 'Police : Arial',
        'MENU_A11Y_POLICE_OPENDYS' => 'Police : OpenDys',
        'MENU_A11Y_POLICE_LARGE' => 'Police large',
    ];

    public function getDescription(): string
    {
        return 'Ajout des paramètres MENU_* pour la personnalisation des libellés du menu principal';
    }

    public function up(Schema $schema): void
    {
        foreach (self::LIBELLES_MENU as $cle => $valeur) {
            $cle = addslashes($cle);
            $valeur = addslashes($valeur);

            // Idempotent : n'insère le paramètre que s'il n'existe pas déjà (ex. migration
            // relancée après un échec partiel, ou paramètre déjà présent en base).
            $this->addSql("insert into parametre(id, cle, fichier)
                                select nextval('parametre_id_seq'), '$cle', false
                                where not exists (select 1 from parametre where cle = '$cle')");

            // De même, n'ajoute une valeur que si ce paramètre n'en a encore aucune.
            $this->addSql("insert into valeur_parametre(id, parametre_id, valeur, debut)
                                select nextval('valeur_parametre_id_seq'), p.id, '$valeur', now()
                                from parametre p
                                where p.cle = '$cle'
                                  and not exists (
                                      select 1 from valeur_parametre vp where vp.parametre_id = p.id
                                  )");
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::LIBELLES_MENU) as $cle) {
            $cle = addslashes($cle);

            $this->addSql("delete from valeur_parametre
                                where parametre_id in (select id from parametre where cle = '$cle')");
            $this->addSql("delete from parametre where cle = '$cle'");
        }
    }
}
