/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Button, notification, Space } from "antd";
import { CopyOutlined, LoginOutlined } from "@ant-design/icons";
import { NavigateFunction } from "react-router-dom";
import { ReactElement } from "react";
import { RequestMethod } from "@context/api/ApiProvider";
import { queryClient } from "@/queryClient";
import { AuthContextType } from "@/auth/AuthProvider";
import { logger } from "@utils/logger";

/** Structure d'une notification d'erreur affichée via Ant Design `notification.error`. Passer une instance à `onError` pour intercepter l'erreur avant son affichage global. */
export interface IErreurNotification {
  title: string;
  description: ReactElement;
  statusText?: string;
  duration: number;
}

// Clés des erreurs en cours d'affichage (TTL 1,5 s) pour éviter les doublons.
// Utiliser un Set plutôt qu'un objet mutable unique permet à deux erreurs
// simultanées différentes d'être toutes les deux affichées sans s'écraser.
const activeErrors = new Set<string>();

// Garde contre les rafales de 401 : une seule déconnexion doit être déclenchée
// même si plusieurs requêtes échouent simultanément.
let signOutEnCours = false;

function getErrorKey(notif: IErreurNotification): string {
  return `${notif.title}|${notif.statusText ?? ""}`;
}

/**
 * Traite la réponse brute d'un `fetch` API Platform et lève une erreur React Query si le statut HTTP ≥ 400.
 *
 * Comportements non évidents :
 * - **401** : déconnecte immédiatement l'utilisateur (`auth.signOut`) et vide le cache React Query (une seule fois en cas de rafale).
 * - **204** : retourne `undefined` (DELETE réussi).
 * - Erreurs dupliquées (même titre + statusText dans la fenêtre de 1,5 s) : affichées une seule fois.
 * - Si `onError` est fourni, il reçoit la notification et supprime l'affichage global (`notification.error`).
 */
export async function handleApiResponse(
  requestMethod: RequestMethod,
  response: Response,
  navigate: NavigateFunction,
  auth: AuthContextType,
  options?: unknown,
  additionalInfos?: string,
  onError?: (notif: IErreurNotification) => void,
) {
  function afficherErreur(notif: IErreurNotification) {
    if (onError) {
      onError(notif);
      return;
    }

    const key = getErrorKey(notif);
    if (!activeErrors.has(key)) {
      activeErrors.add(key);
      notification.error({ ...notif, duration: notif.duration || 5 });
      logger.error(response, options);

      // Fenêtre de déduplication de 1,5 s : même erreur → une seule notification
      window.setTimeout(() => activeErrors.delete(key), 1500);
    }
  }

  if (response.status >= 400) {
    try {
      let notif: IErreurNotification = {
        title: "Erreur",
        description: (
          <>
            Erreur {response.status} : {response.statusText || "Erreur inconnue"}
          </>
        ),
        statusText: response.statusText || "Erreur inconnue",
        duration: 5,
      };

      const data = await response.json();
      if (response.status === 401) {
        // Déconnexion immédiate : l'UI ne doit pas continuer à afficher des données
        // alors que la session est invalide. La notification (portail Ant Design)
        // reste visible après la navigation.
        if (!signOutEnCours) {
          signOutEnCours = true;
          queryClient.clear();
          auth.signOut(() => {
            navigate("/");
            signOutEnCours = false;
          });
        }
        notif = {
          title: "Erreur d'authentification",
          description: (
            <Space orientation="vertical">
              <div>Votre session a expiré. Veuillez vous reconnecter.</div>
              <Button
                icon={<LoginOutlined />}
                onClick={() => window.location.assign(window.location.origin)}
              >
                Se reconnecter
              </Button>
            </Space>
          ),
          statusText: "Votre session a expiré. Veuillez vous reconnecter.",
          duration: 0,
        };
      } else if (data["hydra:description"] || data.detail) {
        const msg = data["hydra:description"] || data.detail;
        notif = {
          title: "Une erreur a été détectée",
          statusText: msg,
          duration: 5,
          description: (
            <>
              Erreur {response.status} : {msg}
              <div className="mt-2">
                <Button
                  icon={<CopyOutlined />}
                  onClick={() => {
                    navigator.clipboard.writeText(
                      `Statut : ${response.status}\n` +
                        `Description : ${msg}\n\n` +
                        `Méthode : ${requestMethod}\n` +
                        `URL : ${response.url}${
                          options ? `\nOptions : ${JSON.stringify(options, null, 2)}` : ""
                        }\n\n` +
                        `Infos supplémentaires : ${additionalInfos || "-"}`,
                    );
                  }}
                >
                  Copier
                </Button>
              </div>
            </>
          ),
        };
      }

      afficherErreur(notif);
    } catch {
      afficherErreur({
        description: (
          <>
            Erreur {response.status} : {response.statusText || "Erreur inconnue"}
          </>
        ),
        title: "Erreur",
        statusText: response.statusText || "Erreur inconnue",
        duration: 5,
      });
    }

    throw new Error(`HTTP error: ${response.status} ${response.statusText}`);
  }

  if (response.status === 204) return;

  return response.json();
}
