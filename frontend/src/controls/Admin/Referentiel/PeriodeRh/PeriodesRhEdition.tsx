/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { useApi } from "@context/api/ApiProvider";
import { createDateAsUTC } from "@utils/dates";
import dayjs, { Dayjs } from "dayjs";
import { Alert, Button, Card, DatePicker, Drawer, Space, Switch, Typography } from "antd";
import { SendOutlined, WarningFilled } from "@ant-design/icons";
import React, { ReactElement } from "react";

import { IPeriode, QK_PERIODES } from "@api";

interface PeriodesRhEditionProps {
  periode: IPeriode | undefined;
  setPeriode: (item: IPeriode | undefined) => void;
  onClose: () => void;
}

const { RangePicker } = DatePicker;

/**
 * Editing component for a PeriodeRhItem.
 *
 */
export function PeriodesRhEdition({
  periode,
  setPeriode,
  onClose,
}: PeriodesRhEditionProps): ReactElement {
  function handleClose() {
    setPeriode(undefined);
    onClose();
  }

  const mutationPost = useApi().usePost({
    path: "/periodes",
    invalidationQueryKeys: [QK_PERIODES],
    onSuccess: handleClose,
  });
  const mutationPatch = useApi().usePatch({
    path: `/periodes/{id}`,
    invalidationQueryKeys: [QK_PERIODES],
    onSuccess: handleClose,
  });

  function createOrUpdate() {
    if (!periode) return;

    const data = {
      ...periode,
      debut: createDateAsUTC(dayjs(periode.debut).startOf("day").toDate()).toISOString(),
      fin: createDateAsUTC(dayjs(periode.fin).endOf("day").toDate()).toISOString(),
      butoir: createDateAsUTC(dayjs(periode.butoir).toDate()).toISOString(),
    };

    if (periode["@id"] === undefined) {
      // Création
      mutationPost?.mutate({
        data,
      });
    } else {
      // Modification
      mutationPatch?.mutate({
        "@id": periode["@id"],
        data,
      });
    }
  }

  function setSelectedDayRange(value: { from: Dayjs | null; to: Dayjs | null }) {
    if (!value.from || !value.to) return;
    setPeriode({
      ...(periode as IPeriode),
      debut: createDateAsUTC(value.from.toDate()).toISOString(),
      fin: createDateAsUTC(value.to.toDate()).toISOString(),
    });
  }

  return (
    <Drawer
      open
      title={
        periode?.["@id"] ? "Éditer un élément du référentiel" : "Ajouter un élément au référentiel"
      }
      onClose={handleClose}
      size="large"
      className="bg-light-grey"
    >
      <Card
        title="Période RH"
        actions={[
          <Button
            disabled={!periode?.debut || !periode?.fin || !periode?.butoir}
            onClick={createOrUpdate}
          >
            Enregistrer
          </Button>,
        ]}
      >
        <Typography.Text strong>Période</Typography.Text>
        <br />
        <RangePicker
          format="DD/MM/YYYY"
          defaultValue={[
            periode?.debut ? dayjs(new Date(periode.debut)) : null,
            periode?.fin ? dayjs(new Date(periode.fin)) : null,
          ]}
          onCalendarChange={(dates) => setSelectedDayRange({ from: dates[0], to: dates[1] })}
        />
        <div className="mt-4">
          <Typography.Text strong>Date butoir</Typography.Text>
          <br />
          <DatePicker
            className="w-100"
            format="DD/MM/YYYY"
            value={periode?.butoir ? dayjs(periode.butoir) : null}
            onChange={(date) => {
              if (date) {
                setPeriode({
                  ...(periode as IPeriode),
                  butoir: createDateAsUTC(date?.toDate()).toISOString(),
                });
              }
            }}
          />
        </div>
        <div className="mt-4">
          <Typography.Text strong>Envoyé à la RH ?</Typography.Text>

          <Space orientation="vertical" className="mt-2">
            <Alert
              type="warning"
              icon={<WarningFilled />}
              showIcon
              title="Envoi des évènements à la RH"
              description="Les évènements contenus dans la période ne seront plus modifiables."
            />
            <Switch
              disabled={periode?.["@id"] === undefined}
              checked={periode?.envoyee}
              checkedChildren={<SendOutlined style={{ marginTop: 5 }} />}
              onChange={(value) => {
                setPeriode({
                  ...(periode as IPeriode),
                  envoyee: value,
                });
              }}
            />
          </Space>
        </div>
      </Card>
    </Drawer>
  );
}
