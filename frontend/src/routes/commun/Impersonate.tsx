/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useEffect } from "react";
import { useNavigate, useParams } from "react-router";
import { useAuth } from "@/auth/AuthProvider";
import {
  initialAffichageFiltres,
  useAffichageFiltres,
} from "@context/affichageFiltres/AffichageFiltresContext";
import { queryClient } from "@/queryClient";
import { env } from "@/env";
import Spinner from "@controls/Spinner/Spinner";

/**
 * Page de transition pour la prise d'identité (impersonation).
 * Garantit que la page courante est démontée avant de vider le cache React Query,
 * puis attend que auth.user soit mis à jour avec le compte impersonné avant de naviguer.
 * Évite ainsi des requêtes sur des ressources non autorisées avec le nouveau compte.
 */
export default function Impersonate() {
  const { uid } = useParams<{ uid: string }>();
  const auth = useAuth();
  const navigate = useNavigate();
  const { setAffichageFiltres } = useAffichageFiltres();

  // Étape 1 : déclencher l'impersonation (s'exécute une seule fois au montage)
  useEffect(() => {
    const canImpersonate = auth.user?.isAdmin && env.REACT_APP_ENVIRONMENT !== "production";
    if (!uid || !canImpersonate) {
      navigate("/");
      return;
    }
    setAffichageFiltres(initialAffichageFiltres.affichage, initialAffichageFiltres.filtres);
    auth.setImpersonate(uid);
  }, [auth, navigate, setAffichageFiltres, uid]);

  // Étape 2 : naviguer vers "/" uniquement quand auth.user a été mis à jour
  // (auth.loading passe à false une fois le fetch de l'utilisateur impersonné terminé)
  useEffect(() => {
    if (!auth.impersonate) return;
    if (!auth.loading) {
      queryClient.clear();
      navigate("/");
    }
  }, [auth.loading, auth.impersonate, navigate]);

  return <Spinner />;
}
