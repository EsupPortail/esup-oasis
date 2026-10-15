/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { describe, expect, it } from "vitest";
import { composantesFromInscriptions } from "./Inscriptions";
import { IComposante, IInscription } from "@api";

describe("composantesFromInscriptions", () => {
  const composantes: IComposante[] = [
    { "@id": "/composantes/1", "@type": "Composante", libelle: "UFR Sciences" },
    { "@id": "/composantes/2", "@type": "Composante", libelle: "UFR Droit" },
  ];

  it("retourne un tableau vide si les inscriptions sont vides ou non définies", () => {
    expect(composantesFromInscriptions([], composantes)).toEqual([]);
    expect(
      composantesFromInscriptions(undefined as unknown as IInscription[], composantes),
    ).toEqual([]);
  });

  it("extrait les composantes correspondantes à partir des inscriptions", () => {
    const inscriptions: IInscription[] = [
      {
        "@id": "/inscriptions/10",
        formation: {
          "@id": "/formations/1",
          composante: "/composantes/1",
          libelle: "Licence Math",
        },
      } as IInscription,
      {
        "@id": "/inscriptions/20",
        formation: {
          "@id": "/formations/2",
          composante: "/composantes/2",
          libelle: "Master Droit",
        },
      } as IInscription,
    ];

    const result = composantesFromInscriptions(inscriptions, composantes);
    expect(result).toHaveLength(2);
    expect(result[0]?.libelle).toBe("UFR Sciences");
    expect(result[1]?.libelle).toBe("UFR Droit");
  });

  it("ignore les inscriptions sans formation ou avec composante inconnue", () => {
    const inscriptions: IInscription[] = [
      {
        "@id": "/inscriptions/10",
        formation: undefined,
      } as IInscription,
      {
        "@id": "/inscriptions/20",
        formation: { "@id": "/formations/99", composante: "/composantes/inconnue" },
      } as IInscription,
    ];

    const result = composantesFromInscriptions(inscriptions, composantes);
    expect(result).toEqual([]);
  });
});
