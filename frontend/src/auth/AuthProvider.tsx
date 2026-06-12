/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React, { useEffect, useState } from "react";
import { Utilisateur } from "@lib";
import { message, notification } from "antd";
import useOAuth2 from "@/auth/hook/useOAuth2";

import { IUtilisateur } from "@api";
import { queryClient } from "@/queryClient";
import useLocalStorageState from "use-local-storage-state";
import { useNavigate } from "react-router-dom";
import { env } from "@/env";
import { jwtDecode } from "jwt-decode";
import { logger } from "@utils/logger";

/**
 * Contexte d'authentification exposé aux composants via `useAuth()`.
 */
export interface AuthContextType {
  user: Utilisateur | undefined;
  signOut: (callback: VoidFunction) => void;
  loading: boolean;
  error: string | null;
  authenticate: VoidFunction;
  setUser: (user: Utilisateur) => void;
  token: string | undefined;
  impersonate: string | undefined;
  setImpersonate: (impersonate: string) => void;
  removeImpersonate: VoidFunction;
}

const AuthContext = React.createContext<AuthContextType>(null!);
type State<TData = string> = TData | null;

/**
 * Fournisseur du contexte d'authentification.
 * Gère le flux OAuth2, le stockage du token JWT, l'impersonation et le chargement de l'utilisateur.
 *
 * @param children - Arbre React à envelopper.
 * @param onSuccess - Callback appelé après chargement réussi de l'utilisateur (redirection).
 */
export function AuthProvider({
  children,
  onSuccess,
}: {
  children: React.ReactNode;
  onSuccess: VoidFunction;
}): React.ReactElement {
  const navigate = useNavigate();
  const [user, setUser] = React.useState<IUtilisateur>();
  // `login` est un simple marqueur de reconnexion automatique : le cookie de session étant
  // HttpOnly, le JS ne peut pas savoir qu'une session existe sans lui. Ce n'est pas une preuve
  // d'authentification (l'API revalide chaque requête) — ne jamais y adjoindre de donnée sensible.
  const [login, setLogin, { removeItem: removeLocalStorageLogin }] =
    useLocalStorageState<State>(`login`);
  const [impersonateLS, setImpersonateLS, { removeItem: removeLocalStorageImpersonate }] =
    useLocalStorageState<State>(`impersonate`);

  const [loadingUser, setLoadingUser] = useState(false);
  const [errorUser, setErrorUser] = useState<string | null>(null);
  const [token, setToken] = useState<string>();
  const [impersonate, setImpersonate] = useState<string | undefined>(impersonateLS ?? undefined);

  // -- Déconnexion
  const signOut = (callback?: VoidFunction) => {
    setToken(undefined);
    setUser(undefined);
    setImpersonate(undefined);
    // Suppression ciblée des clés de session : ne pas effacer les préférences
    removeLocalStorageLogin();
    removeLocalStorageImpersonate();
    Object.keys(sessionStorage)
      .filter((k) => k.startsWith("oasis:filter:"))
      .forEach((k) => sessionStorage.removeItem(k));
    if (callback) setTimeout(callback, 100);
  };

  // -- Gestion de l'impersonation
  useEffect(() => {
    if (impersonate) {
      setImpersonateLS(impersonate);
    } else {
      removeLocalStorageImpersonate();
    }
    // setImpersonateLS/removeLocalStorageImpersonate sont stables (use-local-storage-state) — pas de dépendance nécessaire
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [impersonate]);

  // -- Gestion de la connexion : lorsque le login a été récupéré dans le token.
  // La validité de la session est tranchée par l'API (cookie HttpOnly) : un 401 signifie
  // simplement « pas de session », il n'y a pas d'échéance suivie côté client.
  useEffect(() => {
    let mounted = true;

    if (!env.REACT_APP_API || !(impersonate || login)) return;

    const controller = new AbortController();
    setTimeout(() => setLoadingUser(true), 0);

    fetch(
      new URL(
        `${env.REACT_APP_API_PREFIX}/utilisateurs/${impersonate || login}`,
        env.REACT_APP_API,
      ),
      {
        method: "GET",
        credentials: "include",
        headers: {
          "Content-Type": "application/ld+json",
        },
        signal: controller.signal,
      },
    )
      .then(async (userResponse) => {
        if (!mounted) return;
        setErrorUser(null);

        if (userResponse.status === 401 || userResponse.status === 403) {
          // 401 « attendu » : cookie absent ou expiré — retour silencieux à l'écran
          // de connexion, sans message d'erreur ni notification.
          removeLocalStorageLogin();
          setUser(undefined);
          setLoadingUser(false);
          return;
        }

        if (!userResponse.ok) {
          removeLocalStorageLogin();
          setUser(undefined);
          setErrorUser(`Erreur d'authentification (${userResponse.status})`);
          setLoadingUser(false);
          return;
        }

        const userData: IUtilisateur = await userResponse.json();

        if (!mounted) return;

        if (userData.roles && userData.roles.length === 1) {
          notification.error({
            title: "Erreur",
            description: "Vous ne possédez pas de rôle valide pour vous connecter à l'application.",
          });
          setLoadingUser(false);
          return;
        }

        setUser(userData);

        // Délai court pour laisser React propager l'état avant la redirection
        setTimeout(() => {
          if (!mounted) return;
          setLoadingUser(false);
          onSuccess();
        }, 500);
      })
      .catch((error) => {
        if (error.name === "AbortError") return;
        if (!mounted) return;
        removeLocalStorageLogin();
        setUser(undefined);
        logger.error(error);
        setErrorUser(error.message);
        setLoadingUser(false);
      });

    return () => {
      mounted = false;
      controller.abort();
    };
    // signOut/removeLocalStorageLogin sont des fonctions stables définies dans le même scope — les inclure créerait une boucle infinie
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [impersonate, login, env.REACT_APP_API]);

  function removeImpersonate() {
    setImpersonate(undefined);
    queryClient.clear();
    window.setTimeout(() => navigate("/"), 1000);
  }

  const { loading, error, getAuth } = useOAuth2({
    authorizeUrl: env.REACT_APP_OAUTH_PROVIDER as string,
    clientId: env.REACT_APP_OAUTH_CLIENT_ID as string,
    redirectUri: `${env.REACT_APP_FRONTEND}/callback`,
    clientUri: env.REACT_APP_FRONTEND as string,
    scope: "profile",
    responseType: "token",
    onSuccess: (payload) => {
      removeLocalStorageLogin();
      setImpersonate(undefined);

      if (!loadingUser && payload && payload.access_token) {
        setLoadingUser(true);
        fetch(
          new URL(`${env.REACT_APP_API_PREFIX}/connect/oauth/token?json=1`, env.REACT_APP_API),
          {
            method: "POST",
            credentials: "include",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              accessToken: payload?.access_token,
            }),
          },
        )
          .then((response) => {
            if (response.status === 401) {
              setErrorUser("Utilisateur inconnu");
              setLoadingUser(false);
              notification.error({
                title: <b>Erreur de connexion</b>,
                duration: 0,
                description: (
                  <p aria-label="Vous n'êtes pas autorisé à accéder à cette application">
                    Vous n'êtes pas autorisé•e à accéder à cette application.
                  </p>
                ),
              });
              return;
            }

            return response.json();
          })
          .then((apiData) => {
            if (!apiData) return;
            if (apiData.token) {
              // Exposé uniquement en local pour faciliter le débogage via les devtools React Query
              if (env.REACT_APP_ENVIRONMENT === "local") setToken(apiData.token);

              // Décodage du JWT pour extraire le login (l'expiration est gérée par
              // le cookie côté serveur : un 401 déclenchera la déconnexion)
              const { username } = jwtDecode<{ username: string }>(apiData.token as string);

              setLogin(username);
            }
          })
          .catch((err) => {
            setErrorUser("Application non accessible");
            notification.error({
              title: <b>Erreur de connexion</b>,
              duration: 0,
              description: <p>L'application est actuellement inaccessible.</p>,
            });
            setLoadingUser(false);
            logger.error(`Impossible de se connecter au serveur`, err);
          });
      }
    },
    onError: (error_) => logger.error("Error", error_),
  });

  // noinspection JSUnusedGlobalSymbols
  const authenticator = {
    user: user ? new Utilisateur(user) : undefined,
    loading: loading || loadingUser,
    error: error || errorUser,
    signOut,
    authenticate: getAuth,
    setUser: setUser,
    token: token,
    impersonate,
    setImpersonate: (uid: string) => {
      if (uid === user?.uid) {
        message.error("Vous ne pouvez pas prendre votre propre identité.").then();
        return;
      }
      setImpersonate(uid);
    },
    removeImpersonate,
  };

  return <AuthContext.Provider value={authenticator}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextType {
  const ctx = React.useContext(AuthContext);
  if (ctx === null) throw new Error("useAuth doit être utilisé dans un <AuthProvider>");
  return ctx;
}
