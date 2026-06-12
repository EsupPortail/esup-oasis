/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import { queryToObject } from "@utils/url";
import "@/auth/OAuthCallback.scss";
import Spinner from "@controls/Spinner/Spinner";
import { OAUTH_STATE_KEY } from "@/auth/hook/constants";
import { deliverCallbackMessage, OAuthCallbackMessage } from "@/auth/hook/callbackPayload";

const checkState = (receivedState: string | null) => {
  const state = sessionStorage.getItem(OAUTH_STATE_KEY);
  return state === receivedState;
};

/**
 * OAuthCallback component
 *
 * Gère les retours OAuth (flow redirect) : lit le hash de l'URL, valide le state,
 * transmet le payload en mémoire puis revient à la racine via une navigation
 * SPA — le hash contenant le token ne reste ni dans l'URL ni dans l'historique.
 */
const OAuthCallback = (props: { Component?: ReactElement }): ReactElement => {
  const {
    Component = (
      <div className="oauth-callback">
        <Spinner size={40} />
      </div>
    ),
  } = props;

  const navigate = useNavigate();
  // Garde contre la double exécution de l'effet (StrictMode) : le payload ne doit être délivré qu'une fois
  const deliveredRef = useRef(false);

  useEffect(() => {
    if (deliveredRef.current) return;
    deliveredRef.current = true;

    const payload = queryToObject(window.location.hash.split("#")[1]);
    const state = payload && payload.state;
    const error = payload && payload.error;

    let message: OAuthCallbackMessage;
    if (error) {
      message = { error: decodeURI(error) || "OAuth error: An error has occured." };
    } else if (state && checkState(state)) {
      message = { payload };
    } else {
      message = { error: "OAuth error: State mismatch." };
    }

    deliverCallbackMessage(message);
    // replace évite que /callback (et son hash) ne reste dans l'historique du navigateur
    navigate("/", { replace: true });
  }, [navigate]);

  return Component;
};

export default OAuthCallback;
