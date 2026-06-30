/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { ReactElement, useEffect, useState } from "react";
import { Button, Image, Tooltip } from "antd";
import { useApi } from "@context/api/ApiProvider";
import { useAuth } from "@/auth/AuthProvider";
import Spinner from "@controls/Spinner/Spinner";
import { EyeOutlined } from "@ant-design/icons";
import type { ButtonType } from "antd/es/button/buttonHelpers";
import { env } from "@/env";

export default function TelechargementImagePreview(props: {
  telechargementId?: string;
  width?: string | number;
  height?: string | number;
  className?: string;
  type?: ButtonType;
}): ReactElement {
  const auth = useAuth();
  const [open, setOpen] = useState<boolean>(false);
  const [photo, setPhoto] = useState<string | undefined>(undefined);
  const [isErreur, setIsErreur] = useState<boolean>(false);
  const { data: telechargement } = useApi().useGetItem({
    path: "/telechargements/{id}",
    url: props.telechargementId,
    enabled: !!props.telechargementId,
  });

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!props.telechargementId) setPhoto(undefined);
  }, [props.telechargementId]);

  useEffect(() => {
    // if (!auth.token) return;
    if (!telechargement?.urlContenu) return;
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setPhoto(undefined);

    let fetchOptions: RequestInit = {
      method: "GET",
      credentials: "include",
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

    let objectUrl: string | undefined;

    fetch(`${env.REACT_APP_API}${telechargement?.urlContenu}`, fetchOptions)
      .then((response) => response.blob())
      .then((blob) => {
        objectUrl = window.URL.createObjectURL(blob);
        setPhoto(objectUrl);
      })
      .catch(() => {
        setPhoto(undefined);
        setIsErreur(true);
      });

    return () => {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [telechargement, auth.impersonate]);

  if (!photo) return <Spinner />;

  return (
    <Tooltip title="Prévisualiser">
      {!isErreur && (
        <Button
          type={props.type}
          aria-label="Prévisualiser l'image"
          aria-hidden={true}
          disabled={false}
          icon={<EyeOutlined aria-hidden className="pr-2 pl-2" />}
          onClick={() => setOpen(true)}
          className={props.className}
          style={{ width: "auto" }}
        />
      )}
      <Image
        preview={{
          open,
          src: photo,
          onOpenChange: setOpen,
        }}
        alt="Image de la pièce justificative"
        src={photo}
        style={{ display: "none" }}
        className="border-radius"
        fallback="data:image/gif;base64,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"
      />
    </Tooltip>
  );
}
