/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useState } from "react";
import { Avatar, Button, Card, Space, Tooltip } from "antd";
import { useApi } from "../../context/api/ApiProvider";
import { IBeneficiaireProfil, IUtilisateur } from "../../api/ApiTypeHelpers";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import Spinner from "../Spinner/Spinner";
import { getLibellePeriode, isEnCoursSurPeriode } from "../../utils/dates";
import { EditOutlined, InfoCircleOutlined } from "@ant-design/icons";
import GestionnaireItem from "../Items/GestionnaireItem";
import { PREFETCH_PROFILS } from "../../api/ApiPrefetchHelpers";
import { AccompagnementAvatar } from "../Avatars/AccompagnementAvatar";
import { BeneficiaireProfilFormItemEdit } from "./BeneficiaireProfilFormItemEdit";

interface IBeneficiaireProfilFormItemProps {
   value?: string;
   onChange?: (v: string | undefined) => void;
   style?: React.CSSProperties;
   className?: string;
   cardClassName?: string;
   cardSize?: "small" | "default";
   placeholder?: string;
   extra?: React.ReactNode;
   utilisateur: IUtilisateur;
   onCancel?: () => void;
   selected?: boolean;
   onSelect?: (profil: string) => void;
   editable?: boolean;
}

function BeneficiaireProfilCardItem({
                                       value,
                                       cardClassName,
                                       extra,
                                       utilisateur,
                                       onCancel,
                                       selected,
                                       onSelect,
                                       editable = true,
                                       cardSize = "default",
                                    }: IBeneficiaireProfilFormItemProps) {
   const [profilEdited, setProfilEdited] = useState<IBeneficiaireProfil>();
   const { data: profils, isFetching: isFetchingProfils } =
      useApi().useGetCollection(PREFETCH_PROFILS);
   const { data, isFetching } = useApi().useGetItem({
      path: "/utilisateurs/{uid}/profils/{id}",
      url: value as string,
      enabled: !!value,
   });
   const { data: typologiesData, isFetching: isFetchingTypologiesData } =
      useApi().useGetCollectionPaginated({
         path: "/typologies",
         page: 1,
         itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
      });

   if (isFetching || isFetchingProfils || isFetchingTypologiesData) return <Spinner />;

   if (value === undefined || profilEdited)
      return (
         <BeneficiaireProfilFormItemEdit
            utilisateur={utilisateur}
            profilBeneficiaire={profilEdited}
            onEdited={() => setProfilEdited(undefined)}
            onCancel={() => {
               setProfilEdited(undefined);
               if (onCancel && !value) onCancel();
            }}
         />
      );

   const profil = profils?.items.find((p) => p["@id"] === data?.profil);

   return (
      <Card
         size={cardSize}
         className={`${cardClassName}${
            selected ? " bg-beneficiaire-light" : ""
         }${onSelect ? " pointer" : ""}`}
         onClick={() => {
            if (onSelect) onSelect(value);
         }}
      >
         <Card.Meta
            avatar={
               isEnCoursSurPeriode(data?.debut, data?.fin) ? (
                  <Tooltip title="En cours" placement="left">
                     <Avatar className="bg-success" size="small" />
                  </Tooltip>
               ) : (
                  <Tooltip title="Terminé" placement="left">
                     <Avatar size="small" />
                  </Tooltip>
               )
            }
            title={
               <div style={{ whiteSpace: "normal", lineHeight: 1.25 }}>
                  <Space className="float-right" size="small">
                     {extra}
                     {editable && (
                        <Button
                           type="text"
                           icon={<EditOutlined />}
                           onClick={() => setProfilEdited(data)}
                        />
                     )}
                  </Space>
                  {profil?.libelle}
                  {profil?.avecTypologie && (
                     <Tooltip
                        title={data?.typologies
                           ?.map((t) => {
                              const typologie = typologiesData?.items.find(
                                 (typo) => typo["@id"] === t,
                              );
                              return typologie?.libelle;
                           })
                           .join(", ")}
                     >
                        <InfoCircleOutlined className="ml-1 pointer" />
                     </Tooltip>
                  )}
               </div>
            }
            description={
               <Space direction="vertical">
                  {getLibellePeriode(data?.debut, data?.fin)}
                  <Space wrap className="text-text">
                     <span>Suivi par :</span>
                     <span>
                        <GestionnaireItem gestionnaireId={data?.gestionnaire} />
                     </span>
                  </Space>
                  <Space wrap className="text-text">
                     <span>
                        <AccompagnementAvatar avecAccompagnement={data?.avecAccompagnement} libelle />
                     </span>
                  </Space>
               </Space>
            }
         />
      </Card>
   );
}

export default BeneficiaireProfilCardItem;
