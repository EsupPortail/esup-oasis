/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { describe, expect, it, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { ModalExportInscriptions } from "./ModalExportInscriptions";

describe("ModalExportInscriptions", () => {
  it("présélectionne le choix par défaut", () => {
    render(<ModalExportInscriptions defaultValue="toutes" onOk={vi.fn()} onCancel={vi.fn()} />);

    expect(screen.getByRole("radio", { name: /^Toutes les inscriptions/ })).toBeChecked();
  });

  it("renvoie le choix de l'utilisateur à la validation", async () => {
    const onOk = vi.fn();
    render(<ModalExportInscriptions defaultValue="en_cours" onOk={onOk} onCancel={vi.fn()} />);

    await userEvent.click(
      screen.getByRole("radio", { name: /^Inscription principale uniquement/ }),
    );
    await userEvent.click(screen.getByRole("button", { name: "Exporter" }));

    expect(onOk).toHaveBeenCalledWith("principale");
  });

  it("n'exporte pas à l'annulation", async () => {
    const onOk = vi.fn();
    const onCancel = vi.fn();
    render(<ModalExportInscriptions defaultValue="en_cours" onOk={onOk} onCancel={onCancel} />);

    await userEvent.click(screen.getByRole("button", { name: "Annuler" }));

    expect(onCancel).toHaveBeenCalled();
    expect(onOk).not.toHaveBeenCalled();
  });
});
