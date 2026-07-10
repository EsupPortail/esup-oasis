/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import styles from "@controls/Dashboard/MonoStackedBar/styles.module.css";
import { Tooltip } from "antd";
import "@routes/intervenant/dashboard/Dashboard.scss";

interface BarData {
  value: number;
  color?: string;
  caption?: string;
  tooltip?: string;
  foreground?: string;
}

interface MonoStackedBarProps {
  data: (number | BarData)[];
  colors?: string[];
  displayLabels?: boolean;
  labelColor?: string;
  unit?: string;
  width?: number;
  height?: number;
  radius?: number;
  onClick?: () => void;
}

const isNumber = (data: number | BarData): boolean => {
  return typeof data === "number";
};

const getSectionValue = (section: number | BarData): number => {
  return isNumber(section) ? (section as number) : (section as BarData).value;
};

const getSectionColor = (section: number | BarData): string | undefined => {
  return isNumber(section) ? undefined : (section as BarData).color;
};

const getSectionForegroundColor = (section: number | BarData): string | undefined => {
  return isNumber(section) ? undefined : (section as BarData).foreground;
};

function isBarDataValues(arr: (number | BarData)[]): arr is BarData[] {
  if (!Array.isArray(arr)) {
    return false;
  }
  return arr.every((elt: number | BarData) => typeof elt !== "number");
}

const toPx = (size: number | undefined): string | undefined => {
  if (!size) return "0";
  return `${size}px`;
};

const capitalize = (s: string): string => {
  return s.charAt(0).toUpperCase() + s.slice(1);
};

const sumSectionArray = (sectionArray: (number | BarData)[]): number => {
  if (sectionArray.length === 0) return 0;
  return sectionArray.map((section) => getSectionValue(section)).reduce((a, b) => a + b);
};

export default function MonoStackedBar({
  data,
  colors = [],
  displayLabels = true,
  labelColor = "white",
  unit = "",
  width,
  height = 20,
  radius = 10,
  onClick,
}: MonoStackedBarProps): React.ReactElement {
  const sumValues = data.length
    ? data.map((section) => getSectionValue(section)).reduce((a, b) => a + b)
    : 1;

  return (
    <div
      style={{
        maxWidth: width,
      }}
    >
      <div
        role="button"
        tabIndex={0}
        className={`${styles.stackedBar}${onClick ? " pointer" : ""}`}
        onClick={onClick}
        onKeyDown={(e) => {
          if (e.key === "Enter" || e.key === " ") {
            onClick?.();
          }
        }}
      >
        {data.length &&
          data.map((section, index) => {
            const radiusPx = toPx(radius);
            const proportion = (getSectionValue(section) * 100) / sumValues;
            return (
              <Tooltip title={(section as BarData).tooltip} key={index}>
                <div
                  style={{
                    width: `${proportion}%`,
                    height: height,
                    borderRadius:
                      data.length === 1 || proportion === 100
                        ? radiusPx
                        : index === 0 || sumSectionArray(data.slice(0, index)) === 0
                          ? `${radiusPx} 0 0 ${radiusPx}`
                          : index === data.length - 1 ||
                              sumSectionArray(data.slice(index + 1)) === 0
                            ? `0 ${radiusPx} ${radiusPx} 0`
                            : "0",
                    backgroundColor: getSectionColor(section) || colors[index] || "#393986",
                    display: "flex",
                    alignItems: "center",
                  }}
                  className={styles.section}
                >
                  {displayLabels && proportion > 10 && (
                    <span
                      style={{
                        fontSize: height - height / 4,
                        color: getSectionForegroundColor(section) || labelColor,
                      }}
                      className={styles.sectionLabel}
                    >
                      {`${Math.round(getSectionValue(section))}${unit}`}
                    </span>
                  )}
                </div>
              </Tooltip>
            );
          })}
      </div>
      <div className={styles.caption}>
        {isBarDataValues(data) &&
          data.filter((section) => section.caption).length > 0 &&
          data.map((section, index) => {
            if (!section.caption) return <React.Fragment />;
            const proportion = (getSectionValue(section) * 100) / sumValues;
            if (proportion < 10) return <React.Fragment />;
            return (
              <div
                key={index}
                style={{
                  width: `${proportion}%`,
                  fontSize: height - height / 3,
                }}
              >
                {capitalize(section.caption)}
              </div>
            );
          })}
      </div>
    </div>
  );
}
