/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { App, Tag, Typography } from "antd";
import { useApi } from "@context/api/ApiProvider";
import { IParametre, QK_PARAMETRES } from "@api";

interface MenuLabelEditItemProps {
  /** Paramètre MENU_* correspondant, undefined si non chargé (cas non attendu après migration). */
  parametre?: IParametre;
  cle: string;
  /** Libellé en dur dans le menuItemXxx de production, affiché tant qu'aucune valeur n'est en base. */
  defaut: string;
  condition?: string;
  /** Niveau d'indentation dans l'arborescence (0 = racine, 1 = sous-item). */
  niveau: number;
  /** Reflète la saisie en cours dans l'aperçu live, avant confirmation de la sauvegarde. */
  onChange?: (cle: string, valeur: string) => void;
  /** N'affiche la clé technique (ex. "MENU_PLANNING_PLANIF") qu'aux administrateurs techniques. */
  afficherCle: boolean;
}

/**
 * Édition inline d'un unique libellé de menu MENU_*. Volontairement plus simple que
 * ParametreFormItem/ParametreFormItemString : pas de dates de validité, une seule valeur
 * courante éditée directement (les 32 paramètres MENU_* ont déjà une valeur suite à la migration).
 */
export default function MenuLabelEditItem({
  parametre,
  cle,
  defaut,
  condition,
  niveau,
  onChange,
  afficherCle,
}: MenuLabelEditItemProps): ReactElement {
  const { message } = App.useApp();
  const patchValeur = useApi().usePatch({
    path: "/parametres/{cle}/valeurs/{id}",
    invalidationQueryKeys: [QK_PARAMETRES],
    onSuccess: () => message.success("Libellé mis à jour"),
  });
  // Paramètre sans valeur courante (ex. valeur expirée/supprimée) : il faut créer la première
  // valeur au lieu de patcher une valeur inexistante, sinon la saisie n'est jamais envoyée.
  const postValeur = useApi().usePost({
    path: "/parametres/{cle}/valeurs",
    invalidationQueryKeys: [QK_PARAMETRES],
    parameters: { cle: parametre?.["@id"] as string },
    onSuccess: () => message.success("Libellé enregistré"),
  });

  const valeurCourante = parametre?.valeursCourantes?.[0];
  const valeurActuelle = valeurCourante?.valeur ?? defaut;

  function handleChange(nouvelleValeur: string): void {
    const valeurNettoyee = nouvelleValeur.trim();

    if (!valeurNettoyee) {
      message.warning("Le libellé ne peut pas être vide.");
      return;
    }

    onChange?.(cle, valeurNettoyee);

    if (valeurCourante?.["@id"]) {
      patchValeur.mutate({
        "@id": valeurCourante["@id"] as string,
        data: { valeur: valeurNettoyee },
      });
      return;
    }

    if (!parametre?.["@id"]) return;

    postValeur.mutate({
      data: { valeur: valeurNettoyee, debut: new Date().toISOString() },
    });
  }

  return (
    <div className={`menu-label-node menu-label-node--${niveau === 0 ? "racine" : "enfant"}`}>
      <Typography.Text
        className="menu-label-node__valeur"
        strong={niveau === 0}
        editable={{ text: valeurActuelle, onChange: handleChange }}
      >
        {valeurActuelle}
      </Typography.Text>
      {afficherCle && <span className="menu-label-node__cle">{cle}</span>}
      {condition && (
        <Tag variant="filled" className="mt-1">
          {condition}
        </Tag>
      )}
    </div>
  );
}
