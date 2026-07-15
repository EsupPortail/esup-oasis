/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { List, Space, Timeline } from "antd";

import { useQuestionnaire } from "@context/demande/QuestionnaireProvider";
import { IModificationEtatDemande } from "@api";
import { useApi } from "@context/api/ApiProvider";
import dayjs from "dayjs";
import { EtatDemandeAvatar } from "@controls/Avatars/EtatDemandeAvatar";
import { EtudiantItem } from "@controls/Items/EtudiantItem";
import { ProfilItem } from "@controls/Items/ProfilItem";
import { ArrowRightOutlined } from "@ant-design/icons";
import Spinner from "@controls/Spinner/Spinner";

export function DossierDemandeTabHistorique(): React.ReactElement {
  const { questionnaire } = useQuestionnaire();
  const { data: histos, isFetching: histosIsFetching } = useApi().useGetCollection({
    path: "/demandes/{demandeId}/modifications",
    parameters: { demandeId: questionnaire?.["@id"] as string },
    query: {
      "order[dateModification]": "asc",
      "order[id]": "asc",
    },
  });

  if (histosIsFetching) return <Spinner />;

  return (
    <div>
      <h2>Historique des actions</h2>

      <Timeline
        mode="start"
        items={histos?.items.map((histo: IModificationEtatDemande) => ({
          title: histo.dateModification
            ? dayjs(histo.dateModification).format("DD/MM/YYYY à HH:mm")
            : "",
          content: (
            <List bordered size="small" className="mb-2">
              <ul>
                <List.Item>
                  <Space wrap>
                    <span>Modification de l'état</span>
                    <span>
                      <EtatDemandeAvatar etatDemandeId={histo.etatPrecedent} />
                    </span>
                    <ArrowRightOutlined />
                    <span>
                      <EtatDemandeAvatar etatDemandeId={histo.etat} className="ml-1" />
                    </span>
                  </Space>
                </List.Item>
                <List.Item>
                  <Space wrap>
                    <span>par</span>
                    <span>
                      <EtudiantItem
                        utilisateurId={histo.utilisateurModification}
                        showAvatar={false}
                      />
                    </span>
                  </Space>
                </List.Item>
                {histo.profil && (
                  <List.Item>
                    <Space wrap>
                      <span>avec le profil</span>
                      <span>
                        <ProfilItem profil={histo.profil} className="mt-0" />
                      </span>
                    </Space>
                  </List.Item>
                )}
                {histo.commentaire && (
                  <List.Item>
                    <Space wrap>
                      <span>Commentaire</span>
                      <span className="light">{histo.commentaire}</span>
                    </Space>
                  </List.Item>
                )}
              </ul>
            </List>
          ),
        }))}
      />
    </div>
  );
}
