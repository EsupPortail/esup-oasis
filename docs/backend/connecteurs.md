# Connecteurs

Oasis s'intègre dans le SI de l'établissement en s'appuyant sur plusieurs briques existantes. Cette section décrit
ces connexions et comment il est possible de les configurer / adapter.

## Serveur OAuth

L'application utilise une authentification OAuth, testée avec un serveur CAS v5.  
Le seul champ utilisé est l'id de l'utilisateur, qui doit correspondre au champ `uid` coté LDAP.

## Annuaire LDAP

L'annuaire LDAP est utilisé dans plusieurs contextes :

* lors de l'authentification d'un utilisateur non déjà connu, récupération des champs `uid`,  `sn`, `givenname`, `mail`
  et du champ pointé par la variable d'environnement LDAP_CHAMP_ETU_ID (`supannetuid` par défaut).
* lors de la recherche d'un utilisateur en vue de lui attribuer un rôle, recherche textuelle sur les champs
  `uid` et `cn` (par défaut, cf plus bas) pour récupération des mêmes champs que précédemment.

La liste des champs de recherche est adaptable via la variable d'environnement `LDAP_CHAMPS_RECHERCHE`, et vous pouvez
également personnaliser la recherche en ajoutant des critères via la variable `LDAP_CRITERES_RECHERCHE_SUP`.  
Par exemple pour les valeurs suivantes :

```
LDAP_CHAMPS_RECHERCHE='["uid", "cn"]'
LDAP_CRITERES_RECHERCHE_SUP="(eduPersonAffiliation=member)(|(ubxstatutcompte=ACTIF)(ubxstatutcompte=NOUVEAU))"
```

la recherche de la chaine de caractères "toto" se fera avec la requête
`(&(eduPersonAffiliation=member)(|(ubxstatutcompte=ACTIF)(ubxstatutcompte=NOUVEAU))(|(uid=*toto*)(cn=*toto*)))`

## SI scolarité

Oasis récupère des informations relatives à l'identité et aux inscriptions des étudiants dans le SI Scolarité. À ce
jour un connecteur Apogée via connexion à la base Oracle est disponible.

### Données récupérées

Oasis récupère actuellement dans Apogée :

* les inscriptions :
    * composantes de formation (table `composante`)
    * étape, version d'étape, libellé web de version d'étape, discipline,
      libellé de diplôme, niveau LMD (**basé sur une table locale !** ) (table `formation`)
    * inscription d'un étudiant à une formation pour une période donnée (table `inscription` - les début et fin de
      période sont figées au 01/09 et 31/08 de l'année universitaire)
    * statut boursier (déprécié, plus montré dans l'interface)
    * régime d'inscription
* des données personnelles complémentaires non présentes dans l'annuaire :
    * numéro de téléphone perso
    * date de naissance
    * civilité d'usage

Ces données sont récupérées à la création initiale de l'utilisateur dans l'application, puis rafraichies par un
traitement hebdomadaire.  
Un rafraichissement est également déclenché à la connexion si l'utilisateur qui se connecte est un étudiant déjà connu
pour lequel on n'a pas d'inscription en cours en base.

### Personnalisation

Plusieurs possibilités:

* personnaliser les requêtes Apogée obligatoires
* définir une requête de récupération des informations complémentaires
* écrire sa propre classe de récupération des données

#### Personnaliser les requêtes

Deux requêtes sont utilisées et adaptables pour vos besoins en modifiant simplement les fichiers
sql disponibles dans le dossier `config/apogee` (attention à bien respecter les noms des champs retournés !) :

* `apogee_get_formation.sql` : récupère le diplôme, la discipline, et le niveau LMD d'une version d'étape
* `apogee_get_inscriptions.sql` : récupère pour un étudiant la liste de ses inscriptions et données personnelles
  complémentaires entre deux dates
    * année
    * version d'étape
    * composante
    * diplôme
    * discipline
    * composante
    * date de naissance
    * civilité d'usage
    * numéro de téléphone perso
    * témoin "boursier"
    * régime d'inscription

Les versions livrées des requêtes obligatoires s'appuient sur une table locale `extern_niveau_etape` pour remonter le
niveau LMD, vous devrez donc les adapter. Le niveau LMD peut être simplement laissé vide si vous le souhaitez.

#### Récupération des informations complémentaires

Une troisième requête, optionnelle, `apogee_get_informations_complementaires.sql`, peut récupèrer des informations "non
utiles" pour le bonfonctionnement de l'application mais qui peuvent être utiles au travail des gestionnaires; elles
seront affichées dansle dossier des bénéficiaires. Ce fichier peut être laissé vide si aucune donnée supplémentaire
n'est nécessaire (laversion livrée contient un exemple commenté).

A l'usage, les résultats de cette requête sont mis en cache afin d'éviter de surcharger le SI scolarité (les appels se
font à la demande sur consultation, contrairement aux données obligatoires qui sont dupliquées dans la base Oasis et
mises à jour périodiquement par une tâche planifiée). Vous pouvez contrôler la durée de vie de ce cache via la variable
d'environnement `SI_SCOL_DUREE_VALIDITE_CACHE`, qui est une durée en secondes (3600 par défaut).

#### Implémentation de sa propre classe

Vous pouvez aussi opter pour une réimplémentation locale de l'interfaçage avec le SI scolarité (pour utiliser les WS
apogée, pour un établissement utilisant Pegase...) en étendant la classe abstraite
[`App\Service\SiScol\AbstractSiScolDataProvider`](../../backend/src/Service/SiScol/AbstractSiScolDataProvider.php).

Les méthodes à implémenter sont le miroir des requêtes plus haut :

* `getInscriptions` doit retourner un tableau des inscriptions,
* `getFormation` retourne un tableau contenant les informations de cette formation.
* `infosComplementaires` retourne un tableau contenant les informations complémentaires pour une liste d'étudiants
  donnée,
* `listeInfosComplementairesDisponibles` retourne un tableau contenant les libellés des informations complémentaires
  disponibles.

Attention à respecter les formats de tableaux en prenant exemple sur l'implémentation fournie.

> [!WARNING]
> Une dernière méthode `getProviderId` doit retourner une chaine de caractères (de votre choix, mais unique parmi les
> implémentations disponibles) servant d'identifiant pour cette implémentation; il faudra ensuite utiliser cette valeur
> pour renseigner la variable d'environnement `SI_SCOL`, dont la valeur par défaut est `APOGEE`.

## GED Nuxeo

Voir [la section dédiée aux pièces justificatives](pieces_justificatives.md)

## Signature électronique

Oasis peut faire signer électroniquement la décision d'aménagements d'examens par un parapheur électronique : au
lieu d'être envoyé par e-mail, le PDF est déposé dans un circuit de signature configuré dans le parapheur
(signataires, ordre des étapes, relances), puis récupéré signé une fois le circuit terminé. OASIS en dépose une
copie au dossier du bénéficiaire et l'envoie à l'étudiant par l'e-mail habituel, le document signé en pièce
jointe.

Si le parapheur envoie lui-même le document signé à l'étudiant, renseignez `PARAPHEUR_ENVOIE_DOCUMENT=true` :
OASIS lui transmet alors l'adresse de l'étudiant au dépôt et n'envoie plus d'e-mail.

Cette fonctionnalité est **totalement optionnelle** : si vous laissez la configuration par défaut, l'application
conserve le comportement historique (génération du PDF et envoi par e-mail).

### Activation

Le parapheur est choisi par la variable d'environnement `PARAPHEUR`, vide par défaut (aucun parapheur). Son
reflet côté frontend, `REACT_APP_PARAPHEUR`, fait apparaître les réglages de la signature électronique dans
l'interface d'administration.

Chaque composante porte le circuit de signature de ses décisions : l'identifiant du circuit, tel que le parapheur
le connaît, se renseigne dans l'administration, sur l'écran « Signature électronique » de la catégorie
Paramètres. Cet écran, visible seulement avec un parapheur, liste les composantes et leur circuit, et rappelle le
parapheur utilisé et la fréquence du suivi. Sans circuit, les
décisions des bénéficiaires de la composante suivent le comportement historique, ce qui permet une activation
progressive, composante par composante.

La composante retenue est celle de l'inscription principale en cours du bénéficiaire : une inscription
secondaire ne désigne pas les signataires (une inscription sans cette information est considérée principale).

Pour une composante reliée à un circuit, la décision validée par le gestionnaire, ou envoyée par
l'administrateur, part en signature : le circuit remplace l'envoi par e-mail de l'administrateur fonctionnel.
`SignatureElectronique` la passe aussitôt à l'état `EN_SIGNATURE`, ce qui la verrouille, puis le worker génère le
PDF et le dépose (message `SignatureDecisionDemandeeMessage`). Tant que le parapheur n'a pas le document, elle est
en cours de dépôt. Un dépôt en échec est rejoué une heure plus tard ; si la composante n'a plus de circuit au
moment du dépôt, la décision repasse à l'état `VALIDE` et l'administrateur l'envoie par e-mail.

* `PARAPHEUR` vide : aucune décision ne part en signature, même si des circuits sont renseignés (un avertissement
  est alors journalisé) ;
* avec un parapheur, une décision dont le circuit ne peut pas être déterminé n'est pas envoyée sans signature : la
  demande d'édition et l'envoi sont refusés (erreur 422), et l'interface désactive l'action en donnant le motif.
  C'est le cas d'un étudiant sans inscription en cours, dont la composante est inconnue, et d'un étudiant inscrit
  dans plusieurs composantes aux circuits différents : l'application ne choisit pas de signataire à la place de
  l'établissement.

### Suivi des signatures

Une fois la décision déposée, le worker interroge le parapheur à intervalle régulier sur les décisions en
signature, récupère les documents signés, les dépose au dossier du
bénéficiaire et passe la décision à l'état `EDITE`. Un circuit terminé sans signature (refus d'un signataire,
circuit interrompu, document inconnu du parapheur) passe la décision à l'état `REFUSEE`, et le document rendu
par le parapheur est lui aussi déposé au dossier du bénéficiaire (« Décision d'aménagements refusée au … »),
pour l'historique, sans devenir le document de la décision ; seul un document inconnu du parapheur ne peut pas
l'être. La fiche du bénéficiaire indique la cause du refus (refus, interruption, erreur), le détail restant
consultable dans le parapheur, et les décisions refusées se retrouvent avec le filtre de la liste des
bénéficiaires.

La fréquence d'interrogation se règle par le paramètre `FREQUENCE_SUIVI_SIGNATURES` (administration, écran des
paramètres), au format du Scheduler Symfony (`15 minutes`, `2 hours`…), une heure par défaut ; le worker le relit
à son redémarrage. Aucune interrogation n'est planifiée sans parapheur. La commande `app:signature:suivi`
effectue le même traitement à la demande. Sur la fiche du bénéficiaire, le bouton de rafraîchissement d'une
décision en signature interroge le parapheur pour cette seule décision, sans attendre le passage suivant
(Patch de la décision avec `{"verifierSignature": true}`, réservé aux gestionnaires ; le reste de la requête est
ignoré).

Si aucune décision du lot n'a pu être vérifiée, un message de niveau `critical` signale que le parapheur semble
injoignable ; les décisions concernées sont reprises au passage suivant. Un document que le parapheur ne connaît
plus passe la décision à l'état `REFUSEE`, avec l'état de signature `ERREUR`, et n'est plus interrogé.

Tant que la décision est en signature, rien de ce qu'elle reprend ne se modifie : les aménagements des types
inclus dans la décision et les avis de santé du bénéficiaire sont refusés en écriture (erreur 422, contrainte
`DecisionNonVerrouilleeConstraint`, suppression comprise), et la décision elle-même ne change plus d'état avant
le retour du parapheur. Une décision refusée reste verrouillée de
la même façon, jusqu'à sa reprise par un gestionnaire (bouton « Reprendre » sur la fiche du bénéficiaire, qui la
repasse à l'état `ATTENTE_VALIDATION_CAS` par le Patch de la décision) : elle redevient alors modifiable, on
corrige, puis on redemande l'édition, ce qui dépose un nouveau document. Une décision dont la période est terminée ne verrouille
plus rien, même restée refusée. L'interface désactive les actions verrouillées et en donne le motif, dès la demande
et le temps du dépôt, dont elle affiche la fin sans recharger la page ; elle ne connaît que la décision de
l'année en cours, le serveur refusant dans tous les cas.

### Date de signature et gabarit du document

Le document est déposé avant d'être signé : il ne peut donc pas imprimer la date de sa propre signature. Cette
date, fournie par le parapheur, est enregistrée sur la décision et affichée sur la fiche du bénéficiaire.

Les signataires voient le document sous le libellé « Décision d'aménagements - Prénom Nom », ou sous le nom
choisi par l'établissement dans `APP_DECISION_ETAB_LIB` (« PAEH - Prénom Nom »), comme dans l'interface.

Le gabarit de la décision reçoit la variable `data.signature_electronique`, vraie quand le document part en
signature électronique. Un établissement peut s'en servir pour adapter son modèle, par exemple ne pas y insérer
l'image de signature scannée :

```twig
{% if not data.signature_electronique %}
    {# signature scannée… #}
{% endif %}
```

### Ajouter un parapheur

Un parapheur s'ajoute en étendant la classe abstraite
[`App\Service\Signature\AbstractParapheur`](../../backend/src/Service/Signature/AbstractParapheur.php) : `deposer`
dépose le PDF dans un circuit, avec l'adresse à laquelle transmettre le document signé quand
`PARAPHEUR_ENVOIE_DOCUMENT` est vrai (`null` sinon), et retourne l'identifiant du document, `suivre` retourne son état et, une fois
signé, la date de signature, `telecharger` retourne le PDF signé. L'état retourné par `suivre` est l'une des constantes `ETAT_SIGNATURE_*` de la décision :
`EN_SIGNATURE`, `SIGNEE`, `REFUSEE`, `EXPIREE` (circuit interrompu), `REMPLACEE` (document remplacé dans le
parapheur) ou `ERREUR` ; tout état autre que `EN_SIGNATURE` et `SIGNEE` passe la décision à l'état `REFUSEE`. Les
erreurs d'appel sont levées en `App\Service\Signature\ParapheurException` (`DocumentInconnuException` pour un
document inconnu) : un dépôt en échec est rejoué une heure plus tard.

La méthode `getProviderId()` doit retourner un identifiant unique parmi les implémentations disponibles : c'est
cette valeur qui renseigne la variable `PARAPHEUR`.

Une implémentation propre à l'établissement, dans l'espace de noms `App\Service\Signature`, se dépose dans
`installation/backend/personnalisation/Signature` : elle est copiée dans les images du backend et du worker à leur
construction, comme celles du SI de scolarité.

Deux implémentations sont livrées : `aucun`, le comportement par défaut, et `factice`, disponible uniquement en
développement et en test (`PARAPHEUR=factice`). Le parapheur factice garde ses documents dans
`var/parapheur-factice`, partagé par l'API et le worker ; la commande `app:signature:factice` joue le rôle des
signataires :

```bash
php bin/console app:signature:factice lister
php bin/console app:signature:factice signer <document>
php bin/console app:signature:factice refuser <document>
```

Le suivi planifié, ou `app:signature:suivi`, reporte ensuite l'état sur la décision.

## Photos

Oasis peut récupérer et afficher les photos des étudiants (aux utilisateurs ayant un rôle gestionnaire ou
supérieur).  
Cette fonctionnalité est totalement optionnelle, si vous laissez la configuration par défaut l'application affichera des
silhouettes à la place des photos.

La récupération des photos est réalisée à l'Université de Bordeaux en interrogeant directement la base de données Oracle
de l'application Uni'Campus de Monécarte, mais cette solution est spécifique à nos usages et peut difficilement être
généralisée.

Il est donc prévu de pouvoir implémenter la récupération des photos avec la méthode de votre choix, simplement en
implémentant l'interface `App\Service\Photo\PhotoProviderInterface` et en référençant votre propre implémentation comme
celle par défaut de l'interface dans la configuration de symfony : dans le fichier `config/services.yaml` ajoutez dans
les bindings par défaut un binding de l'interface vers votre implémentation locale :

<pre>
services:
  _defaults:
    autowire: true      # Automatically injects dependencies in your services.
    autoconfigure: true # Automatically registers your services as commands, event subscribers, etc.
    bind:
      App\Service\FileStorage\StorageProviderInterface $storageProvider: '@App\Service\FileStorage\NuxeoStorageProvider'
      <b>App\Service\Photo\PhotoProviderInterface $photoProvider: '@App\Service\Photo\MonImplementationLocale'</b>
      $startScheduleNow: '%env(resolve:APP_SCHEDULER_START_NOW)%'
</pre>

Cette interface ne contient qu'une méthode, retournant pour l'utilisateur passé en paramètre le contenu d'une image
JPEG.