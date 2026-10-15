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
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import CsvExportButtonWithoutFetch from "./CsvExportButtonWithoutFetch";
import CsvExportButtonWithFetch from "./CsvExportButtonWithFetch";

// Le vrai CSVLink déclenche un téléchargement : on le remplace par un lien dont le clic appelle onClick.
vi.mock("react-csv", async () => {
  const { useImperativeHandle } = await import("react");
  return {
    CSVLink: ({
      ref,
      onClick,
      children,
    }: {
      ref: React.Ref<unknown>;
      onClick: () => void;
      children: React.ReactNode;
    }) => {
      useImperativeHandle(ref, () => ({ link: { click: onClick } }));
      return <span data-testid="csv-link">{children}</span>;
    },
  };
});

let fetchTermine = false;
vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetFullCollection: () => ({
      data: fetchTermine ? { items: [] } : undefined,
      isError: false,
      isSuccess: fetchTermine,
      fetchedCount: 0,
      totalItems: 0,
    }),
  }),
}));

const boutons = {
  "sans fetch": (props: { onStart: () => void; onBeforeStart?: (d: () => void) => void }) => (
    <CsvExportButtonWithoutFetch
      getData={() => []}
      headers={[]}
      filename="test"
      ready={false}
      {...props}
    />
  ),
  "avec fetch": (props: { onStart: () => void; onBeforeStart?: (d: () => void) => void }) => (
    <CsvExportButtonWithFetch
      path="/beneficiaires"
      itemsPerPage={10}
      getData={() => []}
      headers={[]}
      filename="test"
      {...props}
    />
  ),
};

describe.each(Object.entries(boutons))("bouton CSV %s — onBeforeStart", (_nom, bouton) => {
  it("démarre directement sans onBeforeStart", async () => {
    const onStart = vi.fn();
    render(bouton({ onStart }));

    await userEvent.click(screen.getByRole("button", { name: /Exporter/ }));

    expect(onStart).toHaveBeenCalledOnce();
  });

  it("ne démarre pas tant que onBeforeStart n'a pas appelé demarrer", async () => {
    const onStart = vi.fn();
    const onBeforeStart = vi.fn();
    render(bouton({ onStart, onBeforeStart }));

    await userEvent.click(screen.getByRole("button", { name: /Exporter/ }));

    expect(onBeforeStart).toHaveBeenCalledOnce();
    expect(onStart).not.toHaveBeenCalled();
    expect(screen.getByRole("button", { name: /Exporter/ })).toBeInTheDocument();
  });

  it("démarre quand onBeforeStart appelle demarrer", async () => {
    const onStart = vi.fn();
    render(bouton({ onStart, onBeforeStart: (demarrer) => demarrer() }));

    await userEvent.click(screen.getByRole("button", { name: /Exporter/ }));

    expect(onStart).toHaveBeenCalledOnce();
  });
});

describe("bouton CSV sans fetch — après le téléchargement", () => {
  it("revient au bouton initial : un nouvel export repasse par onBeforeStart", async () => {
    const onBeforeStart = vi.fn((demarrer: () => void) => demarrer());
    const onDownloaded = vi.fn();
    render(
      <CsvExportButtonWithoutFetch
        getData={() => []}
        headers={[]}
        filename="test"
        onBeforeStart={onBeforeStart}
        onDownloaded={onDownloaded}
      />,
    );

    await userEvent.click(screen.getByRole("button", { name: /Exporter/ }));

    await waitFor(() => expect(onDownloaded).toHaveBeenCalledOnce());
    await waitFor(() => expect(screen.queryByTestId("csv-link")).not.toBeInTheDocument());

    await userEvent.click(screen.getByRole("button", { name: /Exporter/ }));

    expect(onBeforeStart).toHaveBeenCalledTimes(2);
    await waitFor(() => expect(onDownloaded).toHaveBeenCalledTimes(2));
  });
});

describe("bouton CSV avec fetch — après le téléchargement", () => {
  it("revient au bouton initial : un nouvel export repasse par onBeforeStart", async () => {
    fetchTermine = true;
    const onBeforeStart = vi.fn((demarrer: () => void) => demarrer());
    const onDownloaded = vi.fn();
    render(
      <CsvExportButtonWithFetch
        path="/beneficiaires"
        itemsPerPage={10}
        getData={() => []}
        headers={[]}
        filename="test"
        onBeforeStart={onBeforeStart}
        onDownloaded={onDownloaded}
      />,
    );

    await userEvent.click(screen.getByRole("button", { name: /Exporter/ }));

    await waitFor(() => expect(onDownloaded).toHaveBeenCalledOnce());
    await waitFor(() => expect(screen.queryByTestId("csv-link")).not.toBeInTheDocument());

    await userEvent.click(screen.getByRole("button", { name: /Exporter/ }));

    expect(onBeforeStart).toHaveBeenCalledTimes(2);
    await waitFor(() => expect(onDownloaded).toHaveBeenCalledTimes(2));
    fetchTermine = false;
  });
});
