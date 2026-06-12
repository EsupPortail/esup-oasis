/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useRef, useState } from "react";
import { App, UploadFile, UploadProps } from "antd";
import { RcFile } from "antd/es/upload";
import { envoyerFichierFetch } from "@utils/upload";
import { ACCEPT_FICHIERS, validerFichier } from "@utils/fichierValidation";
import { useAuth } from "@/auth/AuthProvider";
import { env } from "@/env";
import { QuestionnaireQuestion, useQuestionnaire } from "@context/demande/QuestionnaireProvider";
import { UploadOutlined } from "@ant-design/icons";

export function useQuestionFileUpload(question: QuestionnaireQuestion) {
  const { mode, form, questUtils, setSubmitting } = useQuestionnaire();
  const auth = useAuth();
  const { notification } = App.useApp();
  const [fileList, setFileList] = useState<UploadFile[]>(
    question.reponse?.piecesJustificatives?.map((pj) => ({
      uid: pj,
      name: "...",
      status: "done",
      url: pj,
    })) || [],
  );
  const [uploadingCount, setUploadingCount] = useState<number>(0);
  // Authoritative list of saved PJ IDs — updated synchronously to avoid race conditions
  const savedPjIdsRef = useRef<string[]>(question.reponse?.piecesJustificatives ?? []);
  // Queue to serialize PUT calls — prevents concurrent requests sending the same PJ IDs
  // and triggering a duplicate key constraint on the backend.
  const responseQueueRef = useRef<Promise<void>>(Promise.resolve());

  function envoyerReponse(
    reponse: string[],
    onSuccess: ((body: string) => void) | undefined,
    setNewFileList: () => void,
  ) {
    responseQueueRef.current = responseQueueRef.current.then(
      () =>
        new Promise<void>((resolve) => {
          questUtils?.envoyerReponse(question["@id"] as string, "file", reponse, () => {
            onSuccess?.("ok");
            setNewFileList();
            resolve();
          });
        }),
    );
  }

  function removeFile(pieceJustificativeId: string) {
    savedPjIdsRef.current = savedPjIdsRef.current.filter((id) => id !== pieceJustificativeId);
    envoyerReponse(
      savedPjIdsRef.current,
      () => {
        setFileList((prev) => prev.filter((f) => f.uid !== pieceJustificativeId));
        form?.resetFields([question["@id"] as string]);
      },
      () => setFileList((prev) => prev.filter((f) => f.uid !== pieceJustificativeId)),
    );
  }

  const uploadProps: UploadProps = {
    name: "file",
    fileList: fileList,
    multiple: question.choixMultiple ?? false,
    disabled: mode === "preview",
    accept: ACCEPT_FICHIERS,
    customRequest: async (options) => {
      const { onSuccess, onError, file } = options;

      if (!file) {
        return;
      }

      if (typeof file === "object") {
        const erreur = validerFichier(file as RcFile);
        if (erreur) {
          notification.error({
            title: `Fichier "${question.libelle}" refusé : ${erreur}`,
            icon: <UploadOutlined className="text-danger" aria-hidden />,
          });
          return;
        }
      }

      setUploadingCount((c) => c + 1);
      setSubmitting(true);
      await envoyerFichierFetch(
        env.REACT_APP_API as string,
        auth,
        file,
        (pj) => {
          const pjId = pj["@id"] as string;
          // Accumulate synchronously — avoids the race condition where concurrent uploads
          // each read stale "done" IDs and overwrite each other's response.
          if (question.choixMultiple) {
            savedPjIdsRef.current = [...savedPjIdsRef.current, pjId];
          } else {
            savedPjIdsRef.current = [pjId];
          }
          envoyerReponse([...savedPjIdsRef.current], onSuccess, () =>
            setFileList((prev) => {
              setUploadingCount((c) => c - 1);
              setSubmitting(false);
              notification.success({
                title: `Le fichier a été chargé.`,
                icon: <UploadOutlined className="text-success" aria-hidden />,
              });
              return prev.map((f) => {
                if (f.uid === (options.file as RcFile).uid) {
                  return { uid: pjId, name: f.name, status: "done", url: pjId };
                }
                return f;
              });
            }),
          );
        },
        (error) => {
          setUploadingCount((c) => c - 1);
          setSubmitting(false);
          setFileList((prev) => {
            notification.error({
              title: `Erreur lors du chargement du fichier.`,
              icon: <UploadOutlined className="text-danger" aria-hidden />,
            });
            return prev.map((f) => {
              if (f.uid === (options.file as RcFile).uid) {
                return { uid: f.uid, name: f.name, status: "error", percent: undefined };
              }
              return f;
            });
          });
          onError?.(error);
        },
        (percent) => {
          setFileList((prev) =>
            prev.map((f) =>
              f.uid === (options.file as RcFile).uid
                ? { ...f, percent, status: "uploading" as const }
                : f,
            ),
          );
        },
      );
    },

    onChange(info) {
      if (info.file.status === "uploading") {
        setFileList((prev) => {
          if (prev.some((f) => f.uid === info.file.uid)) return prev;
          return [...prev, { ...info.file }];
        });
      }
    },
  };

  return {
    fileList,
    setFileList,
    uploading: uploadingCount > 0,
    removeFile,
    uploadProps,
  };
}
