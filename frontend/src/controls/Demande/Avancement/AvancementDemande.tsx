/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IDemande } from "@api";
import React from "react";
import { useApi } from "@context/api/ApiProvider";
import { Flex, Space, Steps, Typography } from "antd";
import {
  ETAT_ATTENTE_CHARTES,
  ETAT_DEMANDE_CONFORME,
  ETAT_DEMANDE_EN_COURS,
  ETAT_DEMANDE_PROFIL_ACCEPTE,
  ETAT_DEMANDE_RECEPTIONNEE,
  ETAT_DEMANDE_REFUSEE,
  ETAT_DEMANDE_VALIDEE,
  EtatDemande,
  getEtatDemande,
  getEtatDemandeOrdre,
  service,
} from "@lib";
import { EtatDescription } from "@controls/Demande/EtatDescription";
import "@controls/Demande/Avancement/AvancementDemande.scss";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { ProfilItem } from "@controls/Items/ProfilItem";
import { DerniereModifDemandeLabel } from "@controls/Avatars/DerniereModifDemandeLabel";
import Spinner from "@controls/Spinner/Spinner";

interface IProps {
  demande?: IDemande;
  demandeId?: string;
  informationEmail?: boolean;
}

export function DemandeProfilAttribue(props: { demandeId: string }) {
  const { data: demande } = useApi().useGetItem({
    path: "/demandes/{id}",
    url: props.demandeId,
  });

  if (!demande?.profilAttribue) return null;

  return <ProfilItem profil={demande.profilAttribue} />;
}

export default function AvancementDemande({
  demande,
  demandeId,
  informationEmail = false,
}: IProps): React.ReactElement {
  const screens = useBreakpoint();
  const { data: demandeData } = useApi().useGetItem({
    path: "/demandes/{id}",
    url: demandeId as string,
    enabled: !!demandeId,
  });
  const item = demande ?? demandeData;

  function getSteps(etat: EtatDemande) {
    return [
      {
        title:
          getEtatDemandeOrdre(etat) > getEtatDemandeOrdre(ETAT_DEMANDE_EN_COURS)
            ? `Votre demande a été saisie`
            : `Votre saisie est en cours`,
      },
      {
        title:
          getEtatDemandeOrdre(etat) >= getEtatDemandeOrdre(ETAT_DEMANDE_RECEPTIONNEE)
            ? `Votre demande a été réceptionnée par ${service.defini}`
            : `Réception par ${service.defini}`,
      },
      {
        title:
          getEtatDemandeOrdre(etat) >= getEtatDemandeOrdre(ETAT_DEMANDE_CONFORME)
            ? `Votre demande est conforme`
            : "Examen de la conformité",
      },

      // Étape optionnelle : attente de signature de la charte
      etat === ETAT_ATTENTE_CHARTES
        ? {
            status: "process",
            title: "Attente de signature de la charte",
          }
        : null,

      {
        status:
          getEtatDemandeOrdre(etat) >= getEtatDemandeOrdre(ETAT_DEMANDE_PROFIL_ACCEPTE)
            ? ("finish" as const)
            : etat === ETAT_DEMANDE_REFUSEE
              ? ("error" as const)
              : ("wait" as const),
        title:
          getEtatDemandeOrdre(etat) >= getEtatDemandeOrdre(ETAT_DEMANDE_PROFIL_ACCEPTE)
            ? "Un profil vous a été attribué"
            : etat === ETAT_DEMANDE_REFUSEE
              ? "Demande de profil refusée"
              : `Attribution de votre profil`,
        subTitle: <DemandeProfilAttribue demandeId={item?.["@id"] as string} />,
      },

      {
        status:
          etat === ETAT_DEMANDE_VALIDEE
            ? ("process" as const)
            : etat === ETAT_DEMANDE_REFUSEE
              ? ("error" as const)
              : ("wait" as const),
        title:
          etat === ETAT_DEMANDE_VALIDEE
            ? "Demande d'accompagnement acceptée"
            : etat === ETAT_DEMANDE_REFUSEE
              ? "Demande d'accompagnement refusée"
              : `Mise en place accompagnement`,
      },
    ].filter((s) => s !== null) as [];
  }

  if (!item) return <Spinner />;

  return (
    <Flex vertical>
      <div className="sr-only">
        Avancement de votre demande :
        <ol>
          {getEtatDemandeOrdre(item?.etat as string) >
          getEtatDemandeOrdre(ETAT_DEMANDE_EN_COURS) ? (
            <li>Votre demande a été saisie</li>
          ) : (
            <li>Votre saisie est en cours</li>
          )}

          {getEtatDemandeOrdre(item?.etat as string) >
          getEtatDemandeOrdre(ETAT_DEMANDE_RECEPTIONNEE) ? (
            <li>Votre demande a été réceptionnée par {service.defini}</li>
          ) : undefined}

          {getEtatDemandeOrdre(item?.etat as string) >
          getEtatDemandeOrdre(ETAT_DEMANDE_CONFORME) ? (
            <li>Votre demande est conforme</li>
          ) : undefined}

          {item?.etat === ETAT_ATTENTE_CHARTES ? <li>Attente de signature de la charte</li> : null}

          {getEtatDemandeOrdre(item?.etat as string) >
          getEtatDemandeOrdre(ETAT_DEMANDE_PROFIL_ACCEPTE) ? (
            <li>
              Le profil suivant vous a été attribué :{" "}
              <DemandeProfilAttribue demandeId={item?.["@id"] as string} />
            </li>
          ) : item?.etat === ETAT_DEMANDE_REFUSEE ? (
            <li>Demande de profil refusée</li>
          ) : undefined}

          {item?.etat === ETAT_DEMANDE_VALIDEE ? (
            <li>Demande d'accompagnement acceptée</li>
          ) : item?.etat === ETAT_DEMANDE_REFUSEE ? (
            <li>Demande d'accompagnement refusée</li>
          ) : undefined}
        </ol>
      </div>
      <Steps
        aria-hidden={true}
        className="steps-avancement"
        items={getSteps(item?.etat as EtatDemande)}
        current={getEtatDemande(item?.etat as EtatDemande)?.etapeIndex}
        titlePlacement="vertical"
        type={screens.md ? "navigation" : "default"}
        orientation="vertical"
      />
      <Typography.Paragraph className={`mt-1 mb-0${screens.md ? " mt-3 text-center" : ""}`}>
        <span className="sr-only">État de votre demande :</span>
        {item?.etat && (
          <Space orientation="vertical">
            <EtatDescription demande={item} />
            {getEtatDemande(item?.etat as string)?.afficherDerniereModif && (
              <DerniereModifDemandeLabel demandeId={item["@id"] as string} />
            )}
          </Space>
        )}
        {informationEmail && getEtatDemande(item?.etat as string)?.afficherMessageSuiviEmail && (
          <>
            <br />
            Vous recevrez un email pour vous informer de l'évolution de votre dossier.
          </>
        )}
      </Typography.Paragraph>
    </Flex>
  );
}
