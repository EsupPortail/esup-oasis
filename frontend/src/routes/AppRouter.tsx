/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { lazy, ReactElement, Suspense, useMemo } from "react";
import { useAuth } from "@/auth/AuthProvider";
import { Navigate, Route, Routes } from "react-router-dom";
import OAuthCallback from "@/auth/OAuthCallback";
import AppLayout from "@controls/AppLayout/AppLayout";
import { APP_ROUTES } from "@routes/AppRoutes";
import Spinner from "@controls/Spinner/Spinner";

const LoginPage = lazy(() => import("@/auth/LoginPage"));
const NotFound = lazy(() => import("@/routes/commun/NotFound"));

/**
 * Router principal de l'application.
 * Gère le chargement dynamique (lazy) en production et statique en développement.
 *
 * @returns {ReactElement} The routes for the Router component.
 */
export default function AppRouter(): ReactElement {
  const auth = useAuth();

  const routes = useMemo<ReactElement | null>(() => {
    const user = auth.user;

    // Routes accessibles par l'utilisateur connecté selon ses rôles.
    // Ce filtrage est purement ergonomique (éviter d'afficher des écrans inutilisables) :
    // il ne constitue PAS une barrière de sécurité, le JS étant modifiable par l'utilisateur.
    // Le contrôle d'accès effectif est assuré par l'API à chaque requête.
    const userAuthorizedRoutes = APP_ROUTES.filter(
      (route) =>
        route.roles === null ||
        (user && route.roles.some((rr) => user.roles.some((ru) => rr === ru))),
    ).map((route) => (
      <Route
        key={route.path}
        path={route.path}
        element={
          <Suspense fallback={<Spinner />}>
            <route.element />
          </Suspense>
        }
      />
    ));

    // Si l'utilisateur n'est pas connecté (la validité de la session est tranchée
    // par l'API : un 401 déclenche la déconnexion via HandleApiResponse)
    if (!user?.uid) {
      // Routes publiques (sans rôles requis)
      const publicRoutes = APP_ROUTES.filter((route) => route.roles === null).map((route) => (
        <Route
          key={route.path}
          path={route.path}
          element={
            <Suspense fallback={<Spinner />}>
              <route.element />
            </Suspense>
          }
        />
      ));

      return (
        <>
          <Route element={<OAuthCallback />} path="/callback" />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/" element={<LoginPage />} />
          {publicRoutes}
          <Route path="*" element={<LoginPage />} />
        </>
      );
    }

    // Si l'utilisateur est connecté
    return (
      <Route element={<AppLayout />}>
        {userAuthorizedRoutes}
        <Route path="/" element={<Navigate to="/dashboard" />} />
        <Route path="*" element={<NotFound />} />
      </Route>
    );
  }, [auth]);

  return <Routes>{routes}</Routes>;
}
