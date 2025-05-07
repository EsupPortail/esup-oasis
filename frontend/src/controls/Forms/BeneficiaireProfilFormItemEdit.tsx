/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { App, Avatar, Button, Card, Checkbox, DatePicker, Select, Space } from "antd";
import { useAuth } from "../../auth/AuthProvider";
import React, { useState } from "react";
import dayjs, { Dayjs } from "dayjs";
import { useApi } from "../../context/api/ApiProvider";
import { NB_MAX_ITEMS_PER_PAGE } from "../../constants";
import { createDateAsUTC } from "../../utils/dates";
import Spinner from "../Spinner/Spinner";
import { CaretRightOutlined, EditOutlined, PlusOutlined, SaveOutlined } from "@ant-design/icons";
import { IBeneficiaireProfil, IUtilisateur } from "../../api/ApiTypeHelpers";
import { env } from "../../env";

interface IBeneficiaireProfilFormItemEditProps {
   utilisateur: IUtilisateur;
   onEdited?: () => void;
   profilBeneficiaire?: IBeneficiaireProfil;
   onCancel?: () => void;
}

export function BeneficiaireProfilFormItemEdit({
   utilisateur,
   profilBeneficiaire,
   onEdited,
   onCancel,
}: IBeneficiaireProfilFormItemEditProps) {
   const { message } = App.useApp();
   const user = useAuth().user;
   const [profil, setProfil] = useState<string | null | undefined>(profilBeneficiaire?.profil);
   const [typologies, setTypologies] = useState<string[]>(profilBeneficiaire?.typologies || []);
   const [submitting, setSubmitting] = React.useState<boolean>(false);
   const [dateDebut, setDateDebut] = useState<Dayjs | null>(
      profilBeneficiaire?.debut ? dayjs(profilBeneficiaire.debut) : null,
   );
   const [dateFin, setDateFin] = useState<Dayjs | null>(
      profilBeneficiaire?.fin ? dayjs(profilBeneficiaire.fin) : null,
   );
   const [gestionnaire, setGestionnaire] = useState<string | undefined>(
      profilBeneficiaire?.gestionnaire || user?.["@id"],
   );
   const [avecAccompagnement, setAvecAccompagnement] = useState<boolean>(
      profilBeneficiaire?.avecAccompagnement !== false,
   );

   const mutationPatch = useApi().usePatch({
      path: "/utilisateurs/{uid}/profils/{id}",
      invalidationQueryKeys: [
         "/utilisateurs",
         "/intervenants",
         "/beneficiaires",
         "/statistiques_evenements",
      ],
      onSuccess: () => {
         setSubmitting(false);
         if (onEdited) onEdited();
      },
   });
   const mutationPost = useApi().usePost({
      path: "/utilisateurs/{uid}/profils",
      invalidationQueryKeys: [
         "/utilisateurs",
         "/intervenants",
         "/beneficiaires",
         "/statistiques_evenements",
      ],
      onSuccess: () => {
         setSubmitting(false);
         if (onEdited) onEdited();
      },
      url: `${utilisateur["@id"]}/profils` as string,
   });

   const { data: profils, isFetching: isFetchingProfils } = useApi().useGetCollectionPaginated({
      path: "/profils",
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
   });
   const { data: typologiesData, isFetching: isFetchingTypologiesData } =
      useApi().useGetCollectionPaginated({
         path: "/typologies",
         page: 1,
         itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
      });

   const { data: gestionnaires, isFetching } = useApi().useGetCollectionPaginated({
      path: "/roles/{roleId}/utilisateurs",
      parameters: { roleId: "/roles/ROLE_GESTIONNAIRE" },
      page: 1,
      itemsPerPage: NB_MAX_ITEMS_PER_PAGE,
   });

   function createOrUpdateProfil() {
      // Validate data
      if (!profil || !dateDebut || !gestionnaire) {
         message.error("Veuillez renseigner tous les champs obligatoires").then();
         return;
      }

      setSubmitting(true);
      const data = {
         profil: profil as string,
         debut: createDateAsUTC(dateDebut.toDate()).toISOString(),
         fin: dateFin ? createDateAsUTC(dateFin.toDate()).toISOString() : null,
         gestionnaire: gestionnaire as string,
         typologies: typologies,
         avecAccompagnement,
      };
      if (profilBeneficiaire) {
         // update
         mutationPatch?.mutate({
            "@id": profilBeneficiaire["@id"] as string,
            data,
         });
      } else {
         // create
         mutationPost?.mutate({
            data,
         });
      }
   }

   if (isFetchingProfils || isFetching || isFetchingTypologiesData) return <Spinner />;

   return (
      <Card>
         <Card.Meta
            avatar={
               <Avatar
                  className="text-text"
                  icon={profilBeneficiaire ? <EditOutlined /> : <PlusOutlined />}
               />
            }
            title={profilBeneficiaire ? "Editer un profil" : "Ajouter un profil"}
            description={
               <>
                  <Space direction="vertical" className="w-100">
                     <Select
                        placeholder="Sélectionnez un profil"
                        options={profils?.items.map((p) => ({ value: p["@id"], label: p.libelle }))}
                        value={profil}
                        onChange={(v) => setProfil(v)}
                        aria-required
                        status={profil ? "" : "error"}
                     />

                     {profils?.items.find((p) => p["@id"] === profil)?.avecTypologie && (
                        <Select
                           placeholder="Sélectionnez une typologie de handicap"
                           options={typologiesData?.items.map((t) => ({
                              value: t["@id"],
                              label: t.libelle,
                           }))}
                           value={typologies}
                           onChange={(v) => setTypologies(v)}
                           mode="tags"
                           aria-required
                           status={typologies.length > 0 ? "" : "error"}
                        />
                     )}

                     <Space className="w-100">
                        <DatePicker
                           placeholder="Début"
                           value={dateDebut}
                           onChange={(v) => setDateDebut(v)}
                           aria-required
                           status={dateDebut ? "" : "error"}
                           changeOnBlur
                        />
                        <CaretRightOutlined />
                        <DatePicker
                           placeholder="Fin"
                           value={dateFin}
                           onChange={(v) => setDateFin(v)}
                           changeOnBlur
                        />
                     </Space>
                     <Space direction="vertical" className="w-100 mt-2">
                        <span className="semi-bold" aria-label="Chargé d'accompagnement associé">
                           Chargé•e d'accompagnement associée
                        </span>
                        <Select
                           placeholder="Sélectionnez un gestionnaire"
                           options={gestionnaires?.items
                              .sort(
                                 (g1, g2) =>
                                    g1.nom
                                       ?.toLocaleUpperCase()
                                       .localeCompare(g2.nom?.toLocaleUpperCase() || "") || 0,
                              )
                              .map((g) => ({
                                 value: g["@id"],
                                 label: `${g.nom?.toLocaleUpperCase()} ${g.prenom}`,
                              }))}
                           value={gestionnaire}
                           onChange={(v) => setGestionnaire(v)}
                           aria-required
                           status={gestionnaire ? "" : "error"}
                        />
                     </Space>

                     <Space direction="vertical" className="w-100 mt-2">
                        <Checkbox
                           checked={avecAccompagnement}
                           onChange={(value) => {
                              setAvecAccompagnement(value.target.checked);
                           }}
                        >
                           <span>
                              Accompagnement par le service {env.REACT_APP_SERVICE}
                           </span>
                        </Checkbox>
                     </Space>

                     <Space className="mt-3">
                        <Button onClick={onCancel}>Annuler</Button>
                        <Button
                           loading={submitting}
                           type="primary"
                           icon={<SaveOutlined />}
                           onClick={() => createOrUpdateProfil()}
                        >
                           Enregistrer le profil
                        </Button>
                     </Space>
                  </Space>
               </>
            }
         />
      </Card>
   );
}
