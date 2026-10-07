import { screen } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { renderWithProviders } from "@/test";
import { Utilisateur } from "@lib";

// -- Mocks de dépendances lourdes --

vi.mock("@/auth/AuthProvider", () => ({
  useAuth: vi.fn(),
}));

vi.mock("@/auth/LoginPage", () => ({ default: () => <div data-testid="login-page" /> }));
vi.mock("@/auth/OAuthCallback", () => ({ default: () => <div data-testid="oauth-callback" /> }));
// AppLayout rend un <Outlet/> pour que les routes enfants (filtrées par rôle) s'affichent.
vi.mock("@controls/AppLayout/AppLayout", async () => {
  const { Outlet } = await import("react-router");
  return {
    default: () => (
      <div data-testid="app-layout">
        <Outlet />
      </div>
    ),
  };
});
vi.mock("@controls/Spinner/Spinner", () => ({ default: () => <div data-testid="spinner" /> }));

// Pages référencées dans les routes publiques (roles: null)
vi.mock("@routes/commun/Rgpd", () => ({ default: () => <div data-testid="page-rgpd" /> }));
vi.mock("@routes/commun/MentionsLegales", () => ({
  default: () => <div data-testid="page-credits" />,
}));
vi.mock("@routes/commun/Version", () => ({ default: () => <div data-testid="page-versions" /> }));
vi.mock("@routes/commun/NotFound", () => ({ default: () => <div data-testid="page-not-found" /> }));
vi.mock("@routes/commun/Impersonate", () => ({
  default: () => <div data-testid="page-impersonate" />,
}));

// Pages authentifiées (moquées en bloc — contenu non testé ici)
vi.mock("@routes/beneficiaire/BeneficiaireDashboard", () => ({
  default: () => <div data-testid="page-beneficiaire-dashboard" />,
}));
vi.mock("@routes/gestionnaire/dashboard/GestionnaireDashboard", () => ({
  default: () => <div data-testid="page-gestionnaire-dashboard" />,
}));
vi.mock("@routes/administration/Administration", () => ({
  default: () => <div data-testid="page-administration" />,
}));
vi.mock("@routes/demandeur/Demandes", () => ({
  default: () => <div data-testid="page-demandes" />,
}));
vi.mock("@routes/gestionnaire/beneficiaires/Beneficiaires", () => ({
  default: () => <div data-testid="page-beneficiaires" />,
}));
vi.mock("@routes/gestionnaire/beneficiaires/Amenagements", () => ({
  default: () => <div data-testid="page-amenagements" />,
}));
vi.mock("@routes/gestionnaire/intervenants/Intervenants", () => ({
  default: () => <div data-testid="page-intervenants" />,
}));
vi.mock("@routes/gestionnaire/interventions/ValidationInterventionsRenforts", () => ({
  default: () => <div />,
}));
vi.mock("@routes/gestionnaire/interventions/InterventionsForfait", () => ({
  default: () => <div />,
}));
vi.mock("@routes/gestionnaire/beneficiaires/Beneficiaire", () => ({ default: () => <div /> }));
vi.mock("@routes/gestionnaire/demandeurs/Demandeurs", () => ({
  default: () => <div data-testid="page-demandeurs" />,
}));
vi.mock("@routes/gestionnaire/demandeurs/Demande", () => ({ default: () => <div /> }));
vi.mock("@routes/gestionnaire/demandeurs/Demandeur", () => ({ default: () => <div /> }));
vi.mock("@routes/administration/Parametres/Parametres", () => ({ default: () => <div /> }));
vi.mock("@routes/administration/Bilans/Bilans", () => ({ default: () => <div /> }));
vi.mock("@routes/administration/Bilans/ServicesFaits/ServicesFaits", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/Bilans/BilanFinancier/BilanFinancier", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/Bilans/BilanActivites/BilanActivites", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/Utilisateurs/Utilisateurs", () => ({ default: () => <div /> }));
vi.mock("@routes/administration/Referentiel/SportifsHautNiveau/SportifsHautNiveau", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/Referentiel/Amenagements/Amenagements", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/Referentiel/ClubsSportifs/ClubsSportifs", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/Referentiel/Referents/Referents", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/Referentiel/Chartes/Chartes", () => ({ default: () => <div /> }));
vi.mock("@routes/administration/Referentiel/PeriodeRh/PeriodesRh", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/Referentiel/TypeEvenement/TypesEvenements", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/Referentiel/Profils/Profils", () => ({ default: () => <div /> }));
vi.mock("@routes/administration/Referentiel/Tags/CategoriesTags", () => ({
  default: () => <div />,
}));
vi.mock("@routes/administration/TypesDemandes/TypesDemandes", () => ({ default: () => <div /> }));
vi.mock("@routes/administration/Commissions/Commissions", () => ({ default: () => <div /> }));
vi.mock("@routes/administration/Referentiel/Referentiel", () => ({ default: () => <div /> }));
vi.mock("@routes/beneficiaire/BilanBeneficiaireIntervenant", () => ({ default: () => <div /> }));
vi.mock("@controls/Interventions/MesInterventions", () => ({ default: () => <div /> }));
vi.mock("@routes/demandeur/DemandeSaisie", () => ({ default: () => <div /> }));
vi.mock("@routes/demandeur/DemandeAvancement", () => ({ default: () => <div /> }));
vi.mock("@routes/intervenant/IntervenantDashboard", () => ({ default: () => <div /> }));
vi.mock("@routes/planning/Planning", () => ({ default: () => <div /> }));

import { useAuth } from "@/auth/AuthProvider";
import AppRouter from "./AppRouter";

// -- Helpers --

function makeAuth(user: Utilisateur | undefined) {
  return {
    user,
    loading: false,
    error: null,
    signOut: vi.fn(),
    authenticate: vi.fn(),
    setUser: vi.fn(),
    token: undefined,
    impersonate: undefined,
    setImpersonate: vi.fn(),
    removeImpersonate: vi.fn(),
  };
}

function makeUser(roles: string[]) {
  return new Utilisateur({
    "@id": "/utilisateurs/test",
    "@type": "Utilisateur",
    uid: "test@test.fr",
    roles: roles as never[],
  });
}

function renderAt(path: string, user: Utilisateur | undefined) {
  vi.mocked(useAuth).mockReturnValue(makeAuth(user));
  return renderWithProviders(<AppRouter />, { route: path });
}

// -- Tests --

describe("AppRouter — flux non-authentifié", () => {
  it("redirige vers LoginPage pour toute route protégée", async () => {
    renderAt("/beneficiaires", undefined);
    expect(await screen.findByTestId("login-page")).toBeInTheDocument();
  });

  it("redirige vers LoginPage pour la racine /", async () => {
    renderAt("/", undefined);
    expect(await screen.findByTestId("login-page")).toBeInTheDocument();
  });

  it("rend les routes publiques sans authentification", async () => {
    renderAt("/rgpd", undefined);
    expect(await screen.findByTestId("page-rgpd")).toBeInTheDocument();
  });

  it("rend /credits sans authentification", async () => {
    renderAt("/credits", undefined);
    expect(await screen.findByTestId("page-credits")).toBeInTheDocument();
  });

  it("rend /versions sans authentification", async () => {
    renderAt("/versions", undefined);
    expect(await screen.findByTestId("page-versions")).toBeInTheDocument();
  });

  it("renvoie LoginPage pour toute route inconnue", async () => {
    renderAt("/une-route-qui-nexiste-pas", undefined);
    expect(await screen.findByTestId("login-page")).toBeInTheDocument();
  });
});

describe("AppRouter — flux authentifié", () => {
  it("rend AppLayout pour un utilisateur connecté", () => {
    renderAt("/", makeUser(["ROLE_USER", "ROLE_GESTIONNAIRE"]));
    expect(screen.getByTestId("app-layout")).toBeInTheDocument();
  });

  it("ne rend pas LoginPage pour un utilisateur connecté", () => {
    renderAt("/beneficiaires", makeUser(["ROLE_USER", "ROLE_GESTIONNAIRE", "ROLE_PLANIFICATEUR"]));
    expect(screen.queryByTestId("login-page")).not.toBeInTheDocument();
  });
});

describe("AppRouter — contrôle d'accès par rôle (guards)", () => {
  it("rôle autorisé : rend la page (planificateur → /beneficiaires)", async () => {
    renderAt("/beneficiaires", makeUser(["ROLE_USER", "ROLE_PLANIFICATEUR"]));
    expect(await screen.findByTestId("page-beneficiaires")).toBeInTheDocument();
    expect(screen.queryByTestId("page-not-found")).not.toBeInTheDocument();
  });

  it("rôle non autorisé : la route gestionnaire tombe sur NotFound (demandeur → /beneficiaires)", async () => {
    renderAt("/beneficiaires", makeUser(["ROLE_USER", "ROLE_DEMANDEUR"]));
    expect(await screen.findByTestId("page-not-found")).toBeInTheDocument();
    expect(screen.queryByTestId("page-beneficiaires")).not.toBeInTheDocument();
  });

  it("autre rôle, autre périmètre : demandeur → /demandes rend bien sa page", async () => {
    renderAt("/demandes", makeUser(["ROLE_USER", "ROLE_DEMANDEUR"]));
    expect(await screen.findByTestId("page-demandes")).toBeInTheDocument();
  });

  it("route admin protégée : un gestionnaire non-admin est renvoyé sur NotFound (/impersonate/:uid)", async () => {
    renderAt("/impersonate/cible@test.fr", makeUser(["ROLE_USER", "ROLE_GESTIONNAIRE"]));
    expect(await screen.findByTestId("page-not-found")).toBeInTheDocument();
    expect(screen.queryByTestId("page-impersonate")).not.toBeInTheDocument();
  });

  it("route admin protégée : un admin y accède (/impersonate/:uid)", async () => {
    renderAt("/impersonate/cible@test.fr", makeUser(["ROLE_USER", "ROLE_ADMIN"]));
    expect(await screen.findByTestId("page-impersonate")).toBeInTheDocument();
  });
});
