/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useRef } from "react";
import { Col, Descriptions, Input, Row, Skeleton } from "antd";

interface CommentaireDemandeSectionProps {
  demandeId: string;
  isFetching: boolean;
  commentaire?: string;
  setCommentaire: (value: string) => void;
  mutateDemande: (params: { data: { commentaire?: string }; "@id": string }) => void;
}

export const CommentaireDemandeSection: React.FC<CommentaireDemandeSectionProps> = ({
  demandeId,
  isFetching,
  commentaire,
  setCommentaire,
  mutateDemande,
}) => {
  const commentaireAuFocus = useRef<string | undefined>(undefined);

  return (
    <Row gutter={16}>
      <Col xs={24} xl={24}>
        <h2>Commentaire sur la demande</h2>
        {isFetching ? (
          <Skeleton active paragraph />
        ) : (
          <Descriptions bordered>
            <Descriptions.Item
              label={
                <>
                  Commentaire
                  <div className="legende">Visible CAS uniquement</div>
                </>
              }
            >
              <Input.TextArea
                autoSize={{ minRows: 3 }}
                value={commentaire}
                onChange={(e) => setCommentaire(e.target.value)}
                onFocus={() => {
                  commentaireAuFocus.current = commentaire;
                }}
                onBlur={() => {
                  if (commentaire === commentaireAuFocus.current) return;

                  mutateDemande({
                    data: {
                      commentaire: commentaire,
                    },
                    "@id": demandeId,
                  });
                }}
              />
            </Descriptions.Item>
          </Descriptions>
        )}
      </Col>
    </Row>
  );
};
