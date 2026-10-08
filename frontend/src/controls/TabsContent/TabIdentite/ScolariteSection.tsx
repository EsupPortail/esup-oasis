/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useMemo, useState } from "react";
import { Button, Descriptions, Empty, Flex, Skeleton, Typography } from "antd";
import { DownOutlined, MinusOutlined, UpOutlined } from "@ant-design/icons";
import { IInscription, IUtilisateur } from "@api";
import { isEnCoursSurPeriode } from "@utils/dates";
import { ScolariteListItem } from "@controls/TabsContent/TabScolarite";

interface ScolariteSectionProps {
  utilisateur: IUtilisateur;
  isFetching: boolean;
}

const ID_INSCRIPTIONS_PRECEDENTES = "inscriptions-precedentes";

const parDebutDecroissant = (i1: IInscription, i2: IInscription): number =>
  (i2.debut || "").localeCompare(i1.debut || "");

export const ScolariteSection: React.FC<ScolariteSectionProps> = ({ utilisateur, isFetching }) => {
  const [afficherPrecedentes, setAfficherPrecedentes] = useState(false);

  const { inscriptionsEnCours, inscriptionsPrecedentes } = useMemo(() => {
    const triees = [...(utilisateur.inscriptions || [])].sort(parDebutDecroissant);
    return {
      inscriptionsEnCours: triees.filter((i) => isEnCoursSurPeriode(i.debut, i.fin)),
      inscriptionsPrecedentes: triees.filter((i) => !isEnCoursSurPeriode(i.debut, i.fin)),
    };
  }, [utilisateur.inscriptions]);

  return (
    <>
      <h2>Scolarité</h2>
      {isFetching ? (
        <Skeleton active paragraph />
      ) : (
        <Descriptions bordered column={1} style={{ overflowX: "auto" }}>
          <Descriptions.Item label="Numéro étudiant">
            <Typography.Text copyable={!!utilisateur.numeroEtudiant}>
              {utilisateur.numeroEtudiant || <MinusOutlined />}
            </Typography.Text>
          </Descriptions.Item>
          <Descriptions.Item label="Régime d'inscription">
            {utilisateur?.statutEtudiant}
          </Descriptions.Item>
          <Descriptions.Item label="Inscriptions" styles={{ label: { width: 200 } }}>
            <h3 className="sr-only">Inscriptions</h3>
            <Flex vertical style={{ width: "100%", overflowY: "auto" }} wrap="wrap">
              {inscriptionsEnCours.length === 0 ? (
                <Empty
                  image={Empty.PRESENTED_IMAGE_SIMPLE}
                  description="Aucune inscription en cours"
                />
              ) : (
                inscriptionsEnCours.map((i) => (
                  <ScolariteListItem key={i["@id"]} inscription={i} titleClassName="" />
                ))
              )}

              {inscriptionsPrecedentes.length > 0 && (
                <>
                  <Button
                    className="mt-2 w-100"
                    style={{ alignSelf: "flex-start" }}
                    icon={afficherPrecedentes ? <UpOutlined /> : <DownOutlined />}
                    onClick={() => setAfficherPrecedentes(!afficherPrecedentes)}
                    aria-expanded={afficherPrecedentes}
                    aria-controls={ID_INSCRIPTIONS_PRECEDENTES}
                  >
                    Inscriptions précédentes ({inscriptionsPrecedentes.length})
                  </Button>
                  <div id={ID_INSCRIPTIONS_PRECEDENTES}>
                    {afficherPrecedentes &&
                      inscriptionsPrecedentes.map((i) => (
                        <ScolariteListItem key={i["@id"]} inscription={i} titleClassName="" />
                      ))}
                  </div>
                </>
              )}
            </Flex>
          </Descriptions.Item>
        </Descriptions>
      )}
    </>
  );
};
