/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import ReactDOM from "react-dom/client";

import AppWrapper from "@/AppWrapper";
import { env, validateEnv } from "@/env";

// Titre par défaut depuis la configuration runtime ; PageTitle l'affine ensuite par route.
if (env.REACT_APP_TITRE) {
  document.title = `[${env.REACT_APP_ETABLISSEMENT_ABV}] ${env.REACT_APP_TITRE}`;
}

try {
  validateEnv();
} catch (e) {
  const container = document.getElementById("root") as HTMLElement;
  container.innerHTML = `
    <div style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;">
      <div style="max-width:480px;padding:32px;border:1px solid #ff4d4f;border-radius:8px;background:#fff2f0;">
        <h2 style="color:#cf1322;margin:0 0 12px">Erreur de configuration</h2>
        <p style="margin:0;color:#333">${e instanceof Error ? e.message : String(e)}</p>
        <p style="margin:12px 0 0;color:#666;font-size:13px">Vérifiez le fichier <code>window.env</code> (env-config.js).</p>
      </div>
    </div>`;
  throw e;
}

const root = ReactDOM.createRoot(document.getElementById("root") as HTMLElement);
root.render(<AppWrapper />);
