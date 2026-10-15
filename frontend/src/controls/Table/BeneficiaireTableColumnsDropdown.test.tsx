/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React from "react";
import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { getBeneficiaireTableColumnOptions } from "./BeneficiaireTableColumns";
import { BeneficiaireTableColumnsDropdown } from "./BeneficiaireTableColumnsDropdown";

describe("BeneficiaireTableColumnsDropdown", () => {
  const optionsSansCompl = getBeneficiaireTableColumnOptions(true);
  const colonnesVisiblesDefaut = optionsSansCompl.map((o) => o.key);

  it("affiche le bouton Colonnes et ouvre la liste au clic", () => {
    render(
      <BeneficiaireTableColumnsDropdown
        colonnesDisponibles={optionsSansCompl}
        colonnesVisibles={colonnesVisiblesDefaut}
        onChangeColonnesVisibles={vi.fn()}
      />,
    );

    const btn = screen.getByRole("button", { name: /colonnes/i });
    expect(btn).toBeInTheDocument();

    fireEvent.click(btn);
    expect(screen.getAllByText("Colonnes")).toHaveLength(2);
    expect(screen.getByRole("button", { name: "Tout afficher" })).toBeInTheDocument();
  });

  it("affiche les colonnes complémentaires dans la liste si présentes", () => {
    const optionsAvecCompl = getBeneficiaireTableColumnOptions(true, [
      "Boursier",
      "Sportif de haut niveau",
    ]);

    render(
      <BeneficiaireTableColumnsDropdown
        colonnesDisponibles={optionsAvecCompl}
        colonnesVisibles={optionsAvecCompl.map((o) => o.key)}
        onChangeColonnesVisibles={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: /colonnes/i }));

    // Les colonnes complémentaires sont bien listées
    expect(screen.getByRole("checkbox", { name: "Boursier" })).toBeInTheDocument();
    expect(screen.getByRole("checkbox", { name: "Sportif de haut niveau" })).toBeInTheDocument();
  });

  it("appelle onChangeColonnesVisibles lors du clic sur une checkbox", () => {
    const onChange = vi.fn();
    render(
      <BeneficiaireTableColumnsDropdown
        colonnesDisponibles={optionsSansCompl}
        colonnesVisibles={colonnesVisiblesDefaut}
        onChangeColonnesVisibles={onChange}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: /colonnes/i }));

    const cbComposantes = screen.getByRole("checkbox", { name: "Composantes" });
    fireEvent.click(cbComposantes);

    expect(onChange).toHaveBeenCalled();
  });

  it("la colonne Bénéficiaire est cochée et disabled", () => {
    render(
      <BeneficiaireTableColumnsDropdown
        colonnesDisponibles={optionsSansCompl}
        colonnesVisibles={[]}
        onChangeColonnesVisibles={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: /colonnes/i }));

    const cbBeneficiaire = screen.getByRole("checkbox", { name: "Bénéficiaire" });
    expect(cbBeneficiaire).toBeChecked();
    expect(cbBeneficiaire).toBeDisabled();
  });

  it("le bouton Réinitialiser appelle onReset", () => {
    const onReset = vi.fn();
    render(
      <BeneficiaireTableColumnsDropdown
        colonnesDisponibles={optionsSansCompl}
        colonnesVisibles={colonnesVisiblesDefaut}
        onChangeColonnesVisibles={vi.fn()}
        onReset={onReset}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: /colonnes/i }));

    const btnReset = screen.getByRole("button", { name: "Réinitialiser" });
    fireEvent.click(btnReset);

    expect(onReset).toHaveBeenCalled();
  });
});
