/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Col, Form, Input, Typography } from "antd";
import { InfoCircleOutlined, MailOutlined, PhoneOutlined, UserOutlined } from "@ant-design/icons";
import { useAuth } from "@/auth/AuthProvider";
import { service } from "@lib";

/**
 * Render the contact section of the user profile
 *
 * @return {ReactElement} The contact profile component.
 */
export function MonProfilContact(): ReactElement {
  const user = useAuth().user;

  return (
    <Col span={24}>
      <Typography.Title level={2}>Pour vous contacter</Typography.Title>
      <div>
        <Form.Item name="nom" label="Nom">
          <Input prefix={<UserOutlined />} disabled />
        </Form.Item>

        <Form.Item name="prenom" label="Prénom">
          <Input disabled />
        </Form.Item>

        <Form.Item name="email" label="Email institutionnel">
          <Input prefix={<MailOutlined />} disabled />
        </Form.Item>

        <Form.Item
          name="emailPerso"
          label="Email personnel"
          className="mt-3"
          rules={[
            {
              type: "email",
              message: "Email non valide",
            },
          ]}
        >
          <Input prefix={<MailOutlined />} inputMode="email" type="email" />
        </Form.Item>

        <Form.Item label="Téléphone" className="mt-2" name="telPerso">
          <Input prefix={<PhoneOutlined />} inputMode="tel" />
        </Form.Item>

        <div className="legende mt-2">
          <InfoCircleOutlined className="mr-1" />
          Votre mail et votre numéro de téléphone personnels ne sont utilisés que par{" "}
          {service.defini}, ils ne sont pas communiqués aux autres utilisateurs de l'application.
        </div>

        {user?.isBeneficiaire && (
          <Form.Item label="Contact en cas d'urgence" className="mt-2" name="contactUrgence">
            <Input.TextArea />
          </Form.Item>
        )}
      </div>
    </Col>
  );
}
