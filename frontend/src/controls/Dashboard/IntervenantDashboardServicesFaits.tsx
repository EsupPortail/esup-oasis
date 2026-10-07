/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useAuth } from "@/auth/AuthProvider";
import { useNavigate } from "react-router";
import { useApi } from "@context/api/ApiProvider";
import { Button, Card, Col, Empty, List, Row, Typography } from "antd";
import { ServiceFaitItem } from "@controls/ServicesFaits/ServiceFaitItem";
import React from "react";
import EtudiantDashboardImage from "@controls/Images/EtudiantDashboardImage";

export function IntervenantDashboardServicesFaits() {
  const user = useAuth().user;
  const navigate = useNavigate();
  const { data: servicesFaitsIntervenant, isLoading } = useApi().useGetCollectionPaginated({
    path: "/intervenants/{uid}/services_faits",
    page: 1,
    itemsPerPage: 3,
    parameters: {
      uid: `/intervenants/${user?.uid}` as string,
    },
    query: {
      "order[debut]": "desc",
    },
    enabled: !!user?.uid,
  });

  return (
    <>
      <Typography.Title level={2}>Relevés de services faits</Typography.Title>
      <Row gutter={[16, 16]}>
        <Col xs={24} sm={12} lg={8}>
          <EtudiantDashboardImage style={{ width: "100%", maxHeight: 380 }} />
        </Col>
        <Col xs={24} sm={12} lg={16}>
          <Card
            variant="borderless"
            loading={isLoading}
            title={<h3>3 derniers relevés de services faits</h3>}
          >
            <List
              className="services-faits"
              loadMore={
                <div className="text-right">
                  <Button type="link" onClick={() => navigate("/services-faits?historique=1")}>
                    Voir tous
                  </Button>
                </div>
              }
            >
              <ul className="list-nostyle">
                {servicesFaitsIntervenant?.items.map((servicesFaits) => {
                  return (
                    <ServiceFaitItem key={servicesFaits["@id"]} servicesFaits={servicesFaits} />
                  );
                })}
                {servicesFaitsIntervenant?.items.length === 0 && (
                  <List.Item className="" key="empty">
                    <Empty
                      image={Empty.PRESENTED_IMAGE_SIMPLE}
                      className="m-auto"
                      description="Aucun relevé de services faits"
                    />
                  </List.Item>
                )}
              </ul>
            </List>
          </Card>
        </Col>
      </Row>
    </>
  );
}
