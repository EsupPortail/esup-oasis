# Authentification

L'API est entièrement privée, les seuls endpoints publics sont ceux permettant de s'authentifier, derrière le préfixe
`/connect`.

## Authentification d'un utilisateur

Par défaut (flux Authorization Code avec PKCE, activé via `OAUTH_PKCE=true` et `REACT_APP_OAUTH_PKCE=true`), le frontend
déclenche l'authentification auprès du serveur OAuth2 / OIDC et obtient en retour un code d'autorisation (`code`). Il
obtient ensuite un token JWT du backend en appelant l'endpoint `/connect/oauth/token` avec ce code, le `codeVerifier`
PKCE et l'`redirectUri` :

```
$ curl 'https://backend.univ.fr/connect/oauth/token?json=1' \
    -X POST \
    -H 'Content-Type: application/json' \
    -H 'Connection: keep-alive' \
    --data-raw '{"code":"OC-xxxxxxxx","codeVerifier":"xxxxxxxx","redirectUri":"https://oasis.univ.fr/callback"}'
```

Si PKCE est désactivé (`OAUTH_PKCE=false` / `REACT_APP_OAUTH_PKCE=false`, ancien flux implicit de CAS), le frontend
transmet directement l'`accessToken` obtenu auprès du serveur OAuth :

```
$ curl 'https://backend.univ.fr/connect/oauth/token?json=1' \
    -X POST \
    -H 'Content-Type: application/json' \
    -H 'Connection: keep-alive' \
    --data-raw '{"accessToken":"AT-xxxxxxxxxxxxxxxxxxxxx"}'
```

En retour l'API va :

* créer un cookie de session httpOnly "oasis-token" contenant le JWT généré
* retourner ce même token dans le payload de la réponse :

  ```
  {"token":"eyxxxxxxxxxxxxxxxxxxxxxxxxx"}
  ```

Les appels suivants à l'API devront fournir le JWT obtenu soit via le cookie "oasis-token", soit dans le header
HTTP Authorization (`Authorization: Bearer eyxxxxxxxxxxxxxx`).

Lors de la déconnexion, un appel à l'endpoint `/connect/oauth/logout` (`POST` ou `GET`) permet d'effacer le cookie
httpOnly de session.

## Authentification d'une application cliente

En complément d'une authentification par un utilisateur via OAuth, l'API permet des accès (très restreints) par une
application externe via un identifiant d'application cliente et une clé d'API.

Pour obtenir un token JWT, les applications clientes peuvent appeler l'endpoint `/connect/apikey` :

```
$ curl 'https://backend.univ.fr/connect/apikey' \ 
    -X POST \
    -H 'Content-Type: application/json' \
    -H 'Connection: keep-alive' \
    -data-raw '{"appId":"identifiant", "apiKey":"apiKey"}'
```

Comme pour l'authentification d'un utilisateur classique, les appels suivants devront fournir le JWT obtenu dans les
headers de la requête.

Le seul endpoint actuellement autorisé pour ce type de client est /evenements. Il est utilisé dans une application
locale pour générer des calendriers individuels.

## Authentification complète sans frontend

Pour le développement il peut être pratique de s'authentifier directement depuis le backend, sans avoir de frontend
déployé. L'endpoint suivant a été mis en place à cet effet : `/connect/oauth`; en y accédant directement depuis un
navigateur vous serez redirigé vers le serveur Oauth, puis authentifié coté application (cookie httpOnly positionné +
token retourné).