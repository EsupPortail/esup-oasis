/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { IUtilisateur } from "@api";
import { etablissement } from "@lib";
import { InscriptionsDetail } from "@controls/Items/InscriptionsDetail";

interface ITabScolariteProps {
  utilisateur: IUtilisateur;
}

/**
 * Renders the "TabScolarite" component.
 *
 * @param {ITabScolariteProps} props - The component props.
 * @returns {ReactElement} The rendered "TabScolarite" component.
 */
export function TabScolarite({ utilisateur }: ITabScolariteProps): ReactElement {
  return (
    <>
      <p className="semi-bold">Inscriptions {etablissement.abv.a}</p>
      <InscriptionsDetail inscriptions={utilisateur.inscriptions} />
    </>
  );
}
