/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Card, Layout } from "antd";
import PageTitle from "@utils/PageTitle/PageTitle";
import { env } from "@/env";
import { mailtoHref } from "@utils/url";
import { useEffectiveTheme } from "@utils/theme/useEffectiveTheme";

export default function Rgpd() {
  const isDark = useEffectiveTheme() === "dark";

  return (
    <Layout style={{ minHeight: "100vh" }}>
      <PageTitle />
      <Layout.Content style={{ padding: 50 }}>
        <Card title={<h1>Traitement des données personnelles</h1>} className="mb-3">
          {env.REACT_APP_LOGO && (
            <img
              src={isDark && env.REACT_APP_LOGO_DARK ? env.REACT_APP_LOGO_DARK : env.REACT_APP_LOGO}
              alt="Logo"
              style={{ width: 200 }}
            />
          )}

          <h2>Service {env.REACT_APP_SERVICE}</h2>
          <p>
            Le Service {env.REACT_APP_SERVICE} de {env.REACT_APP_ETABLISSEMENT_ARTICLE} est
            responsable du traitement des données à caractère personnel vous concernant et
            collectées dans le cadre{" "}
            <b>
              du suivi et de l’accompagnement des étudiantes et des étudiants à besoins spécifiques
              pour la mise en place du plan d’accompagnement individuel et personnalisé
            </b>
            .
          </p>
          <h2>Base légale</h2>
          <p>
            Ce traitement relève de la mission de service public d’accueil des étudiant•es à besoins
            spécifiques dans l’enseignement supérieur et la recherche telle que référencée dans les
            articles du Code de l’éducation L611-4 (sport haut niveau et assimilé), L611-11
            (activités et engagement étudiant), L 123-4-1 (situation de handicap).
          </p>
          <h2>Données traitées</h2>
          <h3>Catégories de données traitées</h3>
          <ul>
            <li>l’identité (nom, prénoms, numéro étudiant) ;</li>
            <li>la typologie du besoin spécifique ;</li>
            <li>Les droits à aménagements spécifiques</li>
            <li>les aménagements proposés ;</li>
            <li>le domaine de formation et l’inscription pédagogique ;</li>
          </ul>
          <h3>Source des données</h3>
          <p>
            Les données traitées proviennent de la base de données du logiciel de gestion de
            scolarité ou sont recueillies directement auprès des usagers.
          </p>
          <h3>Caractère facultatif du transfert de données</h3>
          <p>
            Toute étudiante ou tout étudiant sollicitant un accompagnement par le Service{" "}
            {env.REACT_APP_SERVICE} engendre de fait des opérations de traitement des données. Ce
            traitement est obligatoire pour la gestion du processus d’accompagnement, son
            évaluation, ses bilans.
          </p>
          <h2>Personnes concernées</h2>
          <p>
            Ce traitement concerne les étudiant•es à besoins spécifiques accompagné•es par le
            Service
            {env.REACT_APP_SERVICE}.
          </p>
          <h2>Destinataires des données</h2>
          <ul>
            <li>
              Données individuelles : le ou la chargé•e d’accompagnement du Service{" "}
              {env.REACT_APP_SERVICE}
            </li>
            <li>
              Données anonymes pour traitement de bilan de service : les agents du Service{" "}
              {env.REACT_APP_SERVICE}
            </li>
          </ul>
          <h2>Vos droits sur les données vous concernant</h2>
          <p>
            Vous pouvez accéder et obtenir copie des données vous concernant, vous opposer au
            traitement de ces données, les faire rectifier. Vous disposez également d'un droit à la
            limitation du traitement de vos données.
          </p>
          <h3>Exercer ses droits</h3>
          <p>
            Le délégué à la protection des données (DPD) de l'université est votre interlocuteur
            pour toute demande d'exercice de vos droits sur ce traitement.
            {env.REACT_APP_ADRESSE_DPD && (
              <>
                <br />
                - Par courrier postal :<br />
                {/* `whiteSpace: pre-line` + remplacement des éventuels <br> : l'adresse
                    peut être configurée avec des retours à la ligne (\n) ou des balises HTML. */}
                <span style={{ whiteSpace: "pre-line" }}>
                  {env.REACT_APP_ADRESSE_DPD.replace(/<br\s*\/?>/gi, "\n")}
                </span>
              </>
            )}
            {env.REACT_APP_EMAIL_DPD && (
              <>
                <br />
                - Par messagerie :<br />
                <a href={mailtoHref(env.REACT_APP_EMAIL_DPD)}>{env.REACT_APP_EMAIL_DPD}</a>
              </>
            )}
          </p>
        </Card>
        <div className="text-center">
          <a href="/">Retour à l'accueil</a>
        </div>
      </Layout.Content>
    </Layout>
  );
}
