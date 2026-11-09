/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { App, Avatar, Button, Card, Checkbox, DatePicker, Select, Space } from "antd";
import { useAuth } from "@/auth/AuthProvider";
import React, { useState } from "react";
import dayjs, { Dayjs } from "dayjs";
import { useApi } from "@context/api/ApiProvider";
import { createDateAsUTC } from "@utils/dates";
import Spinner from "@controls/Spinner/Spinner";
import { CaretRightOutlined, EditOutlined, PlusOutlined, SaveOutlined } from "@ant-design/icons";
import {
  IBeneficiaireProfil,
  IUtilisateur,
  QK_BENEFICIAIRES,
  QK_INTERVENANTS,
  QK_STATISTIQUES_EVENEMENTS,
  QK_UTILISATEURS,
} from "@api";
import { service } from "@lib";

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
      QK_UTILISATEURS,
      QK_INTERVENANTS,
      QK_BENEFICIAIRES,
      QK_STATISTIQUES_EVENEMENTS,
    ],
    onSuccess: () => {
      if (onEdited) onEdited();
    },
  });
  const mutationPost = useApi().usePost({
    path: "/utilisateurs/{uid}/profils",
    invalidationQueryKeys: [
      QK_UTILISATEURS,
      QK_INTERVENANTS,
      QK_BENEFICIAIRES,
      QK_STATISTIQUES_EVENEMENTS,
    ],
    onSuccess: () => {
      if (onEdited) onEdited();
    },
    url: `${utilisateur["@id"]}/profils` as string,
  });

  const { data: profils, isLoading: isLoadingProfils } = useApi().useGetFullCollection({
    path: "/profils",
  });
  const { data: typologiesData, isLoading: isLoadingTypologiesData } =
    useApi().useGetFullCollection({
      path: "/typologies",
    });

  const { data: gestionnaires, isLoading: isLoadingGestionnaires } = useApi().useGetFullCollection({
    path: "/roles/{roleId}/utilisateurs",
    parameters: { roleId: "/roles/ROLE_GESTIONNAIRE" },
  });

  const profilAvecTypologie = !!profils?.items.find((p) => p["@id"] === profil)?.avecTypologie;

  function createOrUpdateProfil() {
    // Validate data
    if (!profil || !dateDebut || !gestionnaire) {
      message.error("Veuillez renseigner tous les champs obligatoires").then();
      return;
    }
    if (profilAvecTypologie && typologies.length === 0) {
      message.error("Veuillez sélectionner au moins une typologie de handicap").then();
      return;
    }

    const data = {
      profil: profil as string,
      debut: createDateAsUTC(dateDebut.toDate()).toISOString(),
      fin: dateFin ? createDateAsUTC(dateFin.toDate()).toISOString() : null,
      gestionnaire: gestionnaire as string,
      // Le backend refuse des typologies sur un profil qui n'en a pas
      typologies: profilAvecTypologie ? typologies : [],
      avecAccompagnement,
    };
    if (profilBeneficiaire) {
      // update
      mutationPatch.mutate({
        "@id": profilBeneficiaire["@id"] as string,
        data,
      });
    } else {
      // create
      mutationPost.mutate({
        data,
      });
    }
  }

  if (isLoadingProfils || isLoadingGestionnaires || isLoadingTypologiesData) return <Spinner />;

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
            <Space orientation="vertical" className="w-100">
              <Select
                placeholder="Sélectionnez un profil"
                options={profils?.items
                  .filter((p) => p.actif || p["@id"] === profilBeneficiaire?.profil)
                  .map((p) => ({
                    value: p["@id"],
                    label: p.libelle,
                  }))}
                value={profil}
                onChange={(v) => setProfil(v)}
                aria-required
                status={profil ? "" : "error"}
              />

              {profilAvecTypologie && (
                <Select
                  placeholder="Sélectionnez une typologie de handicap"
                  options={typologiesData?.items.map((t) => ({
                    value: t["@id"],
                    label: t.libelle,
                  }))}
                  value={typologies}
                  onChange={(v) => setTypologies(v)}
                  mode="multiple"
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
                />
                <CaretRightOutlined />
                <DatePicker placeholder="Fin" value={dateFin} onChange={(v) => setDateFin(v)} />
              </Space>
              <Space orientation="vertical" className="w-100 mt-2">
                <span className="semi-bold" aria-label="Chargé d'accompagnement associé">
                  Chargé•e d'accompagnement associée
                </span>
                <Select
                  placeholder="Sélectionnez un gestionnaire"
                  options={[...(gestionnaires?.items || [])]
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

              <Space orientation="vertical" className="w-100 mt-2">
                <Checkbox
                  checked={avecAccompagnement}
                  onChange={(value) => {
                    setAvecAccompagnement(value.target.checked);
                  }}
                >
                  <span>Accompagnement par {service.defini}</span>
                </Checkbox>
              </Space>

              <Space className="mt-3">
                <Button onClick={onCancel}>Annuler</Button>
                <Button
                  loading={mutationPost.isPending || mutationPatch.isPending}
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
