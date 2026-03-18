/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { IAmenagement, ITypeAmenagement } from "../../api/ApiTypeHelpers";
import {
   App,
   Button,
   Checkbox,
   Col,
   DatePicker,
   Divider,
   Flex,
   Form,
   Input,
   Modal,
   Popconfirm,
   Row,
   Select,
   Space,
} from "antd";
import { useApi } from "../../context/api/ApiProvider";
import React, { useEffect } from "react";
import dayjs, { Dayjs } from "dayjs";
import { PREFETCH_TYPES_SUIVI_AMENAGEMENTS } from "../../api/ApiPrefetchHelpers";
import { useAuth } from "../../auth/AuthProvider";
import { DeleteOutlined } from "@ant-design/icons";
import { DomaineAmenagementInfos } from "../../lib/amenagements";

type IAmenagementForm = IAmenagement & { dateDebut: Dayjs | null; dateFin: Dayjs | null };

export function ModalAmenagement(props: {
   amenagementId?: string;
   open: boolean;
   setOpen: (open: boolean) => void;
   typeAmenagementAjoute?: ITypeAmenagement;
   utilisateurId?: string;
   domaineAmenagement?: DomaineAmenagementInfos;
}) {
   const user = useAuth().user;
   const { message } = App.useApp();
   const [form] = Form.useForm<IAmenagementForm>();
   const { data: amenagement } = useApi().useGetItem({
      path: "/utilisateurs/{uid}/amenagements/{id}",
      url: props.amenagementId,
      enabled: !!props.amenagementId,
   });
   const { data: suivis } = useApi().useGetCollection(PREFETCH_TYPES_SUIVI_AMENAGEMENTS);

   const mutateDeleteAmenagement = useApi().useDelete({
      path: "/utilisateurs/{uid}/amenagements/{id}",
      invalidationQueryKeys: ["/utilisateurs/{uid}/amenagements", "/beneficiaires"],
      onSuccess: () => {
         props.setOpen(false);
         message.success("Aménagement supprimé").then();
      },
   });

   const mutatePostAmenagement = useApi().usePost({
      path: "/utilisateurs/{uid}/amenagements",
      url: `${props.utilisateurId}/amenagements`,
      onSuccess: () => {
         message.success("Aménagement ajouté").then();
         props.setOpen(false);
      },
      invalidationQueryKeys: [
         "/utilisateurs/{uid}/amenagements",
         props.utilisateurId ?? "/utilisateurs/{uid}",
         "/amenagements",
         "/beneficiaires",
      ],
   });
   const mutatePatchAmenagement = useApi().usePatch({
      path: "/utilisateurs/{uid}/amenagements/{id}",
      onSuccess: () => {
         message.success("Aménagement modifié").then();
         props.setOpen(false);
      },
      invalidationQueryKeys: [
         props.utilisateurId ?? "/utilisateurs/{uid}",
         "/utilisateurs/{uid}/amenagements",
         props.amenagementId as string,
         "/amenagements",
         "/beneficiaires",
      ],
   });

   function handleSubmit(values: IAmenagementForm) {
      const data = {
         typeAmenagement: props.typeAmenagementAjoute?.["@id"],
         semestre1: user?.isGestionnaire ? values.semestre1 : undefined,
         semestre2: user?.isGestionnaire ? values.semestre2 : undefined,
         debut: user?.isGestionnaire
            ? values.dateDebut
               ? values.dateDebut.format("YYYY-MM-DD")
               : null
            : undefined,
         fin: user?.isGestionnaire
            ? values.dateFin
               ? values.dateFin.format("YYYY-MM-DD")
               : null
            : undefined,
         commentaire: values.commentaire || null,
         suivi: values.suivi || null,
      } as IAmenagement;

      /*
      TODO: attente validation métier ; ajouter les required + traiter l'ajout par catégorie
      if (user?.isGestionnaire) {
         if (!values.semestre1 && !values.semestre2 && !values.dateDebut) {
            message.error("Une date de début ou un semestre doit être renseigné").then();
            return;
         }
         if (!values.semestre1 && !values.semestre2 && !values.dateFin) {
            message.error("Une date de fin ou un semestre doit être renseigné").then();
            return;
         }
      }*/

      if (props.amenagementId) {
         mutatePatchAmenagement.mutate({
            "@id": props.amenagementId,
            data,
         });
      } else {
         mutatePostAmenagement.mutate({
            data,
         });
      }
   }

   useEffect(() => {
      if (amenagement) {
         form.resetFields();
         form.setFieldsValue({
            ...amenagement,
            dateDebut: amenagement.debut ? dayjs(amenagement.debut) : undefined,
            dateFin: amenagement.fin ? dayjs(amenagement.fin) : undefined,
         });
      }
   }, [form, amenagement]);

   return (
      <Modal
         open={props.open}
         onCancel={() => props.setOpen(false)}
         onOk={() => {
            form.submit();
         }}
         okText={props.amenagementId ? "Valider" : "Ajouter"}
         cancelText="Annuler"
         title={props.amenagementId ? "Éditer un aménagement" : "Ajouter un aménagement"}
         width={700}
         footer={
            <Flex justify="space-between">
               <Popconfirm
                  okText="Oui, supprimer"
                  okButtonProps={{ danger: true }}
                  title="Supprimer cet aménagement ?"
                  onConfirm={() => {
                     mutateDeleteAmenagement.mutate({
                        "@id": props.amenagementId as string,
                     });
                  }}
               >
                  <Button
                     icon={<DeleteOutlined aria-hidden />}
                     danger
                     className={props.amenagementId ? "" : "v-hidden"}
                  >
                     Supprimer
                  </Button>
               </Popconfirm>
               <Space>
                  <Button onClick={() => props.setOpen(false)}>Annuler</Button>
                  <Button type="primary" onClick={() => form.submit()}>
                     {props.amenagementId ? "Valider" : "Ajouter"}
                  </Button>
               </Space>
            </Flex>
         }
      >
         <Form<IAmenagementForm>
            layout="vertical"
            form={form}
            onFinish={handleSubmit}
            initialValues={{
               dateDebut: dayjs(),
               ...amenagement,
            }}
         >
            <Row gutter={16}>
               <Col xs={24} sm={24} md={10}>
                  <Divider>Semestres</Divider>
                  <Row>
                     <Col span={24}>
                        <Form.Item name="semestre1" valuePropName="checked">
                           <Checkbox disabled={!user?.isGestionnaire}>Semestre 1</Checkbox>
                        </Form.Item>
                     </Col>
                     <Col span={24}>
                        <Form.Item name="semestre2" valuePropName="checked">
                           <Checkbox disabled={!user?.isGestionnaire}>Semestre 2</Checkbox>
                        </Form.Item>
                     </Col>
                  </Row>
               </Col>
               <Col xs={24} sm={24} md={14}>
                  <Divider>Période</Divider>
                  <Row>
                     <Col span={12}>
                        <Form.Item
                           name="dateDebut"
                           label="Date début"
                           rules={[
                              {
                                 validator: async (_, value) => {
                                    if (
                                       !value &&
                                       !form.getFieldValue("semestre1") &&
                                       !form.getFieldValue("semestre2")
                                    ) {
                                       return Promise.reject(
                                          "Une date de début ou un semestre doit être renseignée",
                                       );
                                    }
                                 },
                              },
                           ]}
                        >
                           <DatePicker
                              disabled={!user?.isGestionnaire}
                              className="text-center"
                              picker="date"
                              format="DD/MM/YYYY"
                           />
                        </Form.Item>
                     </Col>

                     <Col span={12}>
                        <Form.Item
                           name="dateFin"
                           label="Date fin"
                           rules={[
                              {
                                 validator: async (_, value) => {
                                    if (
                                       value &&
                                       form.getFieldValue("dateDebut") &&
                                       value.isBefore(form.getFieldValue("dateDebut"))
                                    ) {
                                       return Promise.reject(
                                          "La date de fin doit être postérieure à la date de début",
                                       );
                                    }
                                 },
                              },
                           ]}
                        >
                           <DatePicker
                              disabled={!user?.isGestionnaire}
                              className="text-center"
                              picker="date"
                              format="DD/MM/YYYY"
                           />
                        </Form.Item>
                     </Col>
                  </Row>
               </Col>
            </Row>
            <Divider>Commentaires</Divider>
            <Form.Item name="commentaire">
               <Input.TextArea autoSize style={{ minHeight: 100 }} />
            </Form.Item>
            <Divider>Suivi mise en place</Divider>
            <Form.Item name="suivi">
               <Select
                  allowClear
                  className="w-100"
                  options={(suivis?.items || []).map((s) => ({
                     value: s["@id"] as string,
                     label: s.libelle,
                  }))}
               />
            </Form.Item>
         </Form>
      </Modal>
   );
}
