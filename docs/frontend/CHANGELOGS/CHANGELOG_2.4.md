# Changelog - Frontend

## [2.4.0]

### En résumé

Cette version constitue une refonte technique majeure du frontend. La stack de build migre de Create React App vers **Vite**, Redux est supprimé au profit des **contextes natifs React** et le reverse proxy passe de Nginx à **Caddy**. Plusieurs dépendances obsolètes sont supprimées (`moment.js`, `react-big-calendar`, `react-inject-env`, `Lottie`) au profit d'alternatives maintenues. L'accessibilité est renforcée sur l'ensemble de l'application, un environnement de tests unitaires est mis en place, et une pré-version du **dark mode** est intégrée. Côté fonctionnel : upload multi-fichiers, persistence des filtres exports CSV découpés en plusieurs requêtes pour éviter les timeouts, nouveau filtre sur la date de validité du profil Bénéficiaire et possibilité d'ajouter une demande directement depuis le profil bénéficiaire.

### Refactoring architectural

- **Suppression de Redux** : l'état UI (drawers, modals, filtres, accessibilité) migre vers des contextes natifs React (`AccessibiliteContext`, `DrawersContext`, `ModalsContext`, `AffichageFiltresContext`).
- **Suppression de `react-scripts` / CRA** au profit de **Vite**, avec alias TypeScript configurés et chunking optimisé (chunks séparés pour React et le lazy-loading de `HtmlEditor`).
- **Suppression de `react-inject-env`** : les variables d'environnement sont désormais validées au démarrage de l'application (`AppProviders`).
- **Barrelisation** des modules pour simplifier les imports.
- **Décomposition des gros composants** en sous-composants atomiques : `EvenementModal`, `UtilisateurDrawer`, `QuestionnaireProvider`, `CommissionsEdition`, `CalendarEvent`, `AvancementDemandeGestion`, `Toolbar` et bien d'autres.
- **`AppRoutes` réorganisé par feature** pour une meilleure colocalisation.
- **Isolation du `queryClient`** et correction des query keys d'invalidation de cache.

### Dépendances

- **Remplacement de `react-big-calendar`** (non maintenu) par **FullCalendar**.
- Suppression de `react-modern-calendar`, `Lottie`, `OAuthPopup`.
- Mise à jour de `jwt-decode`, `eslint` (10.2), `prettier` (3.8).
- Suppression de `moment.js`.
- Migration vers **Node.js 24 LTS**.
- Migration **Sass `@import` → `@use`** (suppression des warnings de déprécation).
- Suppression des attributs et API Ant Design dépréciés.

### Accessibilité (a11y)

- Corrections WCAG multiples sur l'ensemble de l'application.
- Ajout de **vitest-axe** pour les tests d'accessibilité automatisés.
- Ajustements des marges, paddings et styles pour le respect des contrastes et la navigation clavier.
- **Dark mode (bêta)** : mode sombre disponible en version préliminaire, destiné aux personnes sujettes à la fatigue oculaire ou à l'inconfort visuel.

### Darkmode (bêta)

- Pré-version du **dark mode** intégrée (le mode est désactivé par défaut) via un `themeBuilder` dédié.
- Ajustements de la page de connexion pour les deux thèmes.
- Hook `useEffectiveTheme` pour la détection et l'application du thème courant.

### Nouveautés fonctionnelles

- **Upload de plusieurs fichiers simultanément** dans les demandes.
- **Onglet "Toutes les campagnes"** pour les administrateurs (affichage - lecture seule - de toutes les campagnes d'un type de demande).
- **Persistence des filtres** (Demandeurs, Bénéficiaires, Aménagements, Intervenants) lors de la navigation entre les pages.
- **Exports CSV en mode split** pour éviter les timeouts sur les grands volumes.
- **Skeleton loader** sur le questionnaire.
- Mail personnel éditable pour un gestionnaire (fix).
- Ajout d'une demande depuis le profil bénéficiaire.
- **Gestion des erreurs 404** et ajout d'un `ErrorBoundary` applicatif.
- Gestion améliorée des **mentions légales** (page dédiée + fix Caddy) ; voir [personnalisation-ui.md](/docs/frontend/personnalisation-ui.md).
- Retrait des `prompt()` natifs JS.
- Nouveau filtre pour les bénéficiaires : selon la date de validité du profil

### Tests

- Mise en place de l'**environnement de tests unitaires** (Vitest + MSW).
- Tests unitaires couvrant : modules `utils`, `lib`, `api`, `auth` et hooks d'authentification.

### Déploiement

- **`REACT_APP_VERSION` supprimée** : la version est désormais injectée automatiquement lors de l'étape de build.
- **`REACT_APP_GERER_DEMANDES`** : nouvelle variable permettant de désactiver la gestion des demandes pour les établissements qui n'en ont pas l'usage.
- **`REACT_APP_MAX_FILE_SIZE`** : taille max des fichiers en upload (en Mo) - 10Mo par défaut.
- **Remplacement de Nginx par Caddy** pour la configuration du reverse proxy.
- Compression **zstd** (prioritaire) et **gzip** (fallback) activées.
- Cache granulaire : assets JS/CSS/fonts immuables (1 an), images (30 jours), `index.html` sans cache.
- Ajout d'un header **Content-Security-Policy**.
- **Sanitisation du HTML** (`DOMPurify`) pour se prémunir des injections.
- Extraction des SVG en assets statiques.
