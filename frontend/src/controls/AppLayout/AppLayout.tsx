/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement } from "react";
import { Button, Layout } from "antd";
import AllModals from "../Modals/AllModals";
import AllDrawers from "../Drawers/AllDrawers";
import { Outlet, useNavigate } from "react-router-dom";
import AppLayoutMenu from "./AppLayoutMenu";
import "./AppLayout.scss";
import { MinusOutlined } from "@ant-design/icons";
import { UtilisateurPreferencesProvider } from "../../context/utilisateurPreferences/UtilisateurPreferencesProvider";
import { env } from "../../env";

const { Header, Content, Footer } = Layout;

/**
 * AppLayout component.
 * This component is rendering the layout of the application.
 *
 * @returns {ReactElement} The rendered AppLayout component.
 */
export default function AppLayout(): ReactElement {
   const navigate = useNavigate();

   return (
      <UtilisateurPreferencesProvider>
         <Layout>
            <Header className="header hide-on-print">
               <nav role="navigation" className="header-skip-nav" aria-label="Navigation rapide">
                  <Button
                     type="link"
                     onClick={() => {
                        const main = document.getElementById("main");
                        main?.focus();
                        const y = (main?.getBoundingClientRect().top || 0) + window.scrollY - 100;
                        window.scrollTo({ top: y, behavior: "smooth" });
                     }}
                  >
                     Aller au contenu principal
                  </Button>
               </nav>
               <AppLayoutMenu />
            </Header>
            <Content className="main-card-content">
               <AllModals />
               <AllDrawers />
               <div id="main" className="main-content" tabIndex={0}>
                  <Outlet />
               </div>
            </Content>
            <Footer style={{ textAlign: "center" }}>
               {env.REACT_APP_TITRE} <MinusOutlined aria-hidden /> {env.REACT_APP_ETABLISSEMENT}{" "}
               <MinusOutlined aria-hidden /> <a href="/rgpd">Politique d'utilisation des données</a>{" "}
               <MinusOutlined aria-hidden />{" "}
               <Button type="link" className="m-0 p-0" onClick={() => navigate("/versions")}>
                  Version {env.REACT_APP_VERSION}
               </Button>
            </Footer>
         </Layout>
      </UtilisateurPreferencesProvider>
   );
}
