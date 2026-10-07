/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Button } from "antd";
import { HomeOutlined } from "@ant-design/icons";
import { useNavigate } from "react-router";
import PageTitle from "@utils/PageTitle/PageTitle";
import styles from "./NotFound.module.scss";

/**
 * Page d'erreur 404 — ressource introuvable.
 */
export default function NotFound() {
  const navigate = useNavigate();

  return (
    <div className={styles.page}>
      <PageTitle />

      <div className={styles.backdrop} aria-hidden="true">
        <span className={styles.backdropNumber}>404</span>
      </div>

      <main className={styles.card} role="main" style={{ minHeight: 0 }}>
        <span className={styles.accent}>Erreur 404</span>
        <h1 className={styles.title}>Page introuvable</h1>
        <p className={styles.description}>
          La page que vous recherchez n'existe pas ou a été déplacée. Vérifiez l'adresse saisie ou
          revenez à l'accueil.
        </p>
        <div className={styles.actions}>
          <Button size="large" onClick={() => navigate(-1)}>
            Page précédente
          </Button>
          <Button type="primary" size="large" icon={<HomeOutlined />} onClick={() => navigate("/")}>
            Retour à l'accueil
          </Button>
        </div>
      </main>
    </div>
  );
}
