/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import {
  Alert,
  App,
  Button,
  Empty,
  Flex,
  Input,
  List,
  Modal,
  Popconfirm,
  Space,
  Tooltip,
} from "antd";
import {
  DeleteOutlined,
  FilterOutlined,
  SaveOutlined,
  StarFilled,
  StarOutlined,
} from "@ant-design/icons";
import React, { useState } from "react";
import FiltreDescription, { FiltreDecrivable } from "@controls/Table/FiltreDescription";
import { usePreferences } from "@context/utilisateurPreferences/UtilisateurPreferencesProvider";

import { UseStateDispatch } from "@utils/utils";

export function FiltresFavoris<T extends FiltreDecrivable>(props: {
  filtre: T;
  setFiltre: UseStateDispatch<T>;
  filtreType: string;
  defaultFilter: T;
}) {
  const { message } = App.useApp();
  const { getPreferenceArray, setPreferenceArray } = usePreferences();
  const [modalOpen, setModalOpen] = useState(false);
  const [nomFiltre, setNomFiltre] = useState("Nouveau filtre");

  function sauvegarderFiltre() {
    const hasSameName = getPreferenceArray(props.filtreType).some((f) => f.nom === nomFiltre);
    if (hasSameName) {
      message.error("Un filtre enregistré porte déjà ce nom");
      return;
    }
    setPreferenceArray(props.filtreType, [
      ...getPreferenceArray(props.filtreType),
      { filtre: { ...props.filtre, page: 1 }, nom: nomFiltre, favori: false },
    ]);
    message.success("Filtre enregistré");
    setModalOpen(false);
    setNomFiltre("Nouveau filtre");
  }

  return (
    <>
      <Alert
        type="info"
        className="mb-2"
        showIcon
        title="Enregistrement des filtres"
        description={
          <>
            Pour retrouver rapidement vos informations, vous pouvez enregistrer des filtres de
            recherche.
            <br />
            <br />
            Pour <b>sauvegarder un nouveau filtre</b>, utilisez les filtres ci-dessus puis cliquez
            sur le bouton "Enregistrer le filtre" une fois que vous avez défini vos critères. Vous
            pourrez alors l'appliquer lorsque vous en aurez besoin en cliquant sur le bouton
            "Filtrer".
            <br />
            Vous pouvez également définir un <b>filtre favori</b> en cliquant sur <StarOutlined />.
            Ce filtre sera appliqué par défaut lorsque vous vous connecterez à l'application.
            <br />
            <br />
            Ces filtres sont personnels, ils ne sont pas partagés avec les autres utilisateurs.
          </>
        }
      />
      <List
        size="small"
        header={
          <Flex justify="space-between" align="center">
            <span className="semi-bold">Filtres sauvegardés</span>
            <Tooltip title="Enregistrer le filtre courant">
              <Button
                type="primary"
                icon={<SaveOutlined aria-hidden />}
                onClick={() => setModalOpen(true)}
              >
                Enregistrer le filtre
              </Button>
            </Tooltip>
          </Flex>
        }
        bordered
      >
        {getPreferenceArray(props.filtreType)
          ?.sort((f1, f2) => f1.nom.localeCompare(f2.nom))
          .map((filtre) => (
            <List.Item
              key={filtre.nom}
              extra={
                <Space.Compact>
                  <Tooltip placement="left" title="Appliquer le filtre">
                    <Button
                      icon={<FilterOutlined />}
                      onClick={() => {
                        props.setFiltre({ ...filtre.filtre, page: 1 } as T);
                        message.info(`Filtre "${filtre.nom}" appliqué`).then();
                      }}
                    >
                      Filtrer
                    </Button>
                  </Tooltip>
                  <Tooltip
                    placement="top"
                    title={
                      filtre.favori ? "Retirer le filtre favori" : "Définir comme filtre favori"
                    }
                  >
                    <Button
                      icon={
                        filtre.favori ? <StarFilled className="text-app-dark" /> : <StarOutlined />
                      }
                      onClick={() => {
                        setPreferenceArray(
                          props.filtreType,
                          getPreferenceArray(props.filtreType).map((f) => {
                            // un seul filtre peut être favori
                            if (!f.favori && f.nom !== filtre.nom) {
                              return { ...f, favori: false };
                            }
                            return { ...f, favori: !f.favori };
                          }) || [],
                        );
                      }}
                    >
                      Favori
                    </Button>
                  </Tooltip>
                  <FiltreDescription filtre={filtre.filtre} as="modal" />
                  <Popconfirm
                    title={`Supprimer le filtre "${filtre.nom}" ?`}
                    onConfirm={() => {
                      setPreferenceArray(
                        props.filtreType,
                        getPreferenceArray(props.filtreType).filter((f) => f.nom !== filtre.nom) ||
                          [],
                      );
                      message.success("Filtre supprimé").then();
                    }}
                  >
                    <Tooltip title="Supprimer le filtre" placement="bottom">
                      <Button icon={<DeleteOutlined />} className="text-danger" />
                    </Tooltip>
                  </Popconfirm>
                </Space.Compact>
              }
            >
              <span className={filtre.favori ? "text-primary semi-bold" : undefined}>
                {filtre.nom}
              </span>
            </List.Item>
          ))}
        {getPreferenceArray(props.filtreType).length === 0 && (
          <List.Item>
            <Empty
              image={Empty.PRESENTED_IMAGE_SIMPLE}
              className="m-auto mt-1 mb-1"
              description="Aucun filtre enregistré"
            />
          </List.Item>
        )}
      </List>
      <Modal
        title="Enregistrer le filtre"
        open={modalOpen}
        onOk={sauvegarderFiltre}
        onCancel={() => {
          setModalOpen(false);
          setNomFiltre("Nouveau filtre");
        }}
        okText="Enregistrer"
        cancelText="Annuler"
      >
        <Input
          value={nomFiltre}
          onChange={(e) => setNomFiltre(e.target.value)}
          placeholder="Nom du filtre"
          onPressEnter={sauvegarderFiltre}
          autoFocus
        />
      </Modal>
    </>
  );
}
