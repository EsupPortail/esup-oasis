/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { MAX_FILE_SIZE } from "@/constants";

/**
 * Types de fichiers acceptés en upload.
 * Cette liste DOIT rester alignée sur celle du backend (validation autoritaire) :
 * le contrôle côté client est un pré-filtrage UX, contournable par construction.
 */
const TYPES_FICHIERS_AUTORISES: Record<string, { mimes: string[]; extensions: string[] }> = {
  PDF: {
    mimes: ["application/pdf"],
    extensions: ["pdf"],
  },
  DOC: {
    mimes: [
      "application/msword",
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      "application/vnd.oasis.opendocument.text",
    ],
    extensions: ["doc", "docx", "odt"],
  },
  IMAGE: {
    mimes: ["image/jpeg", "image/png", "image/gif", "image/webp", "image/tiff"],
    extensions: ["jpg", "jpeg", "png", "gif", "webp", "tif", "tiff"],
  },
  VIDEO: {
    mimes: [
      "video/mp4",
      "video/webm",
      "video/ogg",
      "video/quicktime",
      "video/x-msvideo",
      "video/x-ms-wmv",
    ],
    extensions: ["mp4", "m4v", "webm", "ogv", "mov", "avi", "wmv"],
  },
  TXT: {
    mimes: ["text/plain"],
    extensions: ["txt"],
  },
};

const EXTENSIONS_AUTORISEES = Object.values(TYPES_FICHIERS_AUTORISES).flatMap(
  (type) => type.extensions,
);

const MIMES_AUTORISES = Object.values(TYPES_FICHIERS_AUTORISES).flatMap((type) => type.mimes);

/**
 * Valeur de la prop `accept` des composants Upload Ant Design :
 * filtre le sélecteur de fichiers du navigateur sur les extensions autorisées.
 */
export const ACCEPT_FICHIERS = EXTENSIONS_AUTORISEES.map((ext) => `.${ext}`).join(",");

/**
 * Pré-filtrage client d'un fichier avant upload : extension, type MIME et taille.
 *
 * @param file fichier sélectionné par l'utilisateur
 * @returns un message d'erreur destiné à l'utilisateur, ou `null` si le fichier est accepté
 */
export function validerFichier(file: {
  name?: string;
  size?: number;
  type?: string;
}): string | null {
  const extension = file.name?.split(".").pop()?.toLowerCase() ?? "";
  if (!EXTENSIONS_AUTORISEES.includes(extension)) {
    return `Ce type de fichier n'est pas accepté. Formats autorisés : ${EXTENSIONS_AUTORISEES.join(", ")}.`;
  }

  // Le type MIME fourni par le navigateur peut être vide (selon l'OS) : on ne le
  // vérifie que s'il est renseigné. Le backend fait la vérification autoritaire.
  if (file.type && !MIMES_AUTORISES.includes(file.type)) {
    return `Ce type de fichier (${file.type}) n'est pas accepté.`;
  }

  if (typeof file.size === "number" && file.size > MAX_FILE_SIZE * 1024 * 1024) {
    return `Le fichier dépasse la taille maximum autorisée (${MAX_FILE_SIZE} Mo).`;
  }

  return null;
}
