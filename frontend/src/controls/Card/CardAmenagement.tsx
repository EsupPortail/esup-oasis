/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Fabien Léon <fabien.leon@univ-brest.fr>
 */

import { Button, Card, Col,Flex, Space, Tag, Tooltip } from "antd";
import {
   ArrowRightOutlined,
   CalendarOutlined,
   CommentOutlined,
   EditOutlined,
   HarmonyOSOutlined,
} from "@ant-design/icons";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { useSearchParams } from "react-router-dom";
import { IAmenagement } from "../../api/ApiTypeHelpers";
import { getLibellePeriode } from "../../utils/dates";
import { EllipsisParagraph } from "../Typography/EllipsisParagraph";
import SuiviAmenagementItem from "../Items/SuiviAmenagementItem";


export function CardAmenagement (props: {
    couleur?: string;
    categorie: string;
    type: string;
    amenagement: IAmenagement;
    onClickEdit?: (amenagement: IAmenagement) => void;
}){
    const screens = useBreakpoint();
    const [searchParams] = useSearchParams();
    const highlightAmenagement = searchParams.get("amenagement");
    return (
        <>
            <Col key={props.amenagement["@id"]} xs={24} sm={24} md={24} lg={12} xl={8} xxl={6}>
                <Card
                    style={{ borderColor: "#e0e0e0" }}
                    styles={
                        screens.lg ? { body: { minHeight: 195 } } : undefined
                    }
                    className={`${
                        highlightAmenagement === props.amenagement["@id"]
                            ? "highlightAmenagement"
                            : ""
                    } bg-${props.couleur}-xxlight`}
                >
                <Card.Meta
                    title={
                        <Flex
                        wrap="wrap"
                        className="w-100"
                        justify="space-between"
                        align="start"
                        >
                        <div>
                            <h5 className="mt-0 mb-0 fs-11">{props.type}</h5>
                            <span
                                className={`text-${props.couleur}-dark fs-09`}
                            >
                                <ArrowRightOutlined className="mr-1" />
                                {props.categorie}
                            </span>
                        </div>
                        { props.onClickEdit &&
                            <Button
                                className="mr-0 pr-0"
                                type="text"
                                icon={<EditOutlined aria-label="Menu" />}
                                onClick={ () => props.onClickEdit?.(props.amenagement)}
                            />
                        }
                        </Flex>
                    }
                    description={
                        <Space direction="vertical" className="text-text">
                        {props.amenagement.debut || props.amenagement.fin ? (
                            <Space align="start" size={[8, 2]} wrap>
                                <CalendarOutlined />
                                <Tag>
                                    {getLibellePeriode(props.amenagement.debut, props.amenagement.fin, "MMM")}
                                </Tag>
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
                            </Space>
                        ) : null}
                        {props.amenagement.commentaire && props.amenagement.commentaire.length > 0 && (
                            <Space align="start" size={12}>
                                <CommentOutlined />
                                <EllipsisParagraph
                                    content={props.amenagement.commentaire}
                                    className="light mb-0 fs-09"
                                    type="secondary"
                                />
                            </Space>
                        )}
                        {props.amenagement.suivi ? (
                            <Space align="start" size={12}>
                                <HarmonyOSOutlined />
                                <SuiviAmenagementItem
                                    suiviId={props.amenagement.suivi}
                                    className="float-right"
                                    couleur={props.couleur}
                                />
                            </Space>
                        ) : null}
                        </Space>
                    }
                />
                </Card>
            </Col>
        </>
    )
}