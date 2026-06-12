/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Breakpoint, Button } from "antd";
import { IUtilisateur } from "@api";
import { PhoneOutlined } from "@ant-design/icons";
import { useAuth } from "@/auth/AuthProvider";
import Highlighter from "react-highlight-words";
import { removeAccents } from "@utils/string";
import { mailtoHref } from "@utils/url";

interface IUtilisateurBeneficiaire {
  utilisateur?: IUtilisateur;
  showAvatar?: boolean;
  responsive?: Breakpoint;
  showEmail?: boolean;
  showTelephone?: boolean;
  inverserNomPrenom?: boolean;
  highlight?: string;
}

/**
 * Renders a Beneficiaireutilisateur component with avatar, name, and email.
 *
 * @param {IUtilisateurBeneficiaire} options - The options object.
 * @param {IUtilisateur} [options.utilisateur] - The beneficiaire object.
 * @param {boolean} [options.showEmail=false] - Whether to show the email or not.
 *
 * @returns {ReactElement} The rendered Beneficiaireutilisateur component.
 */
export function UtilisateurContent({
  utilisateur,
  showEmail = false,
  showTelephone = false,
  inverserNomPrenom = false,
  highlight,
}: IUtilisateurBeneficiaire): ReactElement {
  const user = useAuth().user;
  return (
    <div style={{ lineHeight: 1.2 }}>
      <span>
        {inverserNomPrenom ? (
          <>
            <span className="semi-bold">
              <Highlighter
                textToHighlight={utilisateur?.nom?.toLocaleUpperCase() ?? ""}
                searchWords={[removeAccents(highlight ?? "")]}
              />
            </span>{" "}
            <span className="light">
              <Highlighter
                searchWords={[removeAccents(highlight ?? "")]}
                textToHighlight={utilisateur?.prenom ?? ""}
              />
            </span>
          </>
        ) : (
          <>
            <span className="light">
              <Highlighter
                searchWords={[removeAccents(highlight ?? "")]}
                textToHighlight={utilisateur?.prenom ?? ""}
              />
            </span>{" "}
            <Highlighter
              textToHighlight={utilisateur?.nom?.toLocaleUpperCase() ?? ""}
              searchWords={[removeAccents(highlight ?? "")]}
            />
          </>
        )}
      </span>
      {showEmail && utilisateur?.email && (
        <Button
          size="small"
          type="text"
          className="legende fs-08 m-0 p-0 d-block contrast-no-border"
          onClick={() => {
            window.location.href = mailtoHref(utilisateur?.email ?? "");
          }}
          aria-label={`Envoyer un email au gestionnaire (${utilisateur?.prenom} ${utilisateur?.nom})`}
        >
          {utilisateur?.email}
        </Button>
      )}
      {showTelephone && user?.isPlanificateur && utilisateur?.telPerso && (
        <div className="legende fs-09 m-0 p-0">
          <PhoneOutlined className="mr-05" />
          <span>{utilisateur?.telPerso}</span>
        </div>
      )}
    </div>
  );
}
