/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { render, screen } from "@testing-library/react";
import { Form } from "antd";
import { afterEach, describe, it, expect } from "vitest";
import { QuestionInfo } from "./QuestionInfo";
import { drainAntdTimers } from "@/test";

function renderQuestion(aide: string) {
  return render(
    <Form>
      <QuestionInfo question={{ "@id": "/questions/1", aide } as never} />
    </Form>,
  );
}

// antd arme un timer non annulé au démontage (cf. drainAntdTimers) : ces tests
// étant synchrones, on le laisse s'écouler avant la destruction de l'environnement.
afterEach(async () => {
  await drainAntdTimers();
});

describe("QuestionInfo — sanitisation du HTML d'aide (XSS)", () => {
  it("préserve la mise en forme légitime", () => {
    renderQuestion("<b>Texte important</b> et <a href='https://exemple.fr'>lien</a>");
    expect(screen.getByText("Texte important")).toBeInTheDocument();
    expect(screen.getByRole("link", { name: "lien" })).toHaveAttribute(
      "href",
      "https://exemple.fr",
    );
  });

  it("supprime les balises <script>", () => {
    renderQuestion("<p>Aide</p><script>window.__xss = true;</script>");
    expect(screen.getByText("Aide")).toBeInTheDocument();
    expect(document.querySelector("script")).toBeNull();
  });

  it("retire les gestionnaires d'événements inline (onerror)", () => {
    renderQuestion('<img src="x" onerror="window.__xss = true">');
    const img = document.querySelector("img");
    expect(img).not.toBeNull();
    expect(img?.getAttribute("onerror")).toBeNull();
  });

  it("neutralise les URLs javascript: dans les liens", () => {
    renderQuestion('<a href="javascript:alert(1)">piège</a>');
    const link = screen.getByText("piège").closest("a");
    expect(link?.getAttribute("href") ?? "").not.toContain("javascript:");
  });
});
