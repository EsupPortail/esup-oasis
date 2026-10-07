/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useNavigate } from "react-router";
import { Button, Tooltip } from "antd";
import { default as Icon } from "@ant-design/icons";
import React, { ReactElement } from "react";

interface IAdminPanelComponent {
  title: string;
  description: string;
  onClickUrl: string;
  icon: React.ComponentType;
  disabled?: boolean;
}

/**
 * Élément de menu de l'administration
 *
 * @param {IAdminPanelComponent} panelProps - The properties for the admin panel component.
 * @param {string} panelProps.title - The title of the panel.
 * @param {string} panelProps.description - The description of the panel.
 * @param {React.ElementType} panelProps.icon - The icon for the panel.
 * @param {string} panelProps.onClickUrl - The URL to navigate to when the panel is clicked.
 * @param {boolean} [panelProps.disabled=false] - Indicates whether the panel is disabled.
 *
 * @return {ReactElement} The rendered admin panel component.
 */
export function AdminPanel({
  title,
  description,
  icon,
  onClickUrl,
  disabled = false,
}: IAdminPanelComponent): ReactElement {
  const navigate = useNavigate();
  return (
    <Tooltip title={description} placement="bottom">
      <div key={title} className="grid-admin-item appear">
        <Button data-testid="panel" disabled={disabled} onClick={() => navigate(onClickUrl)}>
          <Icon component={icon} className="mt-2 grid-admin-item-icon" style={{ fontSize: 75 }} />
          <div className="grid-admin-item-content text-center">
            <div className="grid-admin-item-content-title">{title}</div>
          </div>
        </Button>
      </div>
    </Tooltip>
  );
}
