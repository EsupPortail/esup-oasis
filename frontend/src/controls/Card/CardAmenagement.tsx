/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Fabien Léon <fabien.leon@univ-brest.fr>
 */

import { Button, Card, Col, Flex, Space, Tag, Tooltip } from "antd";
import {
  ArrowRightOutlined,
  CalendarOutlined,
  CommentOutlined,
  EditOutlined,
  HarmonyOSOutlined,
} from "@ant-design/icons";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { useSearchParams } from "react-router-dom";
import { IAmenagement, ITypeAmenagement } from "@api";
import { getLibellePeriode, isEnCoursSurPeriode } from "@utils/dates";
import { EllipsisParagraph } from "../Typography/EllipsisParagraph";
import { SuiviAmenagementItem } from "../Items/SuiviAmenagementItem";
import { getDomaineAmenagement } from "@lib";
import "./CardAmenagement.css";

const iconColStyle = { width: 16, flexShrink: 0, textAlign: "center" as const };

export function CardAmenagement(props: {
  categorie: string;
  type: ITypeAmenagement;
  amenagement: IAmenagement;
  onClickEdit?: (amenagement: IAmenagement) => void;
  showCategorie?: boolean;
}) {
  const screens = useBreakpoint();
  const [searchParams] = useSearchParams();
  const highlightAmenagement = searchParams.get("amenagement");
  const domaine = getDomaineAmenagement(props.type);
  const expire = !isEnCoursSurPeriode(props.amenagement.debut, props.amenagement.fin);

  return (
    <>
      <Col key={props.amenagement["@id"]} xs={24} sm={24} md={24} lg={12} xl={8} xxl={6}>
        <Card
          styles={screens.lg ? { body: { minHeight: 195 } } : undefined}
          className={`${
            highlightAmenagement === props.amenagement["@id"] ? "highlightAmenagement" : ""
          } ${expire ? "amenagement-expire" : ""} bg-${domaine?.couleur}-xxlight border-${domaine?.couleur}-dark`}
        >
          <Card.Meta
            title={
              <Flex wrap="wrap" className="w-100" justify="space-between" align="start">
                <div>
                  {props.showCategorie && (
                    <Tag
                      className={`text-${domaine?.couleur}-dark bg-${domaine?.couleur}-light mb-1`}
                    >
                      {domaine?.singulier}
                    </Tag>
                  )}
                  <h5 className="mt-0 mb-0 fs-11" style={{ whiteSpace: "pre-wrap" }}>
                    {props.type.libelle}
                  </h5>
                  <span className={`text-${domaine?.couleur}-dark fs-09`}>
                    <ArrowRightOutlined className="mr-1" />
                    {props.categorie}
                  </span>
                </div>
                {props.onClickEdit && (
                  <Button
                    className="mr-0 pr-0"
                    type="text"
                    icon={<EditOutlined aria-label="Menu" />}
                    onClick={() => props.onClickEdit?.(props.amenagement)}
                  />
                )}
              </Flex>
            }
            description={
              <Space orientation="vertical" size={8} className="text-text w-100">
                {props.amenagement.debut || props.amenagement.fin ? (
                  <Flex align="start" gap={8} wrap>
                    <span style={iconColStyle}>
                      <CalendarOutlined />
                    </span>
                    <div>
                      {getLibellePeriode(props.amenagement.debut, props.amenagement.fin, "MMM")}
                    </div>
                    <Space size={0}>
                      {props.amenagement.semestre1 && (
                        <Tooltip title="Semestre 1">
                          <Tag>S1</Tag>
                        </Tooltip>
                      )}
                      {props.amenagement.semestre2 && (
                        <Tooltip title="Semestre 2">
                          <Tag>S2</Tag>
                        </Tooltip>
                      )}
                    </Space>
                  </Flex>
                ) : null}
                {props.amenagement.commentaire && props.amenagement.commentaire.length > 0 && (
                  <Flex align="start" gap={8}>
                    <span style={iconColStyle}>
                      <CommentOutlined />
                    </span>
                    <EllipsisParagraph
                      content={props.amenagement.commentaire}
                      className="mb-0 fs-09"
                    />
                  </Flex>
                )}
                {props.amenagement.suivi ? (
                  <Flex align="start" gap={8}>
                    <span style={iconColStyle}>
                      <HarmonyOSOutlined />
                    </span>
                    <SuiviAmenagementItem
                      suiviId={props.amenagement.suivi}
                      className={`float-right bg-${domaine?.couleur}-xlight border-${domaine?.couleur}-dark text-${domaine?.couleur}-dark`}
                      couleur={domaine?.couleur}
                    />
                  </Flex>
                ) : null}
              </Space>
            }
          />
        </Card>
      </Col>
    </>
  );
}
