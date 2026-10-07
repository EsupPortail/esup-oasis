/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useEffect } from "react";
import { useLocation } from "react-router";
import { env } from "@/env";
import { etablissement } from "@lib";

/**
 * Changement du titre de la page en fonction de la localisation actuelle.
 * Retourne un élément React vide afin de l'insérer dans le rendu de la page sans rien afficher.
 * @return {ReactElement}
 */
export default function PageTitle(props: {
  setSelectedMenuKey?: (domaine: string | undefined) => void;
}): ReactElement {
  const location = useLocation();

  useEffect(() => {
    let title = `[${etablissement.abv.denomination}] ${env.REACT_APP_TITRE}`;

    switch (location.pathname.split("/")[1].toLowerCase()) {
      case "":
        title += " : connexion";
        props.setSelectedMenuKey?.(undefined);
        break;

      case "dashboard":
        title += " : tableau de bord";
        props.setSelectedMenuKey?.(undefined);
        break;

      case "planning":
        title += " : planning";
        props.setSelectedMenuKey?.("planning");
        break;

      case "amenagements":
        title += " : amenagements";
        props.setSelectedMenuKey?.("beneficiaires");
        break;

      case "mes-interventions":
        title += " : mes interventions";
        break;

      case "services-faits":
        title += " : services faits";
        props.setSelectedMenuKey?.("services-faits");
        break;

      case "beneficiaires":
        title += " : bénéficiaires";
        props.setSelectedMenuKey?.("beneficiaires");
        break;

      case "intervenants":
        title += " : intervenants";
        props.setSelectedMenuKey?.("intervenants");
        break;

      case "demandeurs":
        title += " : demandeurs";
        props.setSelectedMenuKey?.("demandeurs");
        break;

      case "demandes":
        title += " : demandes";
        props.setSelectedMenuKey?.("demandes");
        break;

      case "administration":
        title += " : administration";
        props.setSelectedMenuKey?.("user");
        break;

      case "bilans":
        title += " : bilans";
        props.setSelectedMenuKey?.("user");
        break;

      case "profil":
        title += " : profil";
        props.setSelectedMenuKey?.("user");
        break;

      case "rgpd":
        title += " : utilisations des données";
        break;

      case "credits":
        title += " : crédits";
        break;

      case "404":
        title += " : page non trouvée";
        break;
    }

    document.title = title;
  }, [location, props]);

  return <></>;
}
