/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useState } from "react";
import { TYPE_EVENEMENT_RENFORT } from "@/constants";
import { Button, Form } from "antd";
import UtilisateurFormItemSelect from "@controls/Forms/UtilisateurFormItemSelect";
import { Evenement, RoleValues, service } from "@lib";
import { MinusCircleOutlined, SearchOutlined } from "@ant-design/icons";
import IntervenantRechercherDrawer from "@controls/Drawers/Intervenant/IntervenantRechercherDrawer";
import { IPartialEvenement } from "@api";

interface TabEvenementParticipantsIntervenantProps {
  evenement: Evenement | undefined;
  setEvenement: (data: IPartialEvenement | undefined, forceResetForm: boolean) => void;
  intervenantDisabled?: boolean;
}

/**
 * Renders a form item for selecting and managing event participants/intervenants.
 *
 * @param {TabEvenementParticipantsIntervenantProps} props - The component props.
 * @param {Object} [props.evenement] - The current event object.
 * @param {Function} props.setEvenement - The function to update the event object.
 * @param {boolean} [props.intervenantDisabled] - Determines if the intervenant input is disabled.
 *
 * @returns {ReactElement} The rendered component.
 */
export function TabEvenementParticipantsIntervenant({
  evenement,
  setEvenement,
  intervenantDisabled,
}: TabEvenementParticipantsIntervenantProps): ReactElement {
  const [rechercherIntervenant, setRechercherIntervenant] = useState(false);

  return (
    <div className="ant-form-item">
      <b className="semi-bold">
        {evenement?.type === TYPE_EVENEMENT_RENFORT ? "Renfort" : "Intervenant"}
      </b>
      <Form.Item
        noStyle
        name="intervenant"
        label={
          evenement?.type === TYPE_EVENEMENT_RENFORT ? (
            <b className="semi-bold">Renfort {service.sigle}</b>
          ) : (
            <b className="semi-bold">Intervenant</b>
          )
        }
        className="mt-2"
        required={evenement?.type === TYPE_EVENEMENT_RENFORT}
        rules={[
          {
            required: evenement?.type === TYPE_EVENEMENT_RENFORT,
            message: "Le champ Renfort doit être complété",
          },
        ]}
      >
        <UtilisateurFormItemSelect
          disabled={intervenantDisabled}
          style={{ width: "calc(100% - 35px)" }}
          intervenantArchive={false}
          onSelect={(value) => {
            setEvenement(
              {
                intervenant: value,
              },
              true,
            );
          }}
          placeholder={
            evenement?.type === TYPE_EVENEMENT_RENFORT
              ? "Rechercher un renfort"
              : "Rechercher un intervenant"
          }
          roleUtilisateur={
            evenement?.type === TYPE_EVENEMENT_RENFORT
              ? RoleValues.ROLE_RENFORT
              : RoleValues.ROLE_INTERVENANT
          }
        />
      </Form.Item>
      {evenement?.intervenant ? (
        <Button
          type="link"
          icon={<MinusCircleOutlined />}
          className="dynamic-delete-button m-0 p-0"
          onClick={() => {
            setEvenement(
              {
                intervenant: undefined,
              },
              true,
            );
          }}
        />
      ) : (
        <>
          <IntervenantRechercherDrawer
            afficherFiltres
            open={rechercherIntervenant}
            setOpen={setRechercherIntervenant}
            onChange={(value) => {
              setEvenement(
                {
                  intervenant: value,
                },
                true,
              );
              setRechercherIntervenant(false);
            }}
            defaultSearchOptions={{
              beneficiaire: evenement?.beneficiaires?.[0],
              "intervenant.typesEvenements": evenement?.type,
              "intervenant.campuses": evenement?.campus,
              "creneau[debut]": evenement?.debut,
              "creneau[fin]": evenement?.fin,
            }}
          />
          <Button
            type="link"
            icon={<SearchOutlined />}
            className="dynamic-delete-button m-0 p-0"
            onClick={() => {
              setRechercherIntervenant(true);
            }}
          />
        </>
      )}
    </div>
  );
}
