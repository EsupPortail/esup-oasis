# Déploiement simplifié avec Docker Compose

Cette procédure permet d'installer rapidement l'application ESUP-Oasis en utilisant Docker Compose. Elle inclut tous les
composants nécessaires à son fonctionnement pour une installation "tout en un" sur la même machine.

## Pré-requis

- Docker et Docker Compose installés sur votre machine.

## Installation

### Configurer l'environnement:

Copiez le fichier [.env.exemple](../../installation/.env.exemple) en `.env`, puis adaptez-le à votre installation (pour
plus d'infos sur
les
variables, référez-vous aux documentations respectives du frontend et du backend).

### Préparer l'initialisation de la base de données

L'initialisation se fait via des fixtures, dont la version livrée correspond aux usages de l'Université de Bordeaux.
Il s'agit de fichiers yaml qu'il vous faudra adapter à vos
usages comme indiqué dans la documentation du backend sur
les [données initiales](/docs/backend/README.md#données-initiales).

### Incorporer votre personnalisation

#### Frontend

En plus de ce qui est contrôlé par des variables d'environnement, vous pouvez surcharger les images utilisées en
plaçant
vos versions locales (qui doivent avoir le même nom / format) dans le
répertoire [installation/frontend/personnalisation/images/](frontend/personnalisation/images/).

De la même manière, vous pouvez personnaliser le pdf pointé par la variable `REACT_APP_GUIDE_SERVICE_SYNCHRO` si vous
utilisez cette fonctionnalité, en plaçant votre fichier dans le
dossier [installation/frontend/personnalisation/pdf/](frontend/personnalisation/pdf/).

Plus de détails sur les personnalisations du frontend
dans [la documentation dédiée](/docs/frontend/personnalisation-ui.md).

#### Backend

De la même manière que pour le frontend, vous pouvez adapter les templates, les images et les css utilisées en ajoutant
vos propres versions dans le dossier [backend/personnalisation/](backend/personnalisation/).

Plus de détails sur les templates disponibles dans la documentation dédiée pour [les emails](/docs/backend/emails.md);
les décisions et les services faits suivent le même modèle.

Vous pouvez également utiliser le
dossier [backend/personnalisation/config/apogee](../../installation/backend/personnalisation/config/apogee) pour
spécifier les requêtes
d'interrogation de la base apogée que vous aurez défini - plus d'informations dans
la [documentation dédiée au connecteur vers le SI de scolarité](/docs/backend/connecteurs.md#si-scolarité).

Si vous utilisez un autre SI de scolarité sans implémentation fournie, vous pouvez également ajouter votre
implémentation dans le dossier [backend/personnalisation/SiScol](../../installation/backend/personnalisation/SiScol) -
cf [documentation dédiée au connecteur vers le SI de scolarité](/docs/backend/connecteurs.md#si-scolarité)

De même, pour faire signer la décision d'aménagements par un parapheur sans implémentation fournie, ajoutez la
vôtre dans le dossier [backend/personnalisation/Signature](../../installation/backend/personnalisation/Signature) -
cf [documentation de la signature électronique](/docs/backend/connecteurs.md#ajouter-un-parapheur)

### Démarrer l'application

* Pour de la production :

```
docker compose up -d
```

* Pour les tests / le développement

```
docker compose -f compose.yaml -f compose.dev.yaml up -d
```

### Aller plus loin

* [Composants inclus](composants.md)
* [Mise à jour](maj.md)