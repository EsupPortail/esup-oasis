/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { App, Button, Form } from "antd";
import { QuestionAide } from "@controls/Questionnaire/Question/QuestionAide";
import { QuestionnaireQuestion, useQuestionnaire } from "@context/demande/QuestionnaireProvider";
import { useNavigate } from "react-router-dom";
import { queryClient } from "@/queryClient";
import { service } from "@lib";
import { QK_DEMANDES } from "@api";

export function QuestionSubmit(props: { question: QuestionnaireQuestion }) {
  const { notification } = App.useApp();
  const navigate = useNavigate();
  const { questionnaire, questUtils, submitting } = useQuestionnaire();
  return (
    <>
      <Form.Item
        className="mb-0 text-center"
        rootClassName="question-item"
        name={props.question["@id"]}
      >
        <Button
          size="large"
          type="primary"
          htmlType="button"
          loading={submitting}
          onClick={() => {
            if (questionnaire?.complete) {
              questUtils?.envoyerReponse(
                props.question["@id"] as string,
                props.question.typeReponse as string,
                "submit",
                () => {
                  queryClient.invalidateQueries({ queryKey: [QK_DEMANDES] }).then();
                  queryClient
                    .invalidateQueries({
                      queryKey: ["/utilisateurs/{uid}/demandes"],
                    })
                    .then();
                  navigate("/demandes");
                },
              );
            } else {
              notification.error({
                title: "Erreur",
                description:
                  "Certaines informations du questionnaire n'ont pas été complétées. Veuillez les renseigner avant de valider votre demande.",
              });
            }
          }}
        >
          Valider ma demande
        </Button>
      </Form.Item>
      <div className="legende">
        <p>
          <strong>Attention</strong> : Vous ne pourrez plus modifier votre demande une fois validée.
          Elle sera examinée par {service.defini}. Vous pourrez suivre son avancement dans la
          rubrique "Demandes".
        </p>
      </div>
      <QuestionAide question={props.question} />
    </>
  );
}
