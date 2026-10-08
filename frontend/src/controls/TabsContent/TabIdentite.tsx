/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useEffect, useState } from "react";
import { useApi } from "@context/api/ApiProvider";
import { App, Col, Form, Row, Skeleton } from "antd";
import { useAuth } from "@/auth/AuthProvider";
import { QK_UTILISATEURS } from "@api";
import { IdentiteSection } from "@controls/TabsContent/TabIdentite/IdentiteSection";
import { ScolariteSection } from "@controls/TabsContent/TabIdentite/ScolariteSection";
import { SuiviSection } from "@controls/TabsContent/TabIdentite/SuiviSection";
import { CommentaireDemandeSection } from "@controls/TabsContent/TabIdentite/CommentaireDemandeSection";
import { InfosComplementairesSection } from "@controls/TabsContent/TabIdentite/InfosComplementairesSection";

export function TabIdentite(props: {
  utilisateurId: string;
  demandeId?: string;
}): React.ReactElement {
  const { message } = App.useApp();
  const [form] = Form.useForm();
  const user = useAuth().user;
  const [commentaireState, setCommentaireState] = useState<{
    demandeId: string | undefined;
    value: string;
  }>({ demandeId: undefined, value: "" });

  const { data: utilisateur, isFetching } = useApi().useGetItem({
    path: "/utilisateurs/{uid}",
    url: props.utilisateurId,
    enabled: !!props.utilisateurId,
  });

  const { data: demande } = useApi().useGetItem({
    path: "/demandes/{id}",
    url: props.demandeId,
    enabled: !!props.demandeId,
  });

  const mutateDemande = useApi().usePatch({
    path: "/demandes/{id}",
    invalidationQueryKeys: [props.demandeId as string],
    onSuccess: () => {
      message.success("Commentaire enregistré").then();
    },
  });

  const mutateUtilisateur = useApi().usePatch({
    path: "/utilisateurs/{uid}",
    invalidationQueryKeys: [QK_UTILISATEURS, utilisateur?.["@id"] || QK_UTILISATEURS],
    onSuccess: () => message.success("Utilisateur modifié").then(),
  });

  useEffect(() => {
    form.setFieldsValue(utilisateur);
  }, [form, utilisateur]);

  // Initialise le commentaire quand la demande charge pour un nouveau demandeId (sans écraser la saisie)
  if (demande && props.demandeId !== commentaireState.demandeId) {
    setCommentaireState({ demandeId: props.demandeId, value: demande.commentaire || "" });
  }

  const commentaire = commentaireState.value;
  const setCommentaire = (v: string) => setCommentaireState((prev) => ({ ...prev, value: v }));

  if (!utilisateur) {
    return (
      <Form form={form}>
        <Skeleton active paragraph />
      </Form>
    );
  }

  return (
    <div>
      <IdentiteSection
        utilisateur={utilisateur}
        isFetching={isFetching}
        mutateUtilisateur={mutateUtilisateur.mutate}
      />

      <Row gutter={16}>
        <Col xs={24} xl={12}>
          <ScolariteSection utilisateur={utilisateur} isFetching={isFetching} />
          <InfosComplementairesSection utilisateur={utilisateur} isFetching={isFetching} />
        </Col>
        <Col xs={24} xl={12}>
          {user?.isGestionnaire && (
            <SuiviSection
              utilisateur={utilisateur}
              isFetching={isFetching}
              mutateUtilisateur={mutateUtilisateur.mutate}
              form={form}
            />
          )}
        </Col>
      </Row>

      {props.demandeId && user?.isGestionnaire && (
        <CommentaireDemandeSection
          demandeId={props.demandeId}
          isFetching={isFetching}
          commentaire={commentaire}
          setCommentaire={setCommentaire}
          mutateDemande={mutateDemande.mutate}
        />
      )}
    </div>
  );
}
