/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { render, renderHook, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import {
  BENEFICIAIRE_TABLE_COLUMNS_KEYS,
  beneficiaireTableColumns,
  getBeneficiaireTableColumnOptions,
} from "./BeneficiaireTableColumns";
import { IBeneficiaire } from "@api";
import React from "react";

import { FiltreBeneficiaire } from "./BeneficiaireTable";

describe("BeneficiaireTableColumns", () => {
  describe("getBeneficiaireTableColumnOptions", () => {
    it("retourne les colonnes standard sans colonnes complémentaires", () => {
      const options = getBeneficiaireTableColumnOptions(true);
      const keys = options.map((o) => o.key);

      expect(keys).toContain(BENEFICIAIRE_TABLE_COLUMNS_KEYS.NOM);
      expect(keys).toContain(BENEFICIAIRE_TABLE_COLUMNS_KEYS.ACTIONS);
      expect(keys[keys.length - 1]).toBe(BENEFICIAIRE_TABLE_COLUMNS_KEYS.ACTIONS);
    });

    it("ajoute les colonnes complémentaires avant Actions et déduplique", () => {
      const options = getBeneficiaireTableColumnOptions(true, [
        "Boursier",
        "Aménagement examens",
        "Boursier", // duplicata
      ]);
      const keys = options.map((o) => o.key);

      expect(keys).toContain("Boursier");
      expect(keys).toContain("Aménagement examens");
      expect(keys[keys.length - 1]).toBe(BENEFICIAIRE_TABLE_COLUMNS_KEYS.ACTIONS);

      // Vérifie que "Boursier" n'est présent qu'une seule fois
      expect(keys.filter((k) => k === "Boursier")).toHaveLength(1);

      // Vérifie qu'ils sont placés juste avant Actions
      const actionsIdx = keys.indexOf(BENEFICIAIRE_TABLE_COLUMNS_KEYS.ACTIONS);
      expect(keys[actionsIdx - 2]).toBe("Boursier");
      expect(keys[actionsIdx - 1]).toBe("Aménagement examens");
    });
  });

  describe("beneficiaireTableColumns", () => {
    const defaultProps = {
      user: { isGestionnaire: true, isAdmin: false } as unknown as never,
      filter: {} as FiltreBeneficiaire,
      setFilter: vi.fn(),
      onBeneficiaireSelected: vi.fn(),
      onImpersonate: vi.fn(),
    };

    it("crée les colonnes pour les informations complémentaires demandées", () => {
      const { result } = renderHook(() =>
        beneficiaireTableColumns({
          ...defaultProps,
          colonnesComplementaires: ["Boursier", "Sportif HN"],
          colonnesVisibles: ["nom", "Boursier", "actions"],
        }),
      );

      const keys = result.current.map((c) => c.key);
      expect(keys).toEqual(["nom", "Boursier", "actions"]);

      const boursierCol = result.current.find((c) => c.key === "Boursier");
      expect(boursierCol).toBeDefined();
      expect(boursierCol?.title).toBe("Boursier");
    });

    it("affiche la valeur de l'info complémentaire quand elle existe", () => {
      const { result } = renderHook(() =>
        beneficiaireTableColumns({
          ...defaultProps,
          colonnesComplementaires: ["Boursier"],
          colonnesVisibles: ["Boursier"],
        }),
      );

      const boursierCol = result.current[0];
      const beneficiaire = {
        "@id": "/utilisateurs/1",
        infosComplementaires: [{ libelle: "Boursier", valeur: "Échelon 5" }],
      } as unknown as IBeneficiaire;

      const rendered = boursierCol.render?.(undefined, beneficiaire, 0);
      render(<div>{rendered as React.ReactNode}</div>);

      expect(screen.getByText("Échelon 5")).toBeInTheDocument();
    });

    it("affiche un tiret quand l'info complémentaire est absente pour le bénéficiaire", () => {
      const { result } = renderHook(() =>
        beneficiaireTableColumns({
          ...defaultProps,
          colonnesComplementaires: ["Boursier"],
          colonnesVisibles: ["Boursier"],
        }),
      );

      const boursierCol = result.current[0];
      const beneficiaire = {
        "@id": "/utilisateurs/1",
        infosComplementaires: [],
      } as unknown as IBeneficiaire;

      const rendered = boursierCol.render?.(undefined, beneficiaire, 0);
      const { container } = render(<div>{rendered as React.ReactNode}</div>);

      expect(container.querySelector(".anticon-minus")).toBeInTheDocument();
    });
  });
});
