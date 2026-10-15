/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React, { useState } from "react";
import { useAuth } from "@/auth/AuthProvider";
import { usePreferences } from "@context/utilisateurPreferences/UtilisateurPreferencesProvider";
import { ModalExportInscriptions } from "@controls/Table/Export/ModalExportInscriptions";
import {
  estPorteeExportInscriptions,
  PORTEE_EXPORT_PAR_DEFAUT,
  PorteeExportInscriptions,
} from "@lib/inscription";

export const PREF_KEY_EXPORT_INSCRIPTIONS = "exportInscriptions";

interface ExportInscriptions {
  /** Inscriptions à exporter, à passer à {@link formaterInscriptionsExport}. */
  portee: PorteeExportInscriptions;
  /** À brancher sur `onBeforeStart` du bouton d'export : ouvre la modale puis démarre l'export. */
  demanderPortee: (demarrer: () => void) => void;
  /** Modale de choix, à afficher à côté du bouton d'export. */
  modalPortee: React.ReactNode;
}

/**
 * Choix des inscriptions d'un export CSV. Le dernier choix est mémorisé et présélectionné à l'export
 * suivant. Un référent de composante n'est pas interrogé : seules ses inscriptions en cours sont exportées.
 */
export function useExportInscriptions(): ExportInscriptions {
  const { user } = useAuth();
  const { getPreference, setPreference } = usePreferences();
  const [porteeChoisie, setPorteeChoisie] = useState(PORTEE_EXPORT_PAR_DEFAUT);
  const [demarrage, setDemarrage] = useState<{ demarrer: () => void } | null>(null);

  const porteeImposee = !!user?.isReferentComposante && !user.isPlanificateur;
  const preference = getPreference(PREF_KEY_EXPORT_INSCRIPTIONS);
  const porteeMemorisee = estPorteeExportInscriptions(preference)
    ? preference
    : PORTEE_EXPORT_PAR_DEFAUT;

  const demanderPortee = (demarrer: () => void) => {
    if (porteeImposee) return demarrer();
    setDemarrage({ demarrer });
  };

  const modalPortee = demarrage && (
    <ModalExportInscriptions
      defaultValue={porteeMemorisee}
      onCancel={() => setDemarrage(null)}
      onOk={(portee) => {
        setPorteeChoisie(portee);
        setPreference(PREF_KEY_EXPORT_INSCRIPTIONS, portee);
        demarrage.demarrer();
        setDemarrage(null);
      }}
    />
  );

  return {
    portee: porteeImposee ? PORTEE_EXPORT_PAR_DEFAUT : porteeChoisie,
    demanderPortee,
    modalPortee,
  };
}
