/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ComponentType, ReactNode, Suspense } from "react";
import { BrowserRouter } from "react-router";
import App from "@/App";
import { AuthProvider } from "@/auth/AuthProvider";
import Spinner from "@controls/Spinner/Spinner";
import { ErrorBoundary } from "@controls/ErrorBoundary/ErrorBoundary";
import { AccessibiliteProvider } from "@context/accessibilite/AccessibiliteContext";
import { ThemeProvider } from "@context/theme/ThemeContext";
import { ModalsProvider } from "@context/modals/ModalsContext";
import { DrawersProvider } from "@context/drawers/DrawersContext";
import { AffichageFiltresProvider } from "@context/affichageFiltres/AffichageFiltresContext";

function composeProviders(
  providers: ComponentType<{ children: ReactNode }>[],
): ComponentType<{ children: ReactNode }> {
  return providers.reduceRight((Child, Provider) => ({ children }) => (
    <Provider>
      <Child>{children}</Child>
    </Provider>
  ));
}

const AppProviders = composeProviders([
  AccessibiliteProvider,
  ThemeProvider,
  DrawersProvider,
  ModalsProvider,
  AffichageFiltresProvider,
]);

function AppAuthWrapper() {
  return (
    <AuthProvider onSuccess={() => {}}>
      <App />
    </AuthProvider>
  );
}

export default function AppWrapper() {
  return (
    <React.StrictMode>
      <ErrorBoundary>
        <Suspense fallback={<Spinner />}>
          <BrowserRouter>
            <AppProviders>
              <AppAuthWrapper />
            </AppProviders>
          </BrowserRouter>
        </Suspense>
      </ErrorBoundary>
    </React.StrictMode>
  );
}
