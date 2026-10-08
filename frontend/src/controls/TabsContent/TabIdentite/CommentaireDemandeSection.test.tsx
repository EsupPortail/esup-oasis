/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, it, expect, vi } from "vitest";
import { renderWithProviders } from "@/test";
import { CommentaireDemandeSection } from "./CommentaireDemandeSection";

const rendre = (props: Partial<React.ComponentProps<typeof CommentaireDemandeSection>> = {}) => {
  const setCommentaire = vi.fn();
  const mutateDemande = vi.fn();
  renderWithProviders(
    <CommentaireDemandeSection
      demandeId="/demandes/1"
      isFetching={false}
      commentaire="Bonjour"
      setCommentaire={setCommentaire}
      mutateDemande={mutateDemande}
      {...props}
    />,
  );
  return { setCommentaire, mutateDemande };
};

describe("CommentaireDemandeSection", () => {
  it("affiche le titre et le commentaire", () => {
    rendre();
    expect(screen.getByRole("heading", { name: "Commentaire sur la demande" })).toBeVisible();
    expect(screen.getByRole("textbox")).toHaveValue("Bonjour");
  });

  it("affiche un skeleton pendant le chargement", () => {
    rendre({ isFetching: true });
    expect(screen.queryByRole("textbox")).not.toBeInTheDocument();
  });

  it("appelle setCommentaire à la saisie", async () => {
    const { setCommentaire } = rendre();
    await userEvent.type(screen.getByRole("textbox"), "!");
    expect(setCommentaire).toHaveBeenCalledWith("Bonjour!");
  });

  it("n'enregistre pas au blur si le texte n'a pas changé", async () => {
    const { mutateDemande } = rendre();
    await userEvent.click(screen.getByRole("textbox"));
    await userEvent.tab();
    expect(mutateDemande).not.toHaveBeenCalled();
  });

  it("enregistre au blur si le texte a changé depuis le focus", async () => {
    const mutateDemande = vi.fn();
    const props = {
      demandeId: "/demandes/1",
      isFetching: false,
      setCommentaire: vi.fn(),
      mutateDemande,
    };
    const { rerender } = renderWithProviders(
      <CommentaireDemandeSection {...props} commentaire="Avant" />,
    );
    await userEvent.click(screen.getByRole("textbox"));
    rerender(<CommentaireDemandeSection {...props} commentaire="Après" />);
    await userEvent.tab();
    expect(mutateDemande).toHaveBeenCalledWith({
      data: { commentaire: "Après" },
      "@id": "/demandes/1",
    });
  });
});
