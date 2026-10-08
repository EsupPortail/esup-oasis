/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { App, Button, Dropdown, Popconfirm, Space, Tooltip } from "antd";
import { useApi } from "@context/api/ApiProvider";
import {
  CheckCircleFilled,
  EditOutlined,
  ExclamationCircleOutlined,
  EyeOutlined,
  FileDoneOutlined,
  ReloadOutlined,
  SendOutlined,
} from "@ant-design/icons";
import React from "react";
import { useAuth } from "@/auth/AuthProvider";
import { QK_BENEFICIAIRES, QK_UTILISATEURS_DECISIONS, QK_UTILISATEURS_ITEM } from "@api";
import apiDownloader from "@utils/apiDownloader";
import { EtatDecisionEtablissement } from "@controls/Avatars/DecisionEtablissementAvatar";
import { ModalDecisionObservations } from "@controls/Modals/ModalDecisionObservations";
import { queryClient } from "@/queryClient";
import { env } from "@/env";
import { decisionEtab } from "@lib";

export function BoutonDecisionEtab(props: { utilisateurId: string }) {
  const auth = useAuth();
  const [loading, setLoading] = React.useState<boolean>(false);
  const [observationsOpen, setObservationsOpen] = React.useState<boolean>(false);
  const { message } = App.useApp();
  const { data: utilisateur } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: props.utilisateurId,
    enabled: !!props.utilisateurId,
  });

  const mutateDecisionEtab = useApi().usePatch({
    path: "/utilisateurs/{uid}/decisions/{annee}",
    invalidationQueryKeys: [
      QK_BENEFICIAIRES,
      QK_UTILISATEURS_ITEM,
      QK_UTILISATEURS_DECISIONS,
      props.utilisateurId,
    ],
    onSuccess: (data) => {
      setLoading(false);
      if (data.etat === EtatDecisionEtablissement.EDITE) {
        message.success(`${decisionEtab.Denomination} : envoyé${decisionEtab.accordE}`).then();
      } else if (data.etat === EtatDecisionEtablissement.VALIDE) {
        message.success(`Demande d'édition ${decisionEtab.de} envoyée`).then();
      }
    },
    onError: () => {
      setLoading(false);
      message.error(`Erreur lors du traitement ${decisionEtab.de}`).then();
    },
  });

  if (!utilisateur || !utilisateur.decisionAmenagementAnneeEnCours) {
    return <></>;
  }

  const decisionIri = utilisateur.decisionAmenagementAnneeEnCours["@id"] as string;
  const observationsMenuItem = {
    key: "observations",
    icon: <EditOutlined />,
    label: "Date de l'avis médical / observations",
    onClick: () => setObservationsOpen(true),
  };
  const observationsModal = (
    <ModalDecisionObservations
      open={observationsOpen}
      setOpen={setObservationsOpen}
      decisionId={decisionIri}
      utilisateurId={props.utilisateurId}
    />
  );

  // le serveur dit si la date de l'avis médical conditionne l'édition : l'interface annonce son refus sans le deviner
  const dateAvisMedecinManquante =
    !!utilisateur.decisionAmenagementAnneeEnCours.dateAvisMedecinRequise &&
    !utilisateur.decisionAmenagementAnneeEnCours.dateAvisMedecin;
  const messageDateManquante =
    "Veuillez saisir une date d'avis médical afin de générer le document.";
  const alerteDateManquante = dateAvisMedecinManquante ? (
    <Tooltip title={messageDateManquante}>
      <Space className="text-danger mr-2">
        <ExclamationCircleOutlined />
        Date manquante
      </Space>
    </Tooltip>
  ) : null;

  switch (utilisateur.decisionAmenagementAnneeEnCours.etat) {
    case EtatDecisionEtablissement.ATTENTE_VALIDATION_CAS:
      return (
        <>
          {observationsModal}
          {alerteDateManquante}
          <Tooltip title="En attente validation CAS">
            <Dropdown
              menu={{
                items: [
                  {
                    key: "apercu",
                    icon: <EyeOutlined />,
                    label: `Aperçu ${decisionEtab.de}`,
                    onClick: () => {
                      setLoading(true);
                      apiDownloader(
                        `${env.REACT_APP_API}${decisionIri}`,
                        auth,
                        {
                          Accept: "application/pdf",
                        },
                        `${env.REACT_APP_TITRE?.toLocaleUpperCase()}_DecisionEtablissement.pdf`,
                        () => setLoading(false),
                        () => setLoading(false),
                      ).then();
                    },
                  },
                  observationsMenuItem,
                  {
                    type: "divider",
                    key: "divider",
                  },
                  {
                    key: "send",
                    icon: <SendOutlined />,
                    disabled: dateAvisMedecinManquante,
                    label: dateAvisMedecinManquante ? (
                      <Tooltip title={messageDateManquante}>
                        <span>
                          {auth.user?.isAdmin
                            ? `Envoyer ${decisionEtab.defini}`
                            : `Demander l'édition ${decisionEtab.de}`}
                        </span>
                      </Tooltip>
                    ) : (
                      <Popconfirm
                        title={
                          auth.user?.isAdmin
                            ? `Envoyer ${decisionEtab.defini} ?`
                            : `Demander l'édition ${decisionEtab.de} ?`
                        }
                        onConfirm={() => {
                          setLoading(true);
                          mutateDecisionEtab.mutate({
                            data: {
                              etat: auth.user?.isAdmin
                                ? EtatDecisionEtablissement.EDITION_DEMANDEE
                                : EtatDecisionEtablissement.VALIDE,
                            },
                            "@id": decisionIri,
                          });
                        }}
                      >
                        <Button loading={loading} type="text" className="p-0 m-0 no-hover">
                          {auth.user?.isAdmin
                            ? `Envoyer ${decisionEtab.defini}`
                            : `Demander l'édition ${decisionEtab.de}`}
                        </Button>
                      </Popconfirm>
                    ),
                  },
                ],
              }}
            >
              <Button
                loading={loading}
                icon={<FileDoneOutlined />}
                className="text-warning border-orange mr-2"
              >
                {decisionEtab.Denomination} en attente
              </Button>
            </Dropdown>
          </Tooltip>
        </>
      );

    case EtatDecisionEtablissement.VALIDE:
      return (
        <>
          {observationsModal}
          {alerteDateManquante}
          <Dropdown
            menu={{
              items: [
                {
                  key: "apercu",
                  icon: <EyeOutlined />,
                  label: `Aperçu ${decisionEtab.de}`,
                  onClick: () => {
                    setLoading(true);
                    apiDownloader(
                      `${env.REACT_APP_API}${decisionIri}`,
                      auth,
                      {
                        Accept: "application/pdf",
                      },
                      `${env.REACT_APP_TITRE?.toLocaleUpperCase()}_DecisionEtablissement.pdf`,
                      () => setLoading(false),
                      () => setLoading(false),
                    ).then();
                  },
                },
                observationsMenuItem,
                auth.user?.isAdmin
                  ? {
                      type: "divider",
                      key: "divider",
                    }
                  : null,
                auth.user?.isAdmin
                  ? {
                      key: "send",
                      icon: <SendOutlined />,
                      disabled: dateAvisMedecinManquante,
                      label: dateAvisMedecinManquante ? (
                        <Tooltip title={messageDateManquante}>
                          <span>Envoyer {decisionEtab.defini}</span>
                        </Tooltip>
                      ) : (
                        `Envoyer ${decisionEtab.defini}`
                      ),
                      onClick: dateAvisMedecinManquante
                        ? undefined
                        : () => {
                            setLoading(true);
                            mutateDecisionEtab.mutate({
                              data: {
                                etat: EtatDecisionEtablissement.EDITION_DEMANDEE,
                              },
                              "@id": decisionIri,
                            });
                          },
                    }
                  : null,
              ],
            }}
          >
            <Button disabled loading={loading} icon={<FileDoneOutlined />} className="mr-2">
              {auth.user?.isAdmin
                ? `Éditer ${decisionEtab.defini}`
                : `${decisionEtab.Denomination} demandé${decisionEtab.accordE}`}
            </Button>
          </Dropdown>
        </>
      );

    case EtatDecisionEtablissement.EDITION_DEMANDEE:
      return (
        <Space orientation="vertical" size={0}>
          <Button
            loading={loading}
            icon={<FileDoneOutlined />}
            className="mr-2"
            onClick={() => {
              setLoading(true);
              apiDownloader(
                `${env.REACT_APP_API}${decisionIri}`,
                auth,
                {
                  Accept: "application/pdf",
                },
                `${env.REACT_APP_TITRE?.toLocaleUpperCase()}_DecisionEtablissement.pdf`,
                () => setLoading(false),
                () => setLoading(false),
              ).then();
            }}
          >
            {decisionEtab.Denomination} en cours d'envoi
          </Button>
          <Space className="legende">
            <div>
              {decisionEtab.Defini} sera envoyé{decisionEtab.accordE} dans les prochaines minutes.
            </div>
            <Tooltip title="Rafraîchir" placement="bottom">
              <Button
                icon={<ReloadOutlined />}
                size="small"
                type="link"
                className="m-0"
                onClick={() => {
                  queryClient
                    .invalidateQueries({
                      queryKey: [props.utilisateurId],
                    })
                    .then();
                }}
              />
            </Tooltip>
          </Space>
        </Space>
      );

    case EtatDecisionEtablissement.EDITE:
      return (
        <Tooltip title={`${decisionEtab.Denomination} : envoyé${decisionEtab.accordE}`}>
          <Button
            loading={loading}
            onClick={() => {
              setLoading(true);
              apiDownloader(
                `${env.REACT_APP_API}${decisionIri}`,
                auth,
                {
                  Accept: "application/pdf",
                },
                `${env.REACT_APP_TITRE?.toLocaleUpperCase()}_DecisionEtablissement.pdf`,
                () => setLoading(false),
                () => setLoading(false),
              ).then();
            }}
            icon={<CheckCircleFilled />}
            className={`mr-2 ${EtatDecisionEtablissement.EDITE ? "text-success border-green-light" : ""}`}
          >
            {decisionEtab.Denomination} : envoyé{decisionEtab.accordE}
          </Button>
        </Tooltip>
      );

    default:
      return <></>;
  }
}
