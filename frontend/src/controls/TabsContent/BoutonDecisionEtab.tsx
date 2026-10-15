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
import { decisionEtab, depotEnCours, intervalleSuiviEnvoi } from "@lib";
import dayjs from "dayjs";

/** États de signature électronique, absents si la décision n'est pas passée par la signature. */
export enum EtatSignatureDecision {
  EN_SIGNATURE = "EN_SIGNATURE",
  SIGNEE = "SIGNEE",
  REFUSEE = "REFUSEE",
  EXPIREE = "EXPIREE",
  ERREUR = "ERREUR",
  REMPLACEE = "REMPLACEE",
}

/** Libellés du bouton et de sa légende, null pour une décision envoyée par e-mail. */
export function libellesSignature(
  etatSignature: string | null | undefined,
  derniereVerification: string | null | undefined,
): { bouton: string; legende: string; enErreur: boolean } | null {
  const verification = derniereVerification
    ? ` Dernière vérification le ${dayjs(derniereVerification).format("DD/MM/YYYY à HH:mm")}.`
    : "";

  switch (etatSignature) {
    case EtatSignatureDecision.EN_SIGNATURE:
      return {
        bouton: `${decisionEtab.Denomination} en signature`,
        legende: `${decisionEtab.Defini} est en cours de signature électronique.${verification}`,
        enErreur: false,
      };
    case EtatSignatureDecision.REFUSEE:
      return {
        bouton: `Signature ${decisionEtab.de} refusée`,
        legende: `Un signataire a refusé ${decisionEtab.defini} dans le parapheur électronique.${verification}`,
        enErreur: true,
      };
    case EtatSignatureDecision.EXPIREE:
      return {
        bouton: `Signature ${decisionEtab.de} interrompue`,
        legende: `Le circuit de signature s'est interrompu sans aboutir.${verification}`,
        enErreur: true,
      };
    case EtatSignatureDecision.ERREUR:
      return {
        bouton: `Erreur de signature ${decisionEtab.de}`,
        legende: `La signature électronique a échoué côté parapheur.${verification}`,
        enErreur: true,
      };
    case EtatSignatureDecision.REMPLACEE:
      return {
        bouton: `${decisionEtab.Denomination} remplacé${decisionEtab.accordE}`,
        legende: "Le document a été remplacé dans le parapheur électronique.",
        enErreur: false,
      };
    default:
      return null;
  }
}

export function BoutonDecisionEtab(props: { utilisateurId: string }) {
  const auth = useAuth();
  const [loading, setLoading] = React.useState<boolean>(false);
  const [observationsOpen, setObservationsOpen] = React.useState<boolean>(false);
  const [verification, setVerification] = React.useState<boolean>(false);
  const { message } = App.useApp();
  const { data: utilisateur } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: props.utilisateurId,
    enabled: !!props.utilisateurId,
    refetchInterval: intervalleSuiviEnvoi,
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
      } else if (data.etat === EtatDecisionEtablissement.ATTENTE_VALIDATION_CAS) {
        message.success(`${decisionEtab.Denomination} repris${decisionEtab.accordE}`).then();
      }
    },
    onError: () => {
      setLoading(false);
      message.error(`Erreur lors du traitement ${decisionEtab.de}`).then();
    },
  });

  // décision en signature : le rafraîchissement interroge le parapheur sans attendre le suivi planifié
  const mutateVerification = useApi().usePatch({
    path: "/utilisateurs/{uid}/decisions/{annee}",
    invalidationQueryKeys: [
      QK_BENEFICIAIRES,
      QK_UTILISATEURS_ITEM,
      QK_UTILISATEURS_DECISIONS,
      props.utilisateurId,
    ],
    onSuccess: () => setVerification(false),
    onError: () => {
      setVerification(false);
      message
        .warning(
          "Le parapheur électronique n'a pas pu être interrogé : l'état sera mis à jour au prochain passage du suivi.",
        )
        .then();
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

  // le serveur dit ce qui empêche l'édition, date de l'avis médical ou circuit de signature : l'interface
  // annonce son refus sans le deviner
  const dateAvisMedecinManquante =
    !!utilisateur.decisionAmenagementAnneeEnCours.dateAvisMedecinRequise &&
    !utilisateur.decisionAmenagementAnneeEnCours.dateAvisMedecin;
  const messageDateManquante =
    "Veuillez saisir une date d'avis médical afin de générer le document.";
  const motifBlocage = dateAvisMedecinManquante
    ? messageDateManquante
    : (utilisateur.decisionAmenagementAnneeEnCours.motifSignatureImpossible ?? null);
  const alerteDateManquante = dateAvisMedecinManquante ? (
    <Tooltip title={messageDateManquante}>
      <Space className="text-danger mr-2">
        <ExclamationCircleOutlined />
        Date manquante
      </Space>
    </Tooltip>
  ) : null;
  const refusee =
    utilisateur.decisionAmenagementAnneeEnCours.etat === EtatDecisionEtablissement.REFUSEE;
  // ce que rapporte le parapheur, pour une décision en signature ou refusée
  const signature =
    refusee ||
    utilisateur.decisionAmenagementAnneeEnCours.etat === EtatDecisionEtablissement.EN_SIGNATURE
      ? libellesSignature(
          utilisateur.decisionAmenagementAnneeEnCours.etatSignature,
          utilisateur.decisionAmenagementAnneeEnCours.derniereVerificationSignature,
        )
      : null;
  const dateSignature = utilisateur.decisionAmenagementAnneeEnCours.dateSignature;

  switch (utilisateur.decisionAmenagementAnneeEnCours.etat) {
    // une décision refusée se reprend, puis se corrige et se redemande comme une décision en attente
    case EtatDecisionEtablissement.REFUSEE:
    case EtatDecisionEtablissement.ATTENTE_VALIDATION_CAS:
      return (
        <>
          {observationsModal}
          {refusee ? null : alerteDateManquante}
          <Tooltip title={refusee ? signature?.legende : "En attente validation CAS"}>
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
                  refusee ? null : observationsMenuItem,
                  {
                    type: "divider",
                    key: "divider",
                  },
                  refusee
                    ? {
                        key: "reprise",
                        icon: <EditOutlined />,
                        label: (
                          <Popconfirm
                            title={`Reprendre ${decisionEtab.defini} ?`}
                            description="Ses aménagements et avis de santé redeviendront modifiables."
                            onConfirm={() => {
                              setLoading(true);
                              mutateDecisionEtab.mutate({
                                data: { etat: EtatDecisionEtablissement.ATTENTE_VALIDATION_CAS },
                                "@id": decisionIri,
                              });
                            }}
                          >
                            <Button loading={loading} type="text" className="p-0 m-0 no-hover">
                              Reprendre {decisionEtab.defini}
                            </Button>
                          </Popconfirm>
                        ),
                      }
                    : {
                        key: "send",
                        icon: <SendOutlined />,
                        disabled: !!motifBlocage,
                        label: motifBlocage ? (
                          <Tooltip title={motifBlocage}>
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
                className={
                  refusee ? "text-danger border-error mr-2" : "text-warning border-orange mr-2"
                }
              >
                {refusee
                  ? (signature?.bouton ?? `Signature ${decisionEtab.de} refusée`)
                  : `${decisionEtab.Denomination} en attente`}
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
                      disabled: !!motifBlocage,
                      label: motifBlocage ? (
                        <Tooltip title={motifBlocage}>
                          <span>Envoyer {decisionEtab.defini}</span>
                        </Tooltip>
                      ) : (
                        `Envoyer ${decisionEtab.defini}`
                      ),
                      onClick: motifBlocage
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

    case EtatDecisionEtablissement.EN_SIGNATURE:
    case EtatDecisionEtablissement.EDITION_DEMANDEE:
      return (
        <Space orientation="vertical" size={0}>
          <Button
            loading={loading}
            icon={<FileDoneOutlined />}
            className={`mr-2 ${signature?.enErreur ? "text-danger border-error" : ""}`}
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
            {signature?.bouton ?? `${decisionEtab.Denomination} en cours d'envoi`}
          </Button>
          <Space className="legende">
            <div>
              {signature?.legende ??
                `${decisionEtab.Defini} sera envoyé${decisionEtab.accordE} dans les prochaines minutes.`}
            </div>
            <Tooltip
              title={
                utilisateur.decisionAmenagementAnneeEnCours.etat ===
                  EtatDecisionEtablissement.EN_SIGNATURE && !depotEnCours(utilisateur)
                  ? "Vérifier auprès du parapheur"
                  : "Rafraîchir"
              }
              placement="bottom"
            >
              <Button
                icon={<ReloadOutlined />}
                size="small"
                type="link"
                className="m-0"
                loading={verification}
                onClick={() => {
                  // en cours de dépôt, le parapheur n'a pas encore le document : on relit la fiche
                  if (
                    utilisateur.decisionAmenagementAnneeEnCours?.etat ===
                      EtatDecisionEtablissement.EN_SIGNATURE &&
                    !depotEnCours(utilisateur)
                  ) {
                    setVerification(true);
                    mutateVerification.mutate({
                      "@id": utilisateur.decisionAmenagementAnneeEnCours["@id"] as string,
                      data: { verifierSignature: true },
                    });
                    return;
                  }
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
        <Tooltip
          title={
            dateSignature
              ? `${decisionEtab.Denomination} : signé${decisionEtab.accordE} électroniquement le ${dayjs(dateSignature).format("DD/MM/YYYY")}`
              : `${decisionEtab.Denomination} : envoyé${decisionEtab.accordE}`
          }
        >
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
            {dateSignature
              ? `${decisionEtab.Denomination} : signé${decisionEtab.accordE}`
              : `${decisionEtab.Denomination} : envoyé${decisionEtab.accordE}`}
          </Button>
        </Tooltip>
      );

    default:
      return <></>;
  }
}
