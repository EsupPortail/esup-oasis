# Décision d'aménagements

La décision d'aménagements, nommée « Décision d'établissement » par défaut dans l'interface
(cf. `REACT_APP_DECISION_ETAB_LIB`), est un document PDF généré à la demande depuis la fiche d'un
bénéficiaire. Cette page décrit ce qui peut être adapté à l'établissement, et comment.

Rien n'est obligatoire : sans paramétrage, le comportement est inchangé.

## Date de l'avis du médecin

La décision peut porter la date de l'avis du médecin, saisie depuis la fiche du bénéficiaire
en même temps que les observations particulières, proposées pour tous les profils. Les deux
champs sont enregistrés sur la décision elle-même, dont la durée est l'année universitaire : la
date reste celle de l'avis retenu pour ce document, même si le bénéficiaire a plusieurs avis
santé. Tant que la décision n'en porte pas, le début de l'avis santé en cours est suggéré en
grisé dans le champ, qui reste vide : le gestionnaire saisit la date après l'avoir vérifiée sur
le document de l'avis.

Les établissements dont le visa cite cet avis ont besoin que la date soit renseignée avant
l'édition, faute de quoi la mention légale s'imprime à trous sur une pièce qui fait courir un
délai de recours.

L'exigence se règle **par profil de bénéficiaire**, dans *Administration › Référentiels › Profils*,
avec l'option « Avis médical requis pour éditer la décision d'établissement », dont le libellé suit
le nom configuré de la décision. Elle n'est proposée que pour les profils de handicap, ceux qui
portent une typologie de handicap (« Handicap permanent » et « Incapacité temporaire » par défaut) :
les profils sportifs ou artistes ne relèvent pas d'un avis médical.

Pour un bénéficiaire dont au moins un profil de handicap, sur la période de la décision, active
l'option, la demande d'édition du gestionnaire comme l'envoi par l'administrateur sont refusés tant
que la date manque, et l'interface l'annonce avant de laisser confirmer. Les observations et la date
elle-même se saisissent tant que la décision n'est pas envoyée, sans changer son état.
