# Oasis

Oasis (**O**util pour l’**A**ccompagnement et le **S**uivi **I**ndividualisé des étudiants à besoins **S**pécifiques)
est une application dédiée à la gestion des étudiants qui, à l'université, ont besoin d'un accompagnement personnalisé :
sportifs de haut niveau, en situation de handicap, élus, salariés...

## Principales fonctionnalités

Oasis propose les fonctionnalités suivantes :

* Saisie par les étudiants des demandes d'accompagnement, en fonction de campagnes définies par les gestionnaires
* Traitement des demandes par les gestionnaires (et les éventuelles commissions)
* Gestion du dossier bénéficiaire (profil, mise en place d'aménagements, CR d'entretiens...)
* Gestion des intervenants (pour de la prise de notes, du tutorat, du secrétariat d'épreuves...)
* Planification des interventions
* Exports / bilans

## Installation

Oasis est disponible sous la forme de deux applications frontend et backend séparées, utilisant les technologies
suivantes :

* Frontend: JavaScript, React 19
* Backend: PHP 8.4, Symfony 8.0 / Api Platform 4

Des Dockerfile sont fournis pour le déploiement des deux applications séparément, plus de détails dans la documentation
de chacune.

Un modèle d'installation "tout-en-un" est également fourni, vous pouvez vous référer à
la [documentation dédiée](docs/installation/README.md).

## Documentation

Les fonctionnalités de l'application sont documentées via
l'[expression de besoins](docs/Documentation_Fonctionnelle_Oasis_1.0.pdf), remise à jour post développement.

Des documentations techniques sont disponibles séparément pour les deux briques applicatives et pour l'installation
tout-en-un:

* [frontend](docs/frontend/README.md)
* [backend](docs/backend/README.md)
* [installation](docs/installation/README.md)

## Communauté et entraide

Pour échanger avec les autres utilisateurs d'Esup-Oasis, deux salons RocketChat sont à votre disposition sur l'instance d'EsupPortail :

- Sujets fonctionnels : [https://rocket.esup-portail.org/channel/oasis-fonctionnel](https://rocket.esup-portail.org/channel/oasis-fonctionnel) 
- Sujets techniques : [https://rocket.esup-portail.org/channel/oasis-technique](https://rocket.esup-portail.org/channel/oasis-technique)

---

_Application développée par la Direction des Systèmes d'Information (DSI) de
l'[université de Bordeaux](https://u-bordeaux.fr), mise à disposition des établissements d'enseignement supérieur via
l'incubateur ESUP (https://www.esup-portail.org/)._
