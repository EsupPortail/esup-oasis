/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { render, screen, fireEvent } from "@testing-library/react";
import { Form } from "antd";
import { afterEach, describe, it, expect, vi, beforeEach } from "vitest";
import { QuestionText } from "./QuestionText";
import { drainAntdTimers } from "@/test";

const mockEnvoyerReponse = vi.fn();
let mockMode = "edition";
vi.mock("@context/demande/QuestionnaireProvider", () => ({
  useQuestionnaire: () => ({
    questUtils: { envoyerReponse: mockEnvoyerReponse },
    mode: mockMode,
    form: undefined,
  }),
}));
vi.mock("@controls/Questionnaire/Question/QuestionAide", () => ({ QuestionAide: () => null }));

const question = {
  "@id": "/questions/1",
  libelle: "Votre nom",
  typeReponse: "text",
  obligatoire: true,
};

function renderText() {
  return render(
    <Form>
      <QuestionText question={question as never} />
    </Form>,
  );
}

// antd arme un timer non annulé au démontage (cf. drainAntdTimers) : ces tests
// étant synchrones, on le laisse s'écouler avant la destruction de l'environnement.
afterEach(async () => {
  await drainAntdTimers();
});

describe("QuestionText", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockMode = "edition";
  });

  it("affiche le libellé et un champ de saisie lié à l'@id de la question", () => {
    renderText();
    expect(screen.getByText("Votre nom")).toBeInTheDocument();
    const input = screen.getByRole("textbox");
    expect(input).toHaveAttribute("data-question", "/questions/1");
    expect(input).toHaveAttribute("data-type", "text");
  });

  it("à la perte de focus : envoie la réponse saisie (@id, type, valeur)", () => {
    renderText();
    const input = screen.getByRole("textbox");
    fireEvent.change(input, { target: { value: "Ada Lovelace" } });
    fireEvent.blur(input, { target: { value: "Ada Lovelace" } });

    expect(mockEnvoyerReponse).toHaveBeenCalledWith(
      "/questions/1",
      "text",
      "Ada Lovelace",
      expect.any(Function),
      expect.any(Function),
    );
  });

  it("mode preview : le champ est désactivé (saisie impossible)", () => {
    mockMode = "preview";
    renderText();
    expect(screen.getByRole("textbox")).toBeDisabled();
  });
});
