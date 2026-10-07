/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { NavigateFunction } from "react-router";
import { Badge, MenuProps } from "antd";
import React from "react";
import { env } from "@/env";

/**
 * Generates the logo menu item with env ribbon.
 *
 * @param setSelectedKey
 * @param {NavigateFunction} navigate - The navigation function.
 * @return {MenuProps["items"]} The generated logo menu item.
 */
export function menuItemLogo(
  setSelectedKey: (key: string | undefined) => void,
  navigate: NavigateFunction,
): MenuProps["items"] {
  return [
    {
      key: "logo",
      label:
        env.REACT_APP_ENVIRONMENT === "production" ? (
          <span className="logo">{env.REACT_APP_TITRE}</span>
        ) : (
          <Badge.Ribbon
            color="var(--color-danger)"
            className="fs-08"
            style={{ top: 0, right: -15 }}
            text={["local", "dev"].includes(env.REACT_APP_ENVIRONMENT as string) ? "DEV" : "TEST"}
          >
            {env.REACT_APP_TITRE}️
          </Badge.Ribbon>
        ),
      className: "logo text-primary",
      onClick: () => {
        setSelectedKey(undefined);
        navigate("/dashboard");
      },
    },
  ];
}
