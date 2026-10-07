import React from "react";
import { axe } from "vitest-axe";
import "vitest-axe/extend-expect";
import { screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { renderWithProviders } from "@/test";
import { makeDemande } from "@/test/fixtures";
import DemandeAvancement from "./DemandeAvancement";

// ─── Router ───────────────────────────────────────────────────────────────────

let mockParams: { id?: string } = { id: "42" };

vi.mock("react-router", async (importOriginal) => {
  const actual = await importOriginal<typeof import("react-router")>();
  return { ...actual, useNavigate: () => vi.fn(), useParams: () => mockParams };
});

// ─── API (pour DemandeProfilAttribue interne à AvancementDemande) ─────────────

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetItem: vi.fn(() => ({ data: undefined, isFetching: false })),
  }),
}));

// ─── Questionnaire ────────────────────────────────────────────────────────────

const { mockUseQuestionnaire } = vi.hoisted(() => ({
  mockUseQuestionnaire: vi.fn(() => ({ demande: undefined as unknown })),
}));

vi.mock("@context/demande/QuestionnaireProvider", () => ({
  QuestionnaireProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
  useQuestionnaire: mockUseQuestionnaire,
}));

// ─── Sous-composants ──────────────────────────────────────────────────────────

vi.mock("@controls/Spinner/Spinner", () => ({
  default: () => <div role="status" aria-label="Chargement en cours" />,
}));

vi.mock("@controls/Demande/ValidationCharte", () => ({
  ValidationCharte: () => null,
}));

vi.mock("@controls/Demande/EtatDescription", () => ({
  EtatDescription: () => <p>Description de l'état de la demande</p>,
}));

vi.mock("@controls/Avatars/DerniereModifDemandeLabel", () => ({
  DerniereModifDemandeLabel: () => null,
}));

vi.mock("@controls/Items/ProfilItem", () => ({
  ProfilItem: () => null,
}));

vi.mock("antd/es/grid/hooks/useBreakpoint", () => ({
  default: () => ({ lg: true, md: true }),
}));

// ─── Y17 : état nominal (demande non encore chargée) ─────────────────────────

describe("DemandeAvancement — accessibilité, demande en cours de chargement (Y17)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockParams = { id: "42" };
    mockUseQuestionnaire.mockReturnValue({ demande: undefined });
  });

  it("aucune violation axe-core", async () => {
    const { container } = renderWithProviders(<DemandeAvancement />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it("la page contient un titre h1", () => {
    renderWithProviders(<DemandeAvancement />);
    expect(screen.getByRole("heading", { level: 1 })).toBeInTheDocument();
  });

  it("le bouton 'Retour à la liste des demandes' a un nom accessible", () => {
    renderWithProviders(<DemandeAvancement />);
    expect(
      screen.getByRole("button", { name: /retour à la liste des demandes/i }),
    ).toBeInTheDocument();
  });
});

// ─── Y17/Y21 : avec données réelles ──────────────────────────────────────────

describe("DemandeAvancement — accessibilité avec données réelles (Y17/Y21)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockParams = { id: "42" };
    mockUseQuestionnaire.mockReturnValue({
      demande: makeDemande({ id: 42, etat: "/etats_demandes/1" }),
    });
  });

  it("aucune violation axe-core (demande chargée)", async () => {
    const { container } = renderWithProviders(<DemandeAvancement />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it("le fil d'avancement doublé d'un texte sr-only est présent", () => {
    renderWithProviders(<DemandeAvancement />);
    // AvancementDemande rend un div.sr-only décrivant les étapes pour les AT
    const srOnlyEl = document.querySelector(".sr-only");
    expect(srOnlyEl).toBeInTheDocument();
    expect(srOnlyEl?.textContent).toMatch(/avancement de votre demande/i);
  });

  it("le stepper visuel est masqué aux technologies d'assistance", () => {
    renderWithProviders(<DemandeAvancement />);
    // Steps Ant Design est aria-hidden pour éviter la redondance avec le sr-only
    const stepsEl = document.querySelector(".ant-steps");
    expect(stepsEl?.getAttribute("aria-hidden")).toBe("true");
  });

  it("le bouton 'Retour à la liste des demandes' reste accessible avec les données", () => {
    renderWithProviders(<DemandeAvancement />);
    expect(
      screen.getByRole("button", { name: /retour à la liste des demandes/i }),
    ).toBeInTheDocument();
  });
});

// ─── Y22 : état d'erreur réseau ───────────────────────────────────────────────
// Note : DemandeAvancement n'a pas d'Alert inline en cas d'erreur API.
// Si la demande ne peut être chargée, le composant reste en état "demande undefined"
// (spinner ou contenu vide). Les erreurs sont gérées via notification.error global.

describe("DemandeAvancement — état d'erreur réseau (Y22)", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockParams = { id: "42" };
    // Simule l'état post-erreur : demande non chargée
    mockUseQuestionnaire.mockReturnValue({ demande: undefined });
  });

  it("aucune violation axe-core en état d'erreur (demande non chargée)", async () => {
    const { container } = renderWithProviders(<DemandeAvancement />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it("la structure de page reste valide (h1 présent) en état d'erreur", () => {
    renderWithProviders(<DemandeAvancement />);
    expect(screen.getByRole("heading", { level: 1 })).toBeInTheDocument();
  });

  it("le bouton 'Retour à la liste des demandes' reste accessible en état d'erreur", () => {
    renderWithProviders(<DemandeAvancement />);
    expect(
      screen.getByRole("button", { name: /retour à la liste des demandes/i }),
    ).toBeInTheDocument();
  });
});
