/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { Fragment, ReactElement } from "react";
import { IParametre } from "@api";
import { MenuLabelNode } from "@controls/Admin/Menus/menusAdminConfig.types";
import MenuLabelEditItem from "@controls/Admin/Menus/MenuLabelEditItem";

interface MenuLabelTreeProps {
  items: MenuLabelNode[];
  parametresParCle: Map<string, IParametre>;
  onChange?: (cle: string, valeur: string) => void;
  /** N'affiche la clé technique (ex. "MENU_PLANNING_PLANIF") qu'aux administrateurs techniques. */
  afficherCle: boolean;
}

function renderNode(
  node: MenuLabelNode,
  niveau: number,
  parametresParCle: Map<string, IParametre>,
  afficherCle: boolean,
  onChange?: (cle: string, valeur: string) => void,
): ReactElement {
  return (
    <Fragment key={node.cle}>
      <MenuLabelEditItem
        parametre={parametresParCle.get(node.cle)}
        cle={node.cle}
        defaut={node.defaut}
        condition={node.condition}
        niveau={niveau}
        onChange={onChange}
        afficherCle={afficherCle}
      />
      {node.enfants?.map((enfant) =>
        renderNode(enfant, niveau + 1, parametresParCle, afficherCle, onChange),
      )}
    </Fragment>
  );
}

/** Rendu hiérarchique (2 niveaux max) des libellés de menu d'un onglet, sans composant Tree. */
export default function MenuLabelTree({
  items,
  parametresParCle,
  onChange,
  afficherCle,
}: MenuLabelTreeProps): ReactElement {
  return (
    <div className="menu-label-tree">
      {items.map((node) => renderNode(node, 0, parametresParCle, afficherCle, onChange))}
    </div>
  );
}
