/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

/**
 Les types sont extraits du schéma OpenAPI.
 Le schéma OpenAPI est généré à partir de Symfony API Platform.

 1/ Le schéma OpenAPI est généré sur le back avec la commande suivante :
 php bin/console api:openapi:export --yaml > ./OpenApi.yml

 2/ Placer le fichier OpenApi.yml dans le dossier src/api

 3/ Les types Typescript sont générés avec la commande suivante :
 openapi-typescript ./src/api/OpenApi.yml -o ./src/api/schema.d.ts
 */

import { ApiPathMethodResponse, Components } from "@api/SchemaHelpers";
import { components, operations } from "@api/schema";

/**
 * Endpoints de l'API faisant référence à des référentiels simples du type clé/valeur.
 * Ils sont gérés de manière identique dans l'application.
 */
export type APIPathsReferentiel =
  | "/campus"
  | "/competences"
  | "/profils"
  | "/services"
  | "/types_equipements"
  | "/types_engagements"
  | "/typologies"
  | "/disciplines_sportives"
  | "/categories_amenagements"
  | "/types_suivi_amenagements"
  | "/disciplines_artistiques"
  | "/etablissements_enseignement_artistique";

/**
 * Types/interfaces des réponses des endpoints de l'API
 */
export type IUtilisateur = ApiPathMethodResponse<"/utilisateurs/{uid}", "get">;
export type IInfoComplementaire =
  components["schemas"]["InfoComplementaire.jsonld-utilisateur.out"];
export type IIntervenant = IUtilisateur;
export type IBeneficiaire = IUtilisateur;
export type IBeneficiaireProfil = ApiPathMethodResponse<"/utilisateurs/{uid}/profils/{id}", "get">;
export type ITauxHoraire = ApiPathMethodResponse<"/types_evenements/{typeId}/taux/{id}", "get">;
export type IParametre = ApiPathMethodResponse<"/parametres/{cle}", "get">;
export type IParametreValeur = ApiPathMethodResponse<"/parametres/{cle}/valeurs/{id}", "get">;
export type IStatistiquesEvenements = ApiPathMethodResponse<"/statistiques">;
export type IServicesFaits = ApiPathMethodResponse<"/periodes/{id}/services_faits", "get">;
export type IServicesFaitsLigne =
  Components["schemas"]["LigneServiceFait.jsonld-services_faits.out"];
export type IActiviteIntervenant =
  Components["schemas"]["ActiviteIntervenant.jsonld-ActiviteIntervenant.out"];
export type IActiviteBeneficiaire =
  Components["schemas"]["ActiviteBeneficiaire.jsonld-ActiviteBeneficiaire.out"];

export type IEvenement = Components["schemas"]["Evenement.jsonld-evenement.out"];
export type IPartialEvenement = Partial<IEvenement>;
export type IInscription = Components["schemas"]["Inscription.jsonld-utilisateur.out"];

export type ICampus = ApiPathMethodResponse<"/campus/{id}", "get">;
export type ICompetence = ApiPathMethodResponse<"/competences/{id}", "get">;
export type IComposante = ApiPathMethodResponse<"/composantes/{id}", "get">;
export type IFormation = ApiPathMethodResponse<"/formations/{id}", "get">;
export type IInterventionForfait = ApiPathMethodResponse<"/interventions_forfait/{id}", "get">;
export type IPeriode = ApiPathMethodResponse<"/periodes/{id}", "get">;
export type IProfil = ApiPathMethodResponse<"/profils/{id}", "get">;
export type ITypeEquipement = ApiPathMethodResponse<"/types_equipements/{id}", "get">;
export type ITypeEvenement = ApiPathMethodResponse<"/types_evenements/{id}", "get">;

export type ITypeDemande = ApiPathMethodResponse<"/types_demandes/{id}", "get">;
export type IQuestion = components["schemas"]["Question.jsonld-question.out"];
export type ICampagneDemande = components["schemas"]["CampagneDemande.jsonld-campagne.out"];
export type IDemande = components["schemas"]["Demande.jsonld-demande.out"];
export type IModificationEtatDemande = components["schemas"]["ModificationEtatDemande.jsonld"];
export type IEtatDemande = components["schemas"]["EtatDemande.jsonld"];
export type ICommission = components["schemas"]["Commission.jsonld-commission.out"];
export type ICommissionMembre =
  components["schemas"]["MembreCommission.jsonld-membre_commission.out"];
export type ITypeAmenagement = components["schemas"]["TypeAmenagement.jsonld-type_amenagement.out"];
export type ICategorieAmenagement = components["schemas"]["CategorieAmenagement.jsonld"];
export type IAmenagement = components["schemas"]["Amenagement.jsonld-amenagement.out"];
export type ITypeSuiviAmenagement =
  components["schemas"]["TypeSuiviAmenagement.jsonld-type_suivi_amenagement.out"];
export type IClubSportif = components["schemas"]["ClubSportif.jsonld-club_sportif.out"];
export type ISportifHautNiveau =
  components["schemas"]["SportifHautNiveau.jsonld-sportif_haut_niveau.out"];
export type ICharte = components["schemas"]["Charte.jsonld"];
export type ICharteUtilisateur = components["schemas"]["CharteUtilisateur.jsonld"];
export type ICategorieTag = components["schemas"]["CategorieTag.jsonld-categorie_tag.out"];
export type ITag = components["schemas"]["Tag.jsonld-tag.out"];

export type IAmenagementQuery =
  operations["api_amenagements_get_collection"]["parameters"]["query"];
export type IAmenagementBeneficiaireQuery =
  operations["api_amenagementsutilisateurs_get_collection"]["parameters"]["query"];
export type IComposanteQuery = operations["api_composantes_get_collection"]["parameters"]["query"];

export type ITelechargement = components["schemas"]["Telechargement.jsonld-telechargement.out"];

export type IAvisEse = components["schemas"]["AvisEse.jsonld-avis_ese.out"];
export type IEntretien = components["schemas"]["Entretien.jsonld-entretien.out"];
export type IAmenagementsBenefificiaire =
  components["schemas"]["Utilisateur.jsonld-amenagements_utilisateurs.out"];
export type IDiscipline =
  components["schemas"]["DisciplineSportive.jsonld-discipline_sportive.out"];

export type IDecisionEtablissement =
  components["schemas"]["DecisionAmenagementExamens.jsonld-utilisateur.out"];

export type IDocumentBeneficiaire =
  components["schemas"]["PieceJointeBeneficiaire.jsonld-piece_beneficiaire.out"];

export type ISuiviAcitivite = components["schemas"]["BilanActivite.jsonld-bilan-activite.out"];
