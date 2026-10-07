/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import HtmlEditor from "./HtmlEditor";

/**
 * Tests de `HtmlEditor` (éditeur WYSIWYG tiptap/ProseMirror).
 *
 * Objectif : verrouiller le contrat observable du composant — barre d'outils,
 * contenu initial, remontée `onChange`, état actif des boutons, pose de lien —
 * afin de pouvoir valider une montée de version majeure de tiptap sans passer
 * par un test manuel de l'écran d'édition des chartes (seul appelant,
 * cf. `controls/Admin/Referentiel/Chartes/ChartesEdition.tsx`).
 *
 * Les boutons icônes n'ont pas de `aria-label` explicite : leur nom accessible
 * vient de l'`aria-label` posé par les icônes `@ant-design/icons`
 * (`BoldOutlined` → "bold"). C'est ce nom qui est ciblé ici, de sorte qu'une
 * régression sur l'accessibilité de la barre d'outils fasse échouer les tests.
 */

/** Zone éditable ProseMirror (pas de rôle accessible exploitable en jsdom). */
function getEditable(container: HTMLElement): HTMLElement {
  const editable = container.querySelector<HTMLElement>(".tiptap");
  if (!editable) {
    throw new Error("Zone éditable ProseMirror introuvable");
  }
  return editable;
}

function renderEditor(props: Parameters<typeof HtmlEditor>[0] = {}) {
  return render(<HtmlEditor {...props} />);
}

beforeEach(() => {
  // ProseMirror interroge la géométrie du DOM, absente de jsdom.
  const emptyRects = Object.assign([], { item: () => null }) as unknown as DOMRectList;
  Range.prototype.getClientRects = vi.fn(
    () => emptyRects,
  ) as unknown as typeof Range.prototype.getClientRects;
  Range.prototype.getBoundingClientRect = vi.fn(() => ({}) as DOMRect);
});

afterEach(() => {
  vi.restoreAllMocks();
});

// ---------------------------------------------------------------------------
// Barre d'outils
// ---------------------------------------------------------------------------

describe("HtmlEditor — barre d'outils", () => {
  it("rend les boutons de mise en forme avec un nom accessible", async () => {
    renderEditor();

    for (const name of ["bold", "italic", "strikethrough", "link"]) {
      expect(await screen.findByRole("button", { name })).toBeInTheDocument();
    }
  });

  it("rend les boutons de liste", async () => {
    renderEditor();

    expect(await screen.findByRole("button", { name: "unordered-list" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "ordered-list" })).toBeInTheDocument();
  });

  it("rend le bouton paragraphe et les six niveaux de titre", async () => {
    renderEditor();

    expect(await screen.findByRole("button", { name: "P" })).toBeInTheDocument();
    for (const level of [1, 2, 3, 4, 5, 6]) {
      expect(screen.getByRole("button", { name: `H${level}` })).toBeInTheDocument();
    }
  });
});

// ---------------------------------------------------------------------------
// Contenu initial
// ---------------------------------------------------------------------------

describe("HtmlEditor — contenu initial", () => {
  it("monte une zone éditable", async () => {
    const { container } = renderEditor();

    await waitFor(() => expect(getEditable(container)).toBeInTheDocument());
    expect(getEditable(container)).toHaveAttribute("contenteditable", "true");
  });

  it("injecte la prop value dans l'éditeur", async () => {
    const { container } = renderEditor({ value: "<p>Charte de l'établissement</p>" });

    await waitFor(() =>
      expect(getEditable(container).innerHTML).toContain("Charte de l'établissement"),
    );
  });

  it("conserve la mise en forme du HTML fourni", async () => {
    const { container } = renderEditor({ value: "<h2>Titre</h2><p><strong>gras</strong></p>" });

    await waitFor(() => expect(getEditable(container).querySelector("h2")).not.toBeNull());
    expect(getEditable(container).querySelector("strong")).not.toBeNull();
  });

  it("n'ajoute pas de paragraphe parasite en fin de document", async () => {
    // Garde-fou sur `trailingNode: false` : cette extension, incluse par défaut
    // dans le StarterKit v3, ajoute un paragraphe vide après un document terminé
    // par un titre ou une liste, qui se retrouverait dans le contenu enregistré.
    const { container } = renderEditor({ value: "<h2>Titre</h2><ul><li><p>un</p></li></ul>" });

    await waitFor(() => expect(getEditable(container).querySelector("ul")).not.toBeNull());
    expect(getEditable(container).lastElementChild?.tagName.toLowerCase()).toBe("ul");
  });

  it("accepte l'absence de value", async () => {
    const { container } = renderEditor();

    await waitFor(() => expect(getEditable(container)).toBeInTheDocument());
  });
});

// ---------------------------------------------------------------------------
// Remontée des modifications
// ---------------------------------------------------------------------------

describe("HtmlEditor — onChange", () => {
  it("remonte le HTML sérialisé quand le document change", async () => {
    const onChange = vi.fn();
    renderEditor({ value: "<p>texte</p>", onChange });

    const h1 = await screen.findByRole("button", { name: "H1" });
    h1.click();

    await waitFor(() => expect(onChange).toHaveBeenCalled());
    expect(onChange.mock.lastCall?.[0]).toContain("<h1>");
  });

  it("n'appelle pas onChange au simple montage", async () => {
    const onChange = vi.fn();
    const { container } = renderEditor({ value: "<p>texte</p>", onChange });

    await waitFor(() => expect(getEditable(container)).toBeInTheDocument());
    expect(onChange).not.toHaveBeenCalled();
  });

  it("ne lève pas si onChange est absent", async () => {
    renderEditor({ value: "<p>texte</p>" });

    const h1 = await screen.findByRole("button", { name: "H1" });
    expect(() => h1.click()).not.toThrow();
  });
});

// ---------------------------------------------------------------------------
// État actif des boutons
// ---------------------------------------------------------------------------

describe("HtmlEditor — état actif", () => {
  it("marque le bouton de titre actif quand le curseur est dans un titre", async () => {
    renderEditor({ value: "<p>texte</p>" });

    const h2 = await screen.findByRole("button", { name: "H2" });
    h2.click();

    await waitFor(() => expect(h2).toHaveClass("is-active"));
  });

  it("ne marque pas actif un bouton dont la marque n'est pas appliquée", async () => {
    renderEditor({ value: "<p>texte</p>" });

    expect(await screen.findByRole("button", { name: "bold" })).not.toHaveClass("is-active");
  });
});

// ---------------------------------------------------------------------------
// Pose de lien
// ---------------------------------------------------------------------------

describe("HtmlEditor — lien", () => {
  it("met à jour l'URL d'un lien existant sous le curseur", async () => {
    const prompt = vi.spyOn(window, "prompt").mockReturnValue("https://univ-bordeaux.fr");
    const { container } = renderEditor({
      value: '<p><a href="https://exemple.test">texte</a></p>',
    });

    await waitFor(() => expect(getEditable(container).querySelector("a")).not.toBeNull());
    (await screen.findByRole("button", { name: "link" })).click();

    // L'URL courante est proposée comme valeur par défaut de la fenêtre de saisie.
    expect(prompt).toHaveBeenCalledWith("URL", "https://exemple.test");
    await waitFor(() =>
      expect(getEditable(container).querySelector("a")).toHaveAttribute(
        "href",
        "https://univ-bordeaux.fr",
      ),
    );
  });

  it("ne crée pas de lien quand aucun texte n'est sélectionné", async () => {
    // Limite connue du composant : `setLink` s'applique à la sélection, et
    // `extendMarkRange("link")` n'a rien à étendre hors d'un lien existant.
    // Au montage la sélection est vide (curseur), donc le clic reste sans effet.
    vi.spyOn(window, "prompt").mockReturnValue("https://univ-bordeaux.fr");
    const onChange = vi.fn();
    const { container } = renderEditor({ value: "<p>texte</p>", onChange });

    (await screen.findByRole("button", { name: "link" })).click();

    await waitFor(() => expect(getEditable(container)).toBeInTheDocument());
    expect(getEditable(container).querySelector("a")).toBeNull();
    expect(onChange).not.toHaveBeenCalled();
  });

  it("ne modifie pas le document quand la saisie est annulée", async () => {
    vi.spyOn(window, "prompt").mockReturnValue(null);
    const onChange = vi.fn();
    const { container } = renderEditor({ value: "<p>texte</p>", onChange });

    const lien = await screen.findByRole("button", { name: "link" });
    lien.click();

    await waitFor(() => expect(getEditable(container)).toBeInTheDocument());
    expect(onChange).not.toHaveBeenCalled();
    expect(getEditable(container).querySelector("a")).toBeNull();
  });

  it("retire le lien quand la saisie est vidée", async () => {
    vi.spyOn(window, "prompt").mockReturnValue("");
    const { container } = renderEditor({
      value: '<p><a href="https://univ-bordeaux.fr">texte</a></p>',
    });

    await waitFor(() => expect(getEditable(container).querySelector("a")).not.toBeNull());
    (await screen.findByRole("button", { name: "link" })).click();

    await waitFor(() => expect(getEditable(container).querySelector("a")).toBeNull());
  });
});

// ---------------------------------------------------------------------------
// Accessibilité
// ---------------------------------------------------------------------------

describe("HtmlEditor — accessibilité", () => {
  it("aucune violation axe-core", async () => {
    const { container } = renderEditor({ value: "<p>texte</p>" });

    await waitFor(() => expect(getEditable(container)).toBeInTheDocument());

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  // La zone éditable est un `div[contenteditable]` sans attribut `role` : on cible
  // donc `aria-label` directement. `role="textbox"` serait contre-productif ici —
  // il aplatirait le contenu et les lecteurs d'écran n'annonceraient plus les
  // titres, listes et liens du document en cours d'édition.
  it("nomme la zone d'édition avec le libellé fourni", async () => {
    const { container } = renderEditor({
      value: "<p>texte</p>",
      ariaLabel: "Contenu de la charte",
    });

    await waitFor(() =>
      expect(getEditable(container)).toHaveAttribute("aria-label", "Contenu de la charte"),
    );
  });

  it("retombe sur un nom accessible par défaut sans ariaLabel", async () => {
    const { container } = renderEditor({ value: "<p>texte</p>" });

    await waitFor(() =>
      expect(getEditable(container)).toHaveAttribute("aria-label", "Éditeur de contenu"),
    );
  });

  it("chaque bouton de la barre d'outils porte un nom accessible", async () => {
    renderEditor();

    const boutons = await screen.findAllByRole("button");
    expect(boutons).toHaveLength(13);
    for (const bouton of boutons) {
      expect(bouton).toHaveAccessibleName();
    }
  });
});
