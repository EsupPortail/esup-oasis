# Oasis - backend

Application backend :

* API REST
* Traitements différés

## Composants techniques

Développé en PHP 8.4, avec les frameworks Symfony 8 (https://symfony.com/)
et Api Platform 4 (https://api-platform.com/), et utilise une base Postgresql (testé sur version 13+).

Déploiement avec docker / K8S, avec le serveur d'applications frankenphp (https://frankenphp.dev/),
un module pour Caddy - https://caddyserver.com/.

[OPTIONNEL] Le cache http Souin (https://souin.io/) est utilisé sous la forme d'un module Caddy.

## Dépendances / intégrations

* Pour l'authentification, un serveur OAuth (testé avec un serveur CAS).
* Pour l'accès aux données de scolarité, une base Apogée (possibilité d'implémenter d'autres connecteurs)
* Pour l'édition PDF, un service gotenberg (https://gotenberg.dev/).
* Pour le stockage des pièces justificatives, un serveur de GED Nuxeo en option (sinon stockage "fichier")
* Pour la vérification antivirus des pièces justificatives, un service clamav (https://www.clamav.net/)
* Pour la récupération des photos des étudiants, possibilité d'utiliser celles stockées
  dans une base oracle Unicampus (ou d'implémenter une solution locale différente, la version livrée est
  possiblement spécifique à l'installation locale Unicampus de l'établissement)
* Pour les envois de mail, un serveur SMTP

## Déploiement

Le déploiement est recommandé via Docker, sont fournis les Dockerfile suivants :

* Dockerfile : pour générer l'image de production du service web
* Dockerfile-dev : pour générer l'image de développement (activation du module php xdebug...)
* Dockerfile-worker : pour générer une image dédiée à un conteneur séparé pour les traitements
  différés (il est possible de tout déployer dans le même conteneur si on le souhaite)

### Build

Pour la production :

```
$ docker build -t oasis-backend .
$ docker build -t oasis-backend-worker -f Dockerfile-worker .
```

Pour le développement :

```
$ docker build -t oasis-backend -f Dockerfile-dev .
```

### Configuration technique :

La configuration technique de l'application se fait en renseignant les variables d'environnement
disponibles dans le fichier
.env (https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-env-files) :

* **APP_ENV** : environnement de déploiement (test, staging ou production)
* **APP_SECRET** : secret utilisé par symfony pour les opérations nécessitant de la
  sécurité (cf https://symfony.com/doc/current/reference/configuration/framework.html#configuration-framework-secret)
* **DATABASE_URL** : url de la base de données Postgresql (`postgresql://user:pwd@server:
  port/dbname?serverVersion=13&charset=utf8`)
* **FRONT_URL** : url du frontend
* **BACK_URL** : url du backend
* **CORS_ALLOW_ORIGIN** : une expression régulière correspondant aux domaines autorisés à faire des
  requêtes CORS (ie localhost + le domaine du frontend, si différent). Par exemple pour
  autoriser https://oasis.u-bordeaux.fr
  en plus de la machine elle-même : `^https?://(localhost|127\.0\.0\.1|oasis\.u-bordeaux\.fr)(:[0-9]+)?$`
* **JWT_COOKIE_DOMAIN** : le domaine auquel associer le cookie généré par l'authentification
* **JWT_PASSPHRASE** : la clé d'encodage des JWT
* **OAUTH_CLIENTID** : l'id de client du backend pour votre serveur oauth,
* **OAUTH_CLIENTSECRET** : secret associé au clientid
* **OAUTH_REDIRECTURI** : l'uri vers laquelle rediriger après authentification correcte
* **OAUTH_URLAUTHORIZE** : l'uri de l'endpoint "authorize" du serveur oauth
* **OAUTH_URLACCESSTOKEN** : l'uri de l'endpoint permettant d'obtenir un accesstoken oauth
* **OAUTH_URLRESOURCEOWNERDETAILS** : l'uri de l'endpoint permettant d'obtenir le profil de l'utilisateur connecté
* **LDAP_HOST** : serveur ldap
* **LDAP_PORT** : port du serveur ldap
* **LDAP_SSL** : true ou false suivant le mode d'accès à votre serveur d'accès
* **LDAP_USERNAME** : nom d'utilisateur pour connexion au LDAP
* **LDAP_PASSWORD** : mot de passe de connexion au LDAP
* **LDAP_DN** : base dans laquelle rechercher les individus dans le LDAP
* **LDAP_CHAMPS_RECHERCHE** : champs utilisés pour la recherche d'individus
* **LDAP_CRITERES_RECHERCHE_SUP** : Filtre additionnel pour la recherche
* **LDAP_CHAMP_ETU_ID** : Champ contenant le numéro étudiant
* **MAILER_DSN** : DSN du serveur smtp (`smtp://user:password@server:port`)
* **GOTENBERG_DSN** : URL de l'instance Gotenberg
* **SI_SCOL** : implémentation à utiliser pour l'accès au SI de scol (cf la section sur
  les [connecteurs](connecteurs.md#personnalisation))
* **APOGEE_USER** : utilisateur avec droits de lecture dans la base Apogée
* **APOGEE_PWD** : mot de passe de l'utilisateur Apogée
* **APOGEE_DB** : url de la base Apogée (`//serveur:port/SID`)
* [OPTION NUXEO] **NUXEO_API_URL** : url de l'api nuxeo
* [OPTION NUXEO] **NUXEO_API_USER** : utilisateur nuxeo
* [OPTION NUXEO] **NUXEO_API_PASS** : mot de passe nuxeo
* [OPTION NUXEO] **NUXEO_ESPACE** : id de l'espace de stockage nuxeo
* [OPTION STOCKAGE LOCAL] **STORAGE_BASE_PATH** : chemin de base des PJ sur le système de fichiers (`/data/PJ`)
* **CLAMAV_SERVER** : serveur clamav (`clamav.univ.fr`)
* **CLAMAV_PORT** : port du serveur clamav
* [OPTIONNEL] **UNICAMPUS_SGBD** : SGBD hébergeant la base Unicampus (`oracle` ou `postgresql`)
* [OPTIONNEL] **UNICAMPUS_USER** : utilisateur avec droits de lecture sur base Unicampus
* [OPTIONNEL] **UNICAMPUS_PWD** : mot de passe utilisateur Unicampus
* [OPTIONNEL] **UNICAMPUS_SID** : url de la base Unicampus (`//serveur:port/SID` pour oracle)
* [OPTIONNEL] **UNICAMPUS_SUFFIXES** : suffixes ajoutés par unicampus aux numéros étudiants (`["suffixe1","suffixe2"]`;
  les RNE des établissements concernés pour l'instance Unicampus utilisée)

Les valeurs renseignées dans le fichier .env peuvent être surchargées par des variables d'environnement au niveau
système (https://symfony.com/doc/current/configuration.html#overriding-environment-variables-defined-by-the-system), ou
encore via un fichier
.env.local (https://symfony.com/doc/current/configuration.html#overriding-environment-values-via-env-local).

### Configuration applicative "statique"

En plus des variables d'environnement statiques, une partie de la configuration applicative est gérée via le
fichier .env. Ce sont des chaines de caractères "statiques" utilisées principalement dans les courriels envoyés
par le backend :

* **APP_TITRE** : le nom affiché de l'application
* **APP_ETABLISSEMENT** : le nom de l'établissement
* **APP_ETABLISSEMENT_ARTICLE** : article (`le`, `la` ou `l'`) à utiliser devant le nom de l'établissement
* **APP_ETABLISSEMENT_ABV** : nom court de l'établissement
* **APP_ETABLISSEMENT_ABV_ARTICLE** : article (`le`, `la` ou `l'`) à utiliser devant le nom court de l'établissement
* **APP_ETABLISSEMENT_URL** : url du site institutionnel de l'établissement
* **APP_SERVICE** : nom court du service d'accompagnement des étudiants (ex : "TANDEM")
* **APP_SERVICE_DENOMINATION** nom du service d'accompagnement des étudiants (ex: "service TANDEM")
* **APP_SERVICE_DENOMINATION_LONGUE** : nom long du service d'accompagnement des étudiants (ex:"Service Tandem
  d'Accompagnement des Étudiants (TANDEM)")
* **APP_SERVICE_ARTICLE** : article (`le`, `la` ou `l'`) à utiliser devant le nom du service
* **APP_EMAIL_SERVICE** : courriel du service d'accompagnement des étudiants
* **APP_URL_SERVICE** : url du site web du service d'accompagnement des étudiants
* **APP_SERVICE_SPORTIFS_DENOMINATION** : nom du service qui gère les demandes des sportifs
* **APP_SERVICE_SPORTIFS_ARTICLE** : article (`le`, `la` ou `l'`) à utiliser devant le nom du service qui gère les
  demandes des sportifs
* **APP_SERVICE_SPORTIFS_EMAIL** : courriel du service qui gère les demandes des sportifs
* **APP_LOGO** : chemin relatif de l'image contenant le logo de l'application sur le serveur backend (par rapport au
  dossier "public" - ex: "/images/logo.svg")
* **APP_LOGO_FOOTER_MAILS** : chemin relatif de l'image contenant le logo à ajouter au footer des courriels sur le
  serveur backend (par rapport au dossier "public" - ex: "/images/logo_footer.svg")

### Exécution

Une fois l'image (ou les images) docker et la configuration prêtes, vous pouvez démarrer le conteneur de manière
classique :

```
$ docker run oasis-backend
```

Pour le développement, vous pouvez passer quelques paramètres supplémentaires pour servir directement les fichiers sur
lesquels vous travaillez et activer xdebug par exemple :

```
$ docker run --add-host=host.docker.internal:ip.machine.dev \
  -v $PWD:/app \ 
  -v $PWD/docker/xdebug-conf.ini:/usr/local/etc/php/conf.d/99-xdebug-conf.ini oasis-backend
```

Au premier démarrage la base (qui doit être créée au préalable) sera initialisée automatiquement / mise à jour si
nécessaire.
Pour le développement, vous pouvez créer et mettre à jour la base à la main après le premier démarrage du conteneur si
vous le souhaitez (en ignorant les erreurs) :

```
$ php bin/console doctrine:database:create
$ php bin/console doctrine:migrations:migrate
```

### Données initiales

Comme mentionné plus haut, la base de données est mise à jour / initialisée automatiquement au démarrage.

Les données qui sont chargées à l'initialisation sont celles définies dans les fixtures du
dossier [deploiement](../../backend/fixtures/deploiement). Les données livrées sont celles utilisées par l'université de
Bordeaux, modifiez les en fonction de vos besoins !

La commande exécutée pour l'initialisation des données peut être lancée à la main :

```
$ php bin/console app:init-db
```

Elle n'a cependant d'effet que s'il n'existe aucun utilisateur déclaré en base ; si vous voulez réinitialiser vos
données via cette commande il faut repartir d'une base sans aucune données.

### Conseils pratiques

L'application génère (si nécessaire) au démarrage des clés servant à signer les token JWT qu'elle émet. Ces clés sont
présentes dans le dossier config/jwt.  
Afin d'éviter des déconnexions intempestives au redéploiement et/ou des soucis d'authentification si vous déployez
plusieurs instances en parallèle, vous devez monter ce dossier en tant que volume docker, partagé par toutes les
instances de l'application.

## Docs complémentaires

* [Guide de développement](developpement.md)
* [Paramétrage technico-fonctionnel](parametrage.md)
* [Emails](emails.md)
* [Traitements asynchrones](traitements_asynchrones.md)
* [Authentification](auth.md)
* [Connecteurs](connecteurs.md)
* [Décision d'aménagements](decision.md)
* [Stockage de fichiers](pieces_justificatives.md)
* [Questionnaire des demandes](questionnaires.md)