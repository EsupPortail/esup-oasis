/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 *
 */

import { EyeOutlined, PlusOutlined } from "@ant-design/icons";
import { Button } from "antd";
import { ITypeDemande, QK_DEMANDES, QK_UTILISATEURS_DEMANDES } from "@api";
import { useApi } from "@context/api/ApiProvider";
import { useNavigate } from "react-router-dom";

export default function PostulerButton(props: { typeDemande: ITypeDemande; demandeurId: string }) {
  const navigate = useNavigate();
  const { data: demandesEnCours } = useApi().useGetFullCollection({
    path: "/demandes",
    query: {
      demandeur: props.demandeurId,
      format_simple: true,
    },
  });

  const mutationPostuler = useApi().usePost({
    path: "/demandes",
    invalidationQueryKeys: [QK_UTILISATEURS_DEMANDES, QK_DEMANDES],
    onSuccess: (data) => {
      navigate(`/demandes/${data.id}/saisie`);
    },
  });

  const demandeTrouvee = demandesEnCours?.items.find(
    (d) => d.campagne === props.typeDemande.campagneEnCours,
  );

  function deposerNouvelleDemande(item: ITypeDemande) {
    mutationPostuler.mutate({
      data: {
        typeDemande: item["@id"] as string,
        demandeur: props.demandeurId,
      },
    });
  }

  return demandeTrouvee ? (
    <Button
      icon={<EyeOutlined aria-hidden />}
      onClick={() => navigate(`/demandes/${demandeTrouvee?.id}`)}
    >
      Voir la demande
    </Button>
  ) : (
    props.typeDemande.campagneEnCours && (
      <Button
        loading={mutationPostuler.isPending}
        disabled={mutationPostuler.isPending}
        icon={<PlusOutlined aria-hidden />}
        onClick={() => deposerNouvelleDemande(props.typeDemande)}
        aria-label={`Déposer une demande : ${props.typeDemande.libelle}`}
      >
        Déposer une demande
      </Button>
    )
  );
}
