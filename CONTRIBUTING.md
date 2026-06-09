# Comment contribuer ?

## Signaler des bogues ou suggérer des fonctionnalités

Si vous trouvez un bogue ou si vous avez une idée de nouvelle fonctionnalité, n'hésitez pas à ouvrir une *issue* sur le
dépôt du projet.

## Proposer des changements (Pull Requests)

1. **Forkez** le dépôt.
2. Créez une **branche** pour votre modification (ex: `feat/ma-nouvelle-fonctionnalite` ou `fix/mon-correctif`).
3. Effectuez vos modifications.
4. Assurez-vous que votre code compile et que les tests passent.
5. Soumettez une **Pull Request** vers la branche cible du projet :
    * "main" pour les évolutions
    * "x.y" pour un correctif sur une version spécifique

# Configuration du projet

Le projet est divisé en deux parties : un backend Symfony et un frontend React.

## Documentation technique

Pour plus de détails sur le développement et le déploiement de chaque composant, veuillez vous référer aux
documentations dédiées :

- [Documentation Backend](docs/backend/README.md)
- [Guide de développement Backend](docs/backend/developpement.md)
- [Documentation Frontend](docs/frontend/README.md)
- [Guide de développement Frontend](docs/frontend/developpement.md)

## Environnement de développement

Le projet fournit des configurations Docker pour faciliter le développement.

- Le backend peut être lancé via Docker (voir `docs/backend/developpement.md`).
- Le frontend peut être lancé localement avec `yarn start` (voir `docs/frontend/developpement.md`).

# Standards de code

- **Backend** : Respecter les standards PSR et les bonnes pratiques Symfony.
- **Frontend** : Utilisation de Prettier et ESLint (voir configurations dans le dossier `frontend`).

# Licence

En contribuant à ce projet, vous acceptez que vos contributions soient placées sous la même licence que le projet
original (voir le fichier [LICENSE](LICENSE)).
