/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import React from "react";
import { Card, Collapse, Empty, Flex, theme, Timeline, Typography } from "antd";
import { StarFilled } from "@ant-design/icons";
import { IInscription } from "@api";
import { separerInscriptions } from "@lib/inscription";
import { getLibellePeriode } from "@utils/dates";
import { ComposanteItem } from "@controls/Items/ComposanteItem";

function PeriodeInscription({ inscription }: { inscription: IInscription }): React.ReactElement {
  const { token } = theme.useToken();

  return (
    <Typography.Text type="secondary" style={{ fontSize: token.fontSizeSM }}>
      {getLibellePeriode(inscription.debut, inscription.fin, "MMM")}
    </Typography.Text>
  );
}

interface InscriptionLigneProps {
  inscription: IInscription;
  /** Mise en avant : fond teinté et libellé en gras. */
  miseEnAvant?: boolean;
  /** Ajoute la mention « Principale » (étoile + texte). */
  principale?: boolean;
  /** Affiche la composante sous le libellé (inutile quand un en-tête de composante la précède). */
  afficherComposante?: boolean;
}

/**
 * Une formation et sa période. La mention « Principale » est écrite (et pas seulement colorée)
 * pour rester lisible quel que soit le thème (clair, sombre, contraste renforcé).
 */
function InscriptionLigne({
  inscription,
  miseEnAvant = false,
  principale = false,
  afficherComposante = false,
}: InscriptionLigneProps): React.ReactElement {
  const { token } = theme.useToken();

  return (
    <Flex
      vertical
      gap={token.marginXXS}
      style={{
        background: miseEnAvant ? token.colorWarningBg : undefined,
        paddingBlock: token.paddingSM,
        paddingInline: token.paddingMD,
      }}
    >
      <Typography.Text strong={miseEnAvant}>{inscription.formation?.libelle}</Typography.Text>
      {afficherComposante && <ComposanteItem composanteId={inscription.formation?.composante} />}
      <Flex wrap gap={token.marginXS} align="baseline">
        {principale && (
          <Typography.Text style={{ fontSize: token.fontSizeSM, fontWeight: 600 }}>
            <StarFilled aria-hidden style={{ color: token.colorWarningText }} /> Principale
          </Typography.Text>
        )}
        <PeriodeInscription inscription={inscription} />
      </Flex>
    </Flex>
  );
}

interface InscriptionsDetailProps {
  inscriptions?: IInscription[];
}

/**
 * Détail des inscriptions d'un utilisateur :
 * - les inscriptions en cours, une carte par composante, la principale (si plusieurs) ou l'inscription unique mise en avant ;
 * - l'historique des inscriptions terminées, replié, en frise par année universitaire.
 */
export function InscriptionsDetail({ inscriptions }: InscriptionsDetailProps): React.ReactElement {
  const { token } = theme.useToken();
  const { enCours, passees } = separerInscriptions(inscriptions);

  const nbEnCours = enCours.reduce((total, groupe) => total + groupe.inscriptions.length, 0);
  const nbPassees = passees.reduce((total, groupe) => total + groupe.inscriptions.length, 0);
  const marquerPrincipale = nbEnCours > 1;

  if (nbEnCours + nbPassees === 0) {
    return <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Aucune inscription" />;
  }

  return (
    <Flex vertical gap={token.marginLG} style={{ width: "100%" }}>
      {nbEnCours === 0 ? (
        <Typography.Text type="secondary">Aucune inscription en cours</Typography.Text>
      ) : (
        enCours.map((groupe) => (
          // Échelle de proximité : composante → carte (8) < groupe → groupe (24).
          <Flex key={groupe.composante} vertical gap={token.marginXS}>
            {groupe.composante && (
              <div>
                <ComposanteItem composanteId={groupe.composante} />
              </div>
            )}
            <Card size="small" style={{ overflow: "hidden" }} styles={{ body: { padding: 0 } }}>
              {groupe.inscriptions.map((inscription, index) => (
                <div
                  key={inscription["@id"]}
                  style={index > 0 ? { borderTop: `1px solid ${token.colorSplit}` } : undefined}
                >
                  <InscriptionLigne
                    inscription={inscription}
                    miseEnAvant={nbEnCours === 1 || (marquerPrincipale && inscription.principale)}
                    principale={marquerPrincipale && inscription.principale}
                  />
                </div>
              ))}
            </Card>
          </Flex>
        ))
      )}
      {nbPassees > 0 && (
        <Collapse
          ghost
          size="small"
          styles={{ header: { paddingInline: 0 }, body: { paddingInline: 0 } }}
          items={[
            {
              key: "historique",
              label: `Historique (${nbPassees})`,
              children: (
                <Timeline
                  items={passees.map((groupe) => ({
                    key: groupe.annee,
                    color: token.colorTextTertiary,
                    content: (
                      // Échelle de proximité : ligne (4) < formation (12) < année (24).
                      <Flex vertical gap={token.marginXS} style={{ paddingBottom: token.marginSM }}>
                        <Typography.Text strong>{groupe.annee}</Typography.Text>
                        <Flex vertical gap={token.marginSM}>
                          {groupe.inscriptions.map((inscription) => (
                            <Flex key={inscription["@id"]} vertical gap={token.marginXXS}>
                              <Typography.Text>{inscription.formation?.libelle}</Typography.Text>
                              <Flex wrap align="center" gap={token.marginXS}>
                                <ComposanteItem composanteId={inscription.formation?.composante} />
                                <PeriodeInscription inscription={inscription} />
                              </Flex>
                            </Flex>
                          ))}
                        </Flex>
                      </Flex>
                    ),
                  }))}
                />
              ),
            },
          ]}
        />
      )}
    </Flex>
  );
}
