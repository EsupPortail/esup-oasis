/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { NavigateFunction } from "react-router-dom";
import { Button, MenuProps } from "antd";
import React from "react";

/**
 * Returns an array of menu items for the "Services faits" menu.
 *
 * @param setSelectedKey
 * @param {Function} navigate - The navigate function to handle navigation.
 * @param className
 * @param labels
 * @return {Array} The menu items.
 */
export const menuItemServicesFaitsIntervenant = (
  setSelectedKey: (key: string) => void,
  navigate: NavigateFunction,
  className?: string,
  labels?: Record<string, string>,
): MenuProps["items"] => [
  {
    key: "services-faits",
    label: (
      <Button
        type="text"
        className="no-hover p-0"
        onClick={() => {
          setSelectedKey("services-faits");
          navigate("/services-faits");
        }}
      >
        {labels?.MENU_SERVICES_FAITS ?? "Services faits"}
      </Button>
    ),
    className,
    children: [],
    popupClassName: "d-none",
  },
];
