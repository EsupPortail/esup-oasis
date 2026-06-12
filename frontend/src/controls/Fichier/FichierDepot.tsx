/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { App, Upload, UploadProps } from "antd";
import { UploadOutlined } from "@ant-design/icons";
import { useAuth } from "@/auth/AuthProvider";
import { envoyerFichierFetch } from "@utils/upload";
import { ACCEPT_FICHIERS, validerFichier } from "@utils/fichierValidation";
import { ITelechargement } from "@api";
import { env } from "@/env";

export function FichierDepot(props: {
  onAdded: (fichier: ITelechargement) => void;
  onError?: (err: Error) => void;
}) {
  const { message } = App.useApp();
  const auth = useAuth();

  const uploadProps: UploadProps = {
    name: "file",
    multiple: false,
    accept: ACCEPT_FICHIERS,
    beforeUpload: (file) => {
      const erreur = validerFichier(file);
      if (erreur) {
        message.error(`${file.name} : ${erreur}`).then();
        return Upload.LIST_IGNORE;
      }
      return true;
    },
    customRequest: async ({ onSuccess, onError, file }) => {
      // envoi du fichier
      await envoyerFichierFetch(
        env.REACT_APP_API as string,
        auth,
        file,
        (fichier) => {
          props.onAdded(fichier);
          onSuccess?.(fichier);
        },
        (error) => {
          props.onError?.(error);
          onError?.(error);
        },
      );
    },

    onChange(info) {
      const { status } = info.file;
      if (status === "done") {
        message.success(`Le fichier ${info.file.name} a été chargé.`).then();
      } else if (status === "error") {
        message.error(`Erreur lors du chargement du fichier ${info.file.name}.`).then();
      }
    },
  };

  return (
    <Upload.Dragger {...uploadProps}>
      <div className="ant-upload-drag-icon mt-1 mb-2">
        <UploadOutlined className="text-primary" style={{ fontSize: 40 }} aria-hidden />
      </div>
      <div className="ant-upload-text">Cliquez ou déposez un fichier pour le charger</div>
    </Upload.Dragger>
  );
}
