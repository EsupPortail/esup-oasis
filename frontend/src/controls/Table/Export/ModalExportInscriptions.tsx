/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React, { useState } from "react";
import { Modal, Radio, Space, Typography } from "antd";
import { PorteeExportInscriptions } from "@lib/inscription";

const CHOIX: { value: PorteeExportInscriptions; label: string; description: string }[] = [
  {
    value: "en_cours",
    label: "Inscriptions en cours",
    description: "Toutes les inscriptions en cours, l'inscription principale en premier.",
  },
  {
    value: "principale",
    label: "Inscription principale uniquement",
    description: "Une seule inscription par personne.",
  },
  {
    value: "toutes",
    label: "Toutes les inscriptions",
    description:
      "Les inscriptions en cours et, dans une colonne distincte, les inscriptions passées.",
  },
];

interface ModalExportInscriptionsProps {
  /** Choix présélectionné (dernier choix de l'utilisateur). */
  defaultValue: PorteeExportInscriptions;
  onOk: (portee: PorteeExportInscriptions) => void;
  onCancel: () => void;
}

/** Demande à l'utilisateur quelles inscriptions inclure dans l'export. */
export function ModalExportInscriptions({
  defaultValue,
  onOk,
  onCancel,
}: ModalExportInscriptionsProps): React.ReactElement {
  const [portee, setPortee] = useState<PorteeExportInscriptions>(defaultValue);

  return (
    <Modal
      open
      centered
      title="Inscriptions à exporter"
      okText="Exporter"
      cancelText="Annuler"
      onOk={() => onOk(portee)}
      onCancel={onCancel}
    >
      <Radio.Group className="mt-3 mb-3" value={portee} onChange={(e) => setPortee(e.target.value)}>
        <Space orientation="vertical" size="middle">
          {CHOIX.map((choix) => (
            <Radio key={choix.value} value={choix.value}>
              <Typography.Text strong>{choix.label}</Typography.Text>
              <br />
              <Typography.Text type="secondary">{choix.description}</Typography.Text>
            </Radio>
          ))}
        </Space>
      </Radio.Group>
    </Modal>
  );
}
