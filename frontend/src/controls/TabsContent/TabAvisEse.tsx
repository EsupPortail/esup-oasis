/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useApi } from "@context/api/ApiProvider";
import { Button, Flex, Tooltip, Typography } from "antd";
import React from "react";
import { PlusOutlined } from "@ant-design/icons";
import { IAvisEse } from "@api";
import { ModalAvisEse } from "@controls/Modals/ModalAvisEse";
import { AvisEseList } from "@controls/List/AvisEseList";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { env } from "@/env";
import { useVerrouSignature } from "@controls/TabsContent/useVerrouSignature";

export function TabAvisEse(props: { utilisateurId: string }) {
  const screens = useBreakpoint();
  const [editedItem, setEditedItem] = React.useState<IAvisEse>();
  const verrou = useVerrouSignature(props.utilisateurId);
  const { data: avis } = useApi().useGetFullCollection({
    path: "/utilisateurs/{uid}/avis_ese",
    parameters: {
      uid: props.utilisateurId,
    },
    query: {
      "order[debut]": "desc",
    },
  });

  return (
    <>
      <Flex justify="space-between" align="center" className="mt-1 mb-2" wrap>
        <Typography.Title level={3} className="mt-0 mb-0">
          Avis {env.REACT_APP_ESPACE_SANTE || "santé"}
        </Typography.Title>

        <div className={`text-right ${!screens.lg ? "mt-2" : ""}`}>
          {editedItem && (
            <ModalAvisEse
              open
              setOpen={(open) => {
                if (!open) setEditedItem(undefined);
              }}
              avisId={editedItem?.["@id"]}
              utilisateurId={props.utilisateurId}
              setEditedItem={setEditedItem}
            />
          )}
          <Tooltip title={verrou}>
            <Button
              type="primary"
              icon={<PlusOutlined />}
              disabled={!!verrou}
              onClick={() => setEditedItem({} as IAvisEse)}
            >
              Ajouter un avis {env.REACT_APP_ESPACE_SANTE_ABV || "santé"}
            </Button>
          </Tooltip>
        </div>
      </Flex>

      <AvisEseList
        utilisateurId={props.utilisateurId}
        avis={avis?.items || []}
        setEditedItem={setEditedItem}
        verrou={verrou}
      />
    </>
  );
}
