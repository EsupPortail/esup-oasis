/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useEffect, useState } from "react";
import { Button, Layout, Space, Tooltip } from "antd";
import "@controls/Calendar/Sider/CalendarSider.scss";
import SmallCalendar from "@controls/Calendar/Sider/SmallCalendar/SmallCalendar";
import AffectationFilter, {
  AffectationFilterValues,
} from "@controls/Filters/Affectation/AffectationFilter";
import CampusFilter from "@controls/Filters/Campus/CampusFilter";
import TypeEvenementFilter from "@controls/Filters/TypeEvenement/TypeEvenementFilter";
import Icon, {
  CloseCircleFilled,
  LeftOutlined,
  RightOutlined,
  UserOutlined,
} from "@ant-design/icons";
import GestionnaireFilter from "@controls/Filters/Gestionnaire/GestionnaireFilter";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { useAuth } from "@/auth/AuthProvider";
import Asterisk from "@/assets/images/asterisk.svg?react";
import { useAffichageFiltres } from "@context/affichageFiltres/AffichageFiltresContext";
import { useApi } from "@context/api/ApiProvider";
import AnnulationFilter, {
  AnnulationFilterValues,
} from "@controls/Filters/Annulation/AnnulationFilter";
import { PREFETCH_TYPES_EVENEMENTS } from "@api";
import BeneficiaireIntervenantFilter from "@controls/Filters/BeneficiaireIntervenant/BeneficiaireIntervenantFilter";
import { FiltresFavorisEvenements } from "@controls/Calendar/Sider/FiltresFavorisEvenements";
import { service } from "@lib";

interface ICalendarSider {
  saisieEvtRenfort?: boolean;
}

/**
 * This function renders the sidebar component (filters) for the calendar page.
 *
 * @param {Object} params - The parameters for the sidebar component.
 * @param {boolean} [params.saisieEvtRenfort] - Indicates if renforcement event entry is enabled.
 *
 * @returns {ReactElement} The rendered sidebar component.
 */
export default function CalendarSider({ saisieEvtRenfort = false }: ICalendarSider): ReactElement {
  const auth = useAuth();
  const screens = useBreakpoint();
  const [collapsedSider, setCollapsedSider] = useState(false);
  const { data: dataTypesEvenements } = useApi().useGetFullCollection(PREFETCH_TYPES_EVENEMENTS);
  const { affichageFiltres: appAffichageFiltres, setFiltres } = useAffichageFiltres();

  // CSS pour formatage du label des filtres avec valeur
  const classNameByStatus = (
    value: string | number | Date | string[] | undefined,
    ifValue = "with-value",
    ifNoValue = "without-value",
  ) => (value === undefined || (Array.isArray(value) && value.length === 0) ? ifNoValue : ifValue);

  // Auto-collapse de la sidebar sur les écrans de petite taille
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setCollapsedSider(screens.lg !== undefined && !screens.lg);
  }, [screens]);

  return (
    <Layout.Sider width={300} className="calendar-sider" collapsed={collapsedSider}>
      <div className="collapse-trigger">
        {collapsedSider ? (
          <Button
            className="shadow-1"
            size="large"
            icon={<RightOutlined />}
            shape="circle"
            onClick={() => setCollapsedSider(false)}
            aria-label="Masquer la barre de navigation"
          />
        ) : (
          <Button
            className="shadow-1"
            size="large"
            icon={<LeftOutlined />}
            shape="circle"
            onClick={() => setCollapsedSider(true)}
            aria-label="Afficher la barre de navigation"
          />
        )}
      </div>
      <ul className="filters sider-content">
        <li className="li-calendar mb-2">
          <SmallCalendar />
        </li>
        {auth.user?.isPlanificateur && <FiltresFavorisEvenements />}
        {!saisieEvtRenfort && auth?.user?.isPlanificateur && (
          <li className="filter">
            <span className="label">Statut</span>
            <AffectationFilter
              value={
                (appAffichageFiltres.filtres["exists[intervenant]"] as AffectationFilterValues) ||
                AffectationFilterValues.Tous
              }
              setValue={(value) => setFiltres({ "exists[intervenant]": value })}
            />
            <AnnulationFilter
              value={
                (appAffichageFiltres.filtres["exists[dateAnnulation]"] as AnnulationFilterValues) ||
                AnnulationFilterValues.EnCours
              }
              setValue={(value) => setFiltres({ "exists[dateAnnulation]": value })}
            />
          </li>
        )}

        {!saisieEvtRenfort && auth?.user?.isPlanificateur && (
          <li className={`filter ${classNameByStatus(appAffichageFiltres.filtres["campus[]"])}`}>
            <Space orientation="vertical" size="small" className="w-100">
              <span className="label">Campus</span>
              <CampusFilter
                value={appAffichageFiltres.filtres["campus[]"]}
                onChange={(value) => setFiltres({ "campus[]": value })}
                mode="tags"
              />
            </Space>
          </li>
        )}

        {auth?.user?.isPlanificateur && (
          <li
            className={`filter ${classNameByStatus(
              appAffichageFiltres.filtres.intervenantBeneficiaire,
            )}`}
          >
            <Space orientation="vertical" size="small" className="w-100">
              <span className="label">Bénéficiaire / Intervenant</span>
              <BeneficiaireIntervenantFilter
                value={
                  appAffichageFiltres.filtres.intervenantBeneficiaireRole
                    ? `${appAffichageFiltres.filtres.intervenantBeneficiaireRole}§${appAffichageFiltres.filtres.intervenantBeneficiaire}`
                    : undefined
                }
                onChange={(utilisateurId, role) => {
                  setFiltres({
                    intervenantBeneficiaire: utilisateurId,
                    intervenantBeneficiaireRole: role,
                  });
                }}
                mode="multiple"
              />
            </Space>
          </li>
        )}

        {!saisieEvtRenfort && auth?.user?.isPlanificateur && (
          <li
            className={`filter ${classNameByStatus(
              appAffichageFiltres.filtres["utilisateurCreation[]"],
            )}`}
          >
            <Space orientation="vertical" size="small" className="w-100">
              <span className="label">
                Gestionnaire {service.sigle}
                <Tooltip title="Évènements que vous avez créés">
                  <Button
                    icon={<UserOutlined />}
                    aria-label="Filtrer sur mes évènements"
                    className="float-right fs-09 text-light"
                    size="small"
                    type="text"
                    onClick={() => {
                      setFiltres({
                        "utilisateurCreation[]": [auth.user?.["@id"] as string],
                      });
                    }}
                  />
                </Tooltip>
              </span>
              <GestionnaireFilter
                value={appAffichageFiltres.filtres["utilisateurCreation[]"]}
                setValue={(value) => setFiltres({ "utilisateurCreation[]": value })}
              />
            </Space>
          </li>
        )}

        <li className={`filter ${classNameByStatus(appAffichageFiltres.filtres.type)}`}>
          <Space orientation="vertical" size="small" className="w-100">
            <span className="label">
              Catégories d'évènements
              <Button
                aria-label={`Sélectionner toutes les catégories d'évènements`}
                className={`float-right fs-08 text-light contrast-no-border ${
                  appAffichageFiltres.filtres.type?.length ===
                  dataTypesEvenements?.items.filter((te) => te.actif).length
                    ? "d-none"
                    : ""
                }`}
                type="link"
                size="small"
                onClick={() =>
                  setFiltres({
                    type: (dataTypesEvenements?.items || [])
                      .filter((te) => te.actif)
                      .map((te) => te["@id"] as string),
                  })
                }
                icon={<Icon component={Asterisk} />}
              />
              <Button
                aria-label={`Désélectionner toutes les catégories d'évènements`}
                className={`float-right fs-08 text-light ${
                  appAffichageFiltres.filtres.type?.length !==
                  dataTypesEvenements?.items.filter((te) => te.actif).length
                    ? "d-none"
                    : ""
                }`}
                type="link"
                size="small"
                onClick={() =>
                  setFiltres({
                    type: [],
                  })
                }
                icon={<CloseCircleFilled className="mb-1" />}
              />
            </span>
            <TypeEvenementFilter
              setValue={(value) => setFiltres({ type: value })}
              value={appAffichageFiltres.filtres.type}
            />
          </Space>
        </li>
      </ul>
    </Layout.Sider>
  );
}
