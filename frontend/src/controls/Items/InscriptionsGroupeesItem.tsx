/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React, { useState } from "react";
import { Button, Flex, Space, theme, Tooltip } from "antd";
import { MinusOutlined, StarFilled } from "@ant-design/icons";
import { IInscription } from "@api";
import { grouperInscriptionsParComposante } from "@lib/inscription";
import { ComposanteItem } from "@controls/Items/ComposanteItem";
import { EllipsisMiddle } from "@controls/Typography/EllipsisMiddle";

/** Au-delà de ce nombre d'inscriptions, seul le groupe de l'inscription principale est affiché. */
const NB_INSCRIPTIONS_AFFICHEES_SANS_REPLI = 2;

interface InscriptionsGroupeesItemProps {
  inscriptions?: IInscription[];
}

/**
 * Liste les inscriptions en cours, regroupées par composante, la principale (★) en tête.
 * Les autres groupes sont repliés lorsqu'il y a plus de {@link NB_INSCRIPTIONS_AFFICHEES_SANS_REPLI}
 * inscriptions et plus d'un groupe (rien à replier si tout tient dans le premier groupe).
 */
export function InscriptionsGroupeesItem({
  inscriptions,
}: InscriptionsGroupeesItemProps): React.ReactElement {
  const { token } = theme.useToken();
  const [deplie, setDeplie] = useState(false);

  const groupes = grouperInscriptionsParComposante(inscriptions);
  if (groupes.length === 0) return <MinusOutlined />;

  const nbInscriptions = groupes.reduce((total, g) => total + g.inscriptions.length, 0);
  const marquerPrincipale = nbInscriptions > 1;
  const replier = nbInscriptions > NB_INSCRIPTIONS_AFFICHEES_SANS_REPLI && groupes.length > 1;
  const groupesAffiches = replier && !deplie ? groupes.slice(0, 1) : groupes;
  const nbMasquees =
    nbInscriptions - groupesAffiches.reduce((total, g) => total + g.inscriptions.length, 0);

  return (
    <Space orientation="vertical" size={4} className="mt-05 mb-05">
      {groupesAffiches.map((groupe, index) => (
        <Space
          key={groupe.composante}
          className={index > 0 ? "mt-05" : undefined}
          orientation="vertical"
          size={2}
        >
          {groupe.composante && <ComposanteItem composanteId={groupe.composante} />}
          {groupe.inscriptions.map((inscription) => (
            <Flex key={inscription["@id"]} gap={4} align="baseline">
              {marquerPrincipale && inscription.principale && (
                <Tooltip title="Inscription principale">
                  <StarFilled
                    tabIndex={0}
                    aria-label="Inscription principale"
                    style={{ color: token.colorWarning }}
                  />
                </Tooltip>
              )}
              <EllipsisMiddle
                className="light"
                style={{ maxWidth: 350, fontSize: 15 }}
                suffixCount={12}
                content={inscription.formation?.libelle as string}
                expandable
              />
            </Flex>
          ))}
        </Space>
      ))}
      {replier && (
        <Button
          type="link"
          size="small"
          style={{ padding: 0 }}
          aria-expanded={deplie}
          onClick={() => setDeplie(!deplie)}
        >
          {deplie
            ? "Réduire"
            : `+ ${nbMasquees} autre${nbMasquees > 1 ? "s" : ""} inscription${nbMasquees > 1 ? "s" : ""}`}
        </Button>
      )}
    </Space>
  );
}
