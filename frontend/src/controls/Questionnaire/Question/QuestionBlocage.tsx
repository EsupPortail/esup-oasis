/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { Alert, Form } from "antd";
import {
   QuestionnaireQuestion,
   useQuestionnaire,
} from "../../../context/demande/QuestionnaireProvider";
import { useEffect } from "react";
import { WarningFilled } from "@ant-design/icons";

export function QuestionBlocage(props: { question: QuestionnaireQuestion }) {
   const { setBlocage } = useQuestionnaire();

   useEffect(() => {
      setBlocage(true);
   }, [setBlocage]);

   // props.question.aide contient du HTML
   return (
      <>
         <Form.Item
            className="mb-0"
            rootClassName="question-item question-blocage"
            name={props.question["@id"]}
         >
            <Alert
               icon={<WarningFilled />}
               showIcon
               type="warning"
               message={props.question.libelle}
               description={
                  <span dangerouslySetInnerHTML={{ __html: props.question.aide as string }} />
               }
            />
         </Form.Item>
      </>
   );
}
