/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Avatar, Card, Empty, List, Tag, Tooltip } from "antd";
import React, { ReactElement } from "react";
import { IInscription, IUtilisateur } from "@api";
import { getLibellePeriode, isEnCoursSurPeriode } from "@utils/dates";
import { ComposanteItem } from "@controls/Items/ComposanteItem";
import { etablissement } from "@lib";
import { CheckOutlined, PlusOutlined } from "@ant-design/icons";

interface ITabScolariteProps {
  utilisateur: IUtilisateur;
}

interface ITabScolariteItemProps {
  inscription: IInscription;
  titleClassName?: string;
}

/**
 * Renders a single item in the TabScolarite component.
 *
 * @param {ITabScolariteItemProps} props - The props object.
 * @param {IInscription} props.inscription - The inscription object containing information about the item.
 *
 * @return {ReactElement} - The rendered item component.
 */
export function ScolariteListItem({
  inscription,
  titleClassName = "text-primary",
}: ITabScolariteItemProps): ReactElement {
  return (
    <Card className="mb-1 mt-1">
      <Card.Meta
        avatar={
          isEnCoursSurPeriode(inscription.debut, inscription.fin) ? (
            <Tooltip title="En cours">
              <Avatar
                size="small"
                className="bg-success"
                icon={<CheckOutlined className="fs-08" aria-hidden />}
              >
                <CheckOutlined className="mt-1" />
              </Avatar>
            </Tooltip>
          ) : (
            <Tooltip title="Terminé">
              <Avatar
                size="small"
                icon={<PlusOutlined rotate={45} className="fs-08 text-text" aria-hidden />}
              />
            </Tooltip>
          )
        }
        title={
          <div style={{ whiteSpace: "wrap", lineHeight: 1.25 }} className={titleClassName}>
            <div className="mb-1">
              {inscription.formation?.libelle}
              {inscription.niveau && (
                <Tag color="blue" style={{ marginLeft: 8 }}>
                  {inscription.niveau}
                </Tag>
              )}
              {inscription.redoublant && (
                <Tag color="orange" style={{ marginLeft: 8 }}>
                  Redoublant
                </Tag>
              )}
              {inscription.codeCursusAmenage && (
                <Tooltip title={inscription.libelleCursusAmenage ?? inscription.codeCursusAmenage}>
                  <Tag color="purple" style={{ marginLeft: 8 }}>
                    Cursus adapté
                  </Tag>
                </Tooltip>
              )}
            </div>
            <ComposanteItem composanteId={inscription.formation?.composante} />
            {inscription.codeEtape && (
              <div className="text-secondary fs-08 mt-1">Étape&nbsp;: {inscription.codeEtape}</div>
            )}
            {inscription.formation?.diplome && (
              <div className="text-secondary fs-08 mt-1">
                Diplôme&nbsp;: {inscription.formation.diplome}
              </div>
            )}
            {inscription.formation?.discipline && (
              <div className="text-secondary fs-08 mt-1">
                Discipline&nbsp;: {inscription.formation.discipline}
              </div>
            )}
          </div>
        }
        description={getLibellePeriode(inscription.debut, inscription.fin, "MMM")}
      />
    </Card>
  );
}

/**
 * Renders the "TabScolarite" component.
 * This component displays the user's registrations.
 *
 * @param {ITabScolariteProps} props - The component props.
 * @param {IUtilisateur} props.utilisateur - The user object containing the registrations.
 *
 * @returns {ReactElement} The rendered component.
 */
export function TabScolarite({ utilisateur }: ITabScolariteProps): ReactElement {
  return (
    <>
      <p className="semi-bold">Inscriptions {etablissement.abv.a}</p>
      {utilisateur.inscriptions?.length === 0 ? (
        <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Aucune inscription" />
      ) : (
        <List className="ant-list-radius no-hover">
          {utilisateur.inscriptions?.map((inscription) => (
            <ScolariteListItem
              key={inscription?.formation?.codeExterne}
              inscription={inscription}
            />
          ))}
        </List>
      )}
    </>
  );
}
