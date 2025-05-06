# language: fr
#noinspection NonAsciiCharacters
Fonctionnalité: Amenagements

  L'application comporte une table de référence des aménagements proposés.
  Les administrateurs peuvent la tenir à jour.

  Scénario: En tant qu'utilisateur connecté je peux obtenir la liste des aménagements
    Etant donné que j'envoie un token d'authentification pour demandeur
    Quand j'envoie une requête "GET" sur "/types_amenagements"
    Alors la réponse devrait être du JSON
    Et le code de status de la réponse devrait être 200
    Et les noeuds JSON devraient contenir:
      | @context | /contexts/TypeAmenagement |
      | @type    | hydra:Collection          |
      | @id      | /types_amenagements       |

  @modifyData
  Scénario: En tant qu'administrateur je peux ajouter un aménagement
    Etant donné que j'envoie un token d'authentification pour admin
    Et que j'ajoute l'entête "Content-Type" égale à "application/ld+json"
    Quand j'envoie une requête "POST" sur "/types_amenagements" avec le contenu :
    """
    {
      "libelle" : "nouveau type aménagement",
      "pedagogique" : false,
      "examens" : true,
      "aideHumaine" : false,
      "categorie" :  "/categories_amenagements/1"
    }
    """
    Alors la réponse devrait être du JSON
    Et le code de status de la réponse devrait être 201
    Et les noeuds JSON devraient contenir:
      | @context | /contexts/TypeAmenagement |
      | libelle  | nouveau type aménagement  |
    Et le noeud JSON "pedagogique" devrait être faux
    Et le noeud JSON "examens" devrait être vrai

  Scénario: En tant que simple gestionnaire je ne peux que consulter
    Etant donné que j'envoie un token d'authentification pour gestionnaire
    Et que j'ajoute l'entête "Content-Type" égale à "application/ld+json"
    Quand j'envoie une requête "POST" sur "/types_amenagements" avec le contenu :
    """
    {
      "libelle" : "nouveau type aménagement",
      "pedagogique" : false,
      "examens" : true,
      "aideHumaine" : false,
      "categorie" :  "/categories_amenagements/1"
    }
    """
    Alors le code de status de la réponse devrait être 403