import { Alert } from "antd";
import React from "react";

export function TimezoneAlert() {
   const fuseauHoraire = Intl.DateTimeFormat().resolvedOptions().timeZone;

   return (
      fuseauHoraire !== "Europe/Paris" && (
         <Alert
            type="warning"
            message="Changement de fuseau horaire détecté"
            description={
               <>
                  Votre navigateur est configuré pour afficher les évènements selon votre fuseau
                  horaire actuel ({fuseauHoraire}).
                  <br />
                  Les événements se déroulent à l'heure de Paris.
               </>
            }
            showIcon
            className="mb-2"
         />
      )
   );
}
