/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useEffect } from "react";
import { useLocation } from "react-router";

/**
 * Scroll en haut de la page à chaque changement de route.
 *
 * @returns {null} Returns null.
 */
export default function ScrollToTop(): null {
  const { pathname, hash } = useLocation();
  useEffect(() => {
    if (hash) {
      try {
        const element = document.querySelector(hash);
        const y = (element?.getBoundingClientRect().top || 0) + window.scrollY - 100;
        window.scrollTo({ top: y, behavior: "smooth" });
      } catch {
        /* empty */
      }
    } else {
      window.scrollTo(0, 0);
    }
  }, [pathname, hash]);

  return null;
}
