/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { useState } from "react";
import { Button, Card, Flex, theme, Tooltip, Typography } from "antd";
import { useApi } from "@context/api/ApiProvider";
import { IBeneficiaireProfil, IUtilisateur, PREFETCH_PROFILS } from "@api";
import Spinner from "@controls/Spinner/Spinner";
import { getLibellePeriode, isEnCoursSurPeriode } from "@utils/dates";
import { CheckCircleFilled, EditOutlined, InfoCircleOutlined } from "@ant-design/icons";
import { GestionnaireItem } from "@controls/Items/GestionnaireItem";
import { AccompagnementAvatar } from "@controls/Avatars/AccompagnementAvatar";
import { BeneficiaireProfilFormItemEdit } from "@controls/Forms/BeneficiaireProfilFormItemEdit";

interface IBeneficiaireProfilFormItemProps {
  value?: string;
  cardClassName?: string;
  extra?: React.ReactNode;
  utilisateur: IUtilisateur;
  onCancel?: () => void;
  editable?: boolean;
}

function BeneficiaireProfilCardItem({
  value,
  cardClassName,
  extra,
  utilisateur,
  onCancel,
  editable = true,
}: IBeneficiaireProfilFormItemProps) {
  const { token } = theme.useToken();
  const [profilEdited, setProfilEdited] = useState<IBeneficiaireProfil>();
  const { data: profils, isLoading: isLoadingProfils } =
    useApi().useGetFullCollection(PREFETCH_PROFILS);
  const { data, isLoading } = useApi().useGetItem({
    path: "/utilisateurs/{uid}/profils/{id}",
    url: value as string,
    enabled: !!value,
  });
  const { data: typologiesData, isLoading: isLoadingTypologiesData } =
    useApi().useGetFullCollection({
      path: "/typologies",
    });

  // Le formulaire passe avant le chargement : un rafraîchissement en arrière-plan effacerait la saisie
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

  if (isLoading || isLoadingProfils || isLoadingTypologiesData) return <Spinner />;

  const profil = profils?.items.find((p) => p["@id"] === data?.profil);
  const enCours = isEnCoursSurPeriode(data?.debut, data?.fin);
  const paddingCarte = { paddingBlock: token.paddingSM, paddingInline: token.paddingMD };

  return (
    <Card
      size="small"
      className={cardClassName}
      style={{ overflow: "hidden" }}
      styles={{ body: { padding: 0 } }}
    >
      {/* Identité du profil : mise en avant quand il est en cours, comme l'inscription principale. */}
      <Flex
        vertical
        gap={token.marginXXS}
        style={{ background: enCours ? token.colorWarningBg : undefined, ...paddingCarte }}
      >
        <Flex justify="space-between" align="start" gap={token.marginXS}>
          <Typography.Text strong={enCours}>
            {profil?.libelle}
            {profil?.avecTypologie && (
              <Tooltip
                title={data?.typologies
                  ?.map((t) => {
                    const typologie = typologiesData?.items.find((typo) => typo["@id"] === t);
                    return typologie?.libelle;
                  })
                  .join(", ")}
              >
                <InfoCircleOutlined className="ml-1 pointer" />
              </Tooltip>
            )}
          </Typography.Text>
          <Flex flex="none" align="center" gap={token.marginXXS}>
            {extra}
            {editable && (
              <Button
                type="text"
                size="small"
                icon={<EditOutlined />}
                aria-label="Modifier le profil"
                onClick={() => setProfilEdited(data)}
              />
            )}
          </Flex>
        </Flex>
        <Flex wrap gap={token.marginXS} align="baseline">
          {enCours ? (
            <Typography.Text style={{ fontSize: token.fontSizeSM, fontWeight: 600 }}>
              <CheckCircleFilled aria-hidden style={{ color: token.colorSuccessText }} /> En cours
            </Typography.Text>
          ) : (
            <Typography.Text type="secondary" style={{ fontSize: token.fontSizeSM }}>
              Terminé
            </Typography.Text>
          )}
          <Typography.Text type="secondary" style={{ fontSize: token.fontSizeSM }}>
            {getLibellePeriode(data?.debut, data?.fin, "MMM")}
          </Typography.Text>
        </Flex>
      </Flex>
      {/* Suivi : séparé de l'identité par un filet, comme les lignes d'une carte d'inscriptions. */}
      <Flex
        vertical
        gap={token.marginXS}
        style={{ borderTop: `1px solid ${token.colorSplit}`, ...paddingCarte }}
      >
        <Flex wrap align="center" gap={token.marginXS}>
          <Typography.Text type="secondary">Suivi par :</Typography.Text>
          <GestionnaireItem gestionnaireId={data?.gestionnaire} />
        </Flex>
        <AccompagnementAvatar avecAccompagnement={data?.avecAccompagnement} libelle />
      </Flex>
    </Card>
  );
}

export default BeneficiaireProfilCardItem;
