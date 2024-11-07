/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Form, Select, Space } from "antd";
import { MinusOutlined } from "@ant-design/icons";
import { QuestionAide } from "./QuestionAide";
import {
   QuestionnaireQuestion,
   useQuestionnaire,
} from "../../../context/demande/QuestionnaireProvider";
import { useApi } from "../../../context/api/ApiProvider";
import React from "react";

export function QuestionSelect(props: { question: QuestionnaireQuestion }) {
   const [submitting, setSubmitting] = React.useState<boolean>(false);
   const { questUtils, mode } = useQuestionnaire();
   const { data: questionApi, isFetching } = useApi().useGetItem({
      path: "/questions/{id}",
      url: props.question["@id"],
   });
   return (
      <>
         <Form.Item
            className="mb-0"
            rules={
               mode !== "preview" && props.question.obligatoire
                  ? [{ required: true, message: "La sélection est obligatoire" }]
                  : []
            }
            required={props.question.obligatoire}
            rootClassName="question-item"
            label={
               <Space className="question" direction="horizontal">
                  <MinusOutlined aria-hidden={true} />
                  <div>{props.question.libelle}</div>
               </Space>
            }
            name={props.question["@id"]}
         >
            <Select
               data-question={props.question["@id"]}
               data-type={props.question.typeReponse}
               disabled={mode === "preview"}
               loading={isFetching || submitting}
               mode={props.question.choixMultiple ? "multiple" : undefined}
               options={questionApi?.optionsReponses?.map((r) => ({
                  label: r.libelle,
                  value: r["@id"] as string,
               }))}
               onChange={(value) => {
                  setSubmitting(true);
                  questUtils?.envoyerReponse(
                     props.question["@id"] as string,
                     props.question.typeReponse as string,
                     value,
                     () => setSubmitting(false),
                  );
               }}
            />
         </Form.Item>
         <QuestionAide question={props.question} />
      </>
   );
}
