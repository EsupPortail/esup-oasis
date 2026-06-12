# Constantes de l'application

Les constantes de l'application sont définies dans le fichier `src/constants.ts`.

| **Variable**                       | **Description**                                                                                         | **Valeur par défaut**             | **Obligatoire** |
|------------------------------------|---------------------------------------------------------------------------------------------------------|-----------------------------------|-----------------|
| `NB_MAX_ITEMS_PER_PAGE`            | Nombre maximum d'éléments par page (utilisé pour récupérer l'intégralité des éléments d'une collection) | 9999                              | Oui             |
| `TYPE_EVENEMENT_RENFORT`           | Id du type d'évènements "Renfort au service"                                                            | "/types_evenements/-1"            | Oui             |
| `BENEFICIAIRE_PROFIL_A_DETERMINER` | Id du profil bénéficiaire "À déterminer"                                                                | "/profils/-1"                     | Oui             |
| `PARAMETRE_COEF_COUT_CHARGE`       | Id du paramètre "Coefficient de charges"                                                                | "/parametres/COEFFICIENT_CHARGES" | Oui             |

Ces constantes sont utilisées pour assurer le bon fonctionnement de l'application. Dans le cadre d'une installation
classique, il n'est généralement pas nécessaire de les modifier.

Cependant, si vous souhaitez personnaliser l'application pour répondre à des besoins spécifiques, vous pouvez adapter
ces constantes en fonction de vos configurations.