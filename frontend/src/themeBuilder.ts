import { theme, ThemeConfig } from "antd";
import { pSBC } from "@utils/colors";
import { IAccessibilite } from "@context/accessibilite/AccessibiliteContext";
import { colorsLight, colorsRegular, colorsXlight } from "@/styles/colors";
import {
  APP_ERROR_COLOR,
  APP_ERROR_LIGHT_COLOR,
  APP_PRIMARY_COLOR,
  APP_PRIMARY_CONTRAST_COLOR,
  APP_PRIMARY_LIGHT_COLOR,
  APP_SECONDARY_COLOR,
  APP_SECONDARY_CONTRAST_COLOR,
  APP_SECONDARY_LIGHT_COLOR,
  APP_SUCCESS_COLOR,
  APP_SUCCESS_LIGHT_COLOR,
  APP_WARNING_COLOR,
  APP_WARNING_LIGHT_COLOR,
} from "@/constants";

function colorVariant(override: string | null, base: string, coef: number): string {
  return override ?? (pSBC(coef, base) as string);
}

export function getPrimaryColor(isContrast: boolean, isDark: boolean = false): string {
  if (isContrast && isDark) {
    // En dark+contrast on veut une couleur PLUS CLAIRE pour contraster sur fond sombre
    // (l'override APP_PRIMARY_CONTRAST_COLOR est conçu pour fond blanc, pas fond sombre)
    return pSBC(0.45, APP_PRIMARY_COLOR) as string;
  }
  return isContrast
    ? colorVariant(APP_PRIMARY_CONTRAST_COLOR, APP_PRIMARY_COLOR, -0.5)
    : APP_PRIMARY_COLOR;
}

function hexToRgba(hex: string, alpha: number): string {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

// Couleurs des rôles : light en dark mode (meilleur contraste sur fond sombre), regular en light mode.
const roleColors = {
  intervenant: { key: "indigo" },
  beneficiaire: { key: "purple" },
  renfort: { key: "pink" },
  demandeur: { key: "amber" },
  danger: { key: "red" },
} as const;

/**
 * Retourne les CSS custom properties non-Ant à appliquer sur :root.
 * Couvre les couleurs de marque, les rôles et la palette Material Design.
 * Gère les deux modes (light / dark) et le mode contraste.
 */
export function buildCssVars(isContrast: boolean, mode: "light" | "dark"): Record<string, string> {
  const primary = getPrimaryColor(isContrast, mode === "dark");
  const appColor = isContrast
    ? colorVariant(APP_SECONDARY_CONTRAST_COLOR, APP_SECONDARY_COLOR, -0.25)
    : APP_SECONDARY_COLOR;
  const isDark = mode === "dark";

  const isDarkContrast = isDark && isContrast;

  // Palette Material Design : regular en light, light en dark, regular en dark+contrast (plus saturé)
  const palette = isDark && !isContrast ? colorsLight : colorsRegular;
  const paletteVars = Object.fromEntries(
    Object.entries(palette).map(([key, val]) => [`--color-${key}`, val]),
  );

  // Couleurs de rôles
  const rolePalette = isDark && !isContrast ? colorsLight : colorsRegular;
  const roleLightPalette = isDarkContrast ? colorsRegular : isDark ? colorsLight : colorsXlight;
  const roleVars = Object.fromEntries(
    Object.entries(roleColors).flatMap(([role, { key }]) => [
      [`--color-${role}`, rolePalette[key]],
      [
        `--color-${role}-light`,
        isDark
          ? hexToRgba(roleLightPalette[key], isDarkContrast ? 0.25 : 0.15)
          : roleLightPalette[key],
      ],
    ]),
  );

  return {
    // Sémantique dark mode : consommées par _dark.scss via var()
    ...(isDark && {
      "--blanc": isContrast ? "#ffffff" : "rgba(255, 255, 255, 0.9)",
      "--gris": isContrast ? "rgba(255, 255, 255, 0.65)" : "rgba(255, 255, 255, 0.5)",
      "--noir": isContrast ? "#000000" : "rgba(32, 32, 32, 0.85)",
      "--noir-mid": isContrast ? "rgba(0, 0, 0, 0.9)" : "rgba(32, 32, 32, 0.66)",
    }),
    "--color-primary": primary,
    // En dark+contrast, --color-primary-contrast pointe vers la couleur claire (comme --color-primary)
    "--color-primary-contrast": isDarkContrast
      ? primary
      : colorVariant(APP_PRIMARY_CONTRAST_COLOR, APP_PRIMARY_COLOR, -0.5),
    "--color-primary-light": colorVariant(APP_PRIMARY_LIGHT_COLOR, APP_PRIMARY_COLOR, 0.8),
    "--color-app": appColor,
    "--color-app-light": isContrast
      ? appColor
      : colorVariant(APP_SECONDARY_LIGHT_COLOR, APP_SECONDARY_COLOR, 0.8),
    "--color-app-contrast": colorVariant(APP_SECONDARY_CONTRAST_COLOR, APP_SECONDARY_COLOR, -0.5),
    "--color-error": APP_ERROR_COLOR,
    "--color-error-light": colorVariant(APP_ERROR_LIGHT_COLOR, APP_ERROR_COLOR, 0.8),
    "--color-warning": APP_WARNING_COLOR,
    "--color-warning-light": colorVariant(APP_WARNING_LIGHT_COLOR, APP_WARNING_COLOR, 0.8),
    "--color-success": APP_SUCCESS_COLOR,
    "--color-success-light": colorVariant(APP_SUCCESS_LIGHT_COLOR, APP_SUCCESS_COLOR, 0.8),
    ...paletteVars,
    ...roleVars,
  };
}

function getFontSize(a: IAccessibilite): number {
  if (a.policeLarge) return 20;
  if (a.dyslexieArial || a.dyslexieOpenDys) return 17;
  return 16;
}

function getFontFamily(a: IAccessibilite): string {
  if (a.dyslexieLexend) return "Lexend, sans-serif";
  if (a.dyslexieArial) return "Arial, Helvetica, sans-serif";
  if (a.dyslexieOpenDys) return "OpenDys, Arial, Helvetica, sans-serif";
  return "NoirPro, sans-serif";
}

function getFontWeight(a: IAccessibilite): number | undefined {
  if (a.dyslexieArial || a.dyslexieOpenDys || a.dyslexieLexend) return undefined;
  return 500;
}

export function buildTheme(mode: "light" | "dark", accessibilite: IAccessibilite): ThemeConfig {
  const { contrast } = accessibilite;
  const isDark = mode === "dark";
  return {
    algorithm: isDark ? theme.darkAlgorithm : theme.defaultAlgorithm,
    token: {
      colorPrimary: getPrimaryColor(contrast, isDark),
      colorInfo: getPrimaryColor(contrast, isDark),
      colorError: APP_ERROR_COLOR,
      colorSuccess: APP_SUCCESS_COLOR,
      colorWarning: APP_WARNING_COLOR,
      // Texte désactivé : contraste WCAG AA minimum (4.5:1) pour tous les modes
      colorTextDisabled: isDark ? "rgba(255, 255, 255, 0.55)" : "rgba(0, 0, 0, 0.55)",
      // Contraste light : texte noir forcé
      ...(contrast &&
        !isDark && {
          colorText: "#000000",
          colorTextDisabled: "#333",
        }),
      // Contraste dark : texte blanc pur + fonds très sombres + bordures plus marquées
      ...(contrast &&
        isDark && {
          colorText: "#ffffff",
          colorTextSecondary: "rgba(255, 255, 255, 0.85)",
          colorTextTertiary: "rgba(255, 255, 255, 0.7)",
          colorTextDescription: "rgba(255, 255, 255, 0.75)",
          colorBgContainer: "#0d0d0d",
          colorBgElevated: "#181818",
          colorBgLayout: "#000000",
          colorBgSpotlight: "#2a2a2a",
          colorBorder: "rgba(255, 255, 255, 0.55)",
          colorBorderSecondary: "rgba(255, 255, 255, 0.3)",
          fontWeightStrong: 600,
        }),
      // Dark mode (sans contrast) : fonds levés depuis le pur noir vers des gris sombres doux
      ...(isDark &&
        !contrast && {
          colorBgLayout: "#1a1c22",
          colorBgContainer: "#242830",
          colorBgElevated: "#2e3038",
          colorBgSpotlight: "#424650",
          colorTextDescription: "rgba(255, 255, 255, 0.6)",
        }),
      borderRadius: 7,
      opacityLoading: 0.75,
      fontSize: getFontSize(accessibilite),
      fontWeightStrong: getFontWeight(accessibilite),
      fontFamily: getFontFamily(accessibilite),
      linkDecoration:
        accessibilite.contrast || accessibilite.dyslexieArial || accessibilite.dyslexieOpenDys
          ? "underline"
          : "none",
    },
    components: {
      ...(isDark && {
        Button: { defaultColor: contrast ? "#ffffff" : "rgba(255, 255, 255, 0.9)" },
        Menu: {
          itemColor: contrast ? "#ffffff" : "rgba(255, 255, 255, 0.85)",
          itemSelectedColor: "#ffffff",
          horizontalItemSelectedColor: "#ffffff",
        },
      }),
    },
  };
}
