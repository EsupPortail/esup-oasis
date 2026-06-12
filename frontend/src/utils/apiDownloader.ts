/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { message } from "antd";
import { AuthContextType } from "@/auth/AuthProvider";
import { env } from "@/env";

/**
 * Neutralise les caractères dangereux d'un nom de fichier (séparateurs de chemin,
 * caractères de contrôle) avant de l'utiliser comme attribut `download`.
 */
function sanitizeFilename(filename: string): string {
  // eslint-disable-next-line no-control-regex
  const sain = filename.replace(/[/\\\x00-\x1f]/g, "_").trim();
  return sain || "fichier";
}

/**
 * Télécharger un fichier généré par l'API en utilisant un lien de téléchargement direct.
 *
 * Si un header `Accept` est fourni, le `Content-Type` de la réponse est vérifié :
 * une réponse d'un autre type (ex. une page HTML à la place d'un PDF) est rejetée.
 *
 * @param {string} url - The URL of the file to download.
 * @param auth
 * @param {HeadersInit} headers - The additional headers to include in the request header.
 * @param {string} [filename=APP_TITRE] - The name to give to the downloaded file.
 * @param {function} [onSuccess] - The function to call after the download is successful.
 *
 * @param onError
 * @throws {Error} If an error occurs during the download.
 */
const apiDownloader = async (
  url: string,
  auth: AuthContextType,
  headers: HeadersInit,
  filename: string = env.REACT_APP_TITRE?.toLocaleUpperCase() || "APP",
  onSuccess?: () => void,
  onError?: () => void,
) => {
  let fetchOptions: RequestInit = {
    method: "GET",
    credentials: "include",
    headers,
  };

  if (auth?.impersonate) {
    fetchOptions = {
      ...fetchOptions,
      headers: {
        ...fetchOptions.headers,
        "X-Switch-User": auth.impersonate,
      },
    };
  }

  fetch(url, fetchOptions)
    .then((response) => {
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const accept = new Headers(headers).get("Accept");
      if (accept && accept !== "*/*") {
        const typeAttendu = accept.split(";")[0].trim();
        const contentType = response.headers.get("Content-Type") ?? "";
        if (!contentType.startsWith(typeAttendu)) {
          throw new Error(`Type de contenu inattendu : ${contentType || "inconnu"}`);
        }
      }

      return response.blob();
    })
    .then((blob) => {
      const privateUrl = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.style.display = "none";
      a.href = privateUrl;
      a.download = sanitizeFilename(filename);
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(privateUrl);

      if (onSuccess) {
        onSuccess();
      }
    })
    .catch(() => {
      message.error("Erreur lors du téléchargement du fichier");
      onError?.();
    });
};

export default apiDownloader;
