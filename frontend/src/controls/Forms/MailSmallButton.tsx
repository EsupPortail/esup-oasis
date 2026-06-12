/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { App, Button, Dropdown } from "antd";
import { CopyOutlined, MailOutlined, SendOutlined } from "@ant-design/icons";
import { IUtilisateur } from "@api";
import { mailtoHref } from "@utils/url";

export function MailSmallButton(props: {
  utilisateur: IUtilisateur | undefined;
  emailPerso?: boolean;
  className?: string;
  mailto?: boolean;
}) {
  const { message } = App.useApp();
  const email = props.emailPerso ? props.utilisateur?.emailPerso : props.utilisateur?.email;
  if (!email) return null;

  return (
    <Dropdown
      menu={{
        items: [
          {
            key: "copier",
            label: "Copier l'adresse email",
            icon: <CopyOutlined />,
          },
          props.mailto
            ? {
                key: "envoyer",
                label: "Envoyer un email",
                icon: <SendOutlined />,
              }
            : null,
        ],
        onClick: (e) => {
          if (e.key === "copier") {
            // Copie du mail dans le presse-papier
            navigator.clipboard.writeText(email).then(() => {
              message.success("Email copié dans le presse-papier").then();
            });
          } else if (e.key === "envoyer") {
            window.open(mailtoHref(email));
          }
        },
      }}
    >
      <Button
        size="small"
        icon={<MailOutlined />}
        className={`m-0 p-0 border-0 ${props.className || "text-text"}`}
        onClick={() => {
          // Copie du mail dans le presse-papier
          navigator.clipboard.writeText(email).then(() => {
            message.success("Email copié dans le presse-papier").then();
          });
        }}
      />
    </Dropdown>
  );
}
