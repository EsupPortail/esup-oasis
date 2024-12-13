/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import React, { ReactElement, useEffect, useState } from "react";
import { Avatar, Image, Tooltip } from "antd";
import { useAuth } from "../../auth/AuthProvider";
import { UserOutlined } from "@ant-design/icons";
import { useQuery } from "@tanstack/react-query";
import { APIEndpointByRole, IUtilisateurBase, RoleValues } from "../../lib/Utilisateur";
import { useApi } from "../../context/api/ApiProvider";
import useBreakpoint from "antd/es/grid/hooks/useBreakpoint";
import { ScreenMap } from "antd/es/_util/responsiveObserver";
import { useInView } from "react-intersection-observer";
import { getRoleClassName } from "./UtilisateurAvatar";
import { env } from "../../env";

/**
 * Avatar/Photo de profil d'un utilisateur.
 * @param props
 * @constructor
 */
export default function UtilisateurAvatarImage(props: {
   utilisateur?: IUtilisateurBase;
   utilisateurId?: string;

   role?: RoleValues;
   inverserNomPrenom?: boolean;

   width?: number;
   height?: number;
   size?: "large" | "small" | "default" | number;
   className?: string;
   style?: React.CSSProperties;
   responsive?: string;

   as?: "img" | "avatar";
   fallback?: ReactElement;
   desactiverLazyLoading?: boolean;
}): ReactElement {
   const auth = useAuth();
   const screens = useBreakpoint();

   const [utilisateurData, setUtilisateurData] = useState<IUtilisateurBase | undefined>(
      props.utilisateur,
   );

   const { data, isFetching } = useApi().useGetItem({
      path: props.role ? APIEndpointByRole(props.role) : "/utilisateurs/{uid}",
      url: props.utilisateurId as string,
      enabled: props.utilisateurId !== undefined,
   });

   const { ref, inView } = useInView();

   useEffect(() => {
      if (props.utilisateur) setUtilisateurData(props.utilisateur);
   }, [props.utilisateur]);

   useEffect(() => {
      if (data) setUtilisateurData(data);
   }, [data]);

   const photo = useQuery({
      queryKey: ["photos", `${utilisateurData?.["@id"]}/photo`],
      queryFn: async () => {
         let fetchOptions: RequestInit = {
            method: "GET",
            credentials: "include",
            cache: "force-cache",
            headers: {
               Accept: "image/jpeg",
            },
         };

         if (auth?.impersonate) {
            fetchOptions = {
               ...fetchOptions,
               headers: {
                  ...fetchOptions.headers,
                  "X-Switch-User": auth.impersonate,
               },
            };
         }

         if (env.REACT_APP_ENVIRONMENT === "local" || env.REACT_APP_PHOTO_DEMO === "true") {
            // utilise l'uid de l'utilisateur pour générer un chiffre entre 1 et 9
            const random = ((utilisateurData?.uid || "@").charCodeAt(0) % 9) + 1;
            return fetch(`/images/demo-faces/Teamwork-${random}.svg`)
               .then((response) => response.blob())
               .then((blob) => {
                  if (blob.type !== "application/ld+json") {
                     return window.URL.createObjectURL(blob);
                  } else {
                     return null;
                  }
               })
               .catch(() => {
                  return null;
               });
         }

         return fetch(`${env.REACT_APP_API}${utilisateurData?.["@id"]}/photo`, fetchOptions)
            .then((response) => {
               if (response.status === 200) {
                  return response.blob();
               }
               return null;
            })
            .then((blob) => {
               if (blob) {
                  // image renvoyée
                  return window.URL.createObjectURL(blob);
               } else {
                  return null;
               }
            })
            .catch(() => {
               return null;
            });
      },
      enabled:
         (props.desactiverLazyLoading || inView) &&
         env.REACT_APP_PHOTO === "true" &&
         (auth.user?.isGestionnaire || auth.user?.isCommissionMembre) &&
         !!utilisateurData?.["@id"] &&
         props.as === "img",
   });

   if (isFetching || utilisateurData === undefined) {
      return (
         <Avatar
            style={props.style}
            shape="square"
            className={`${props.className} bg-primary-light text-primary semi-bold`}
            icon={
               (!props.responsive || screens[props.responsive as keyof ScreenMap]) && (
                  <UserOutlined aria-hidden />
               )
            }
            aria-hidden
         />
      );
   }

   if (props.as === "img") {
      if (photo.data)
         return (
            <Image
               alt={`Photo de ${utilisateurData.prenom} ${utilisateurData.nom}`}
               aria-hidden
               width={props.width}
               height={props.height}
               src={photo.data ?? ""}
               style={{ objectFit: "contain" }}
               className="border-radius"
               rootClassName={`${props.className} border-radius`}
            />
         );
   }

   function getContent() {
      if (!utilisateurData) return <></>;

      if (!photo.data && props.fallback) return props.fallback;

      return props.inverserNomPrenom
         ? `${utilisateurData.nom?.[0].toLocaleUpperCase()}${utilisateurData.prenom?.[0].toLocaleUpperCase()}`
         : `${utilisateurData.prenom?.[0].toLocaleUpperCase()}${utilisateurData.nom?.[0].toLocaleUpperCase()}`;
   }

   if (!props.responsive || screens[props.responsive as keyof ScreenMap]) {
      return (
         <Tooltip title={`${utilisateurData.prenom} ${utilisateurData.nom?.toLocaleUpperCase()}`}>
            <Avatar
               ref={ref}
               shape="square"
               src={photo.data ?? ""}
               size={props.size || props.height || "default"}
               style={props.style}
               data-testid={utilisateurData.nom}
               className={`cursor-default ${getRoleClassName(props.role)} ${props.className}`}
               aria-hidden
               alt=""
            >
               {getContent()}
            </Avatar>
         </Tooltip>
      );
   }

   return <></>;
}
