/*
 * Copyright (c) 2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

/**
  -- ci-dessous un exemple qui récupère quelques informations complémentaires. /!\ cod_etu est obligatoire /!\
select cod_etu,
       cod_nne_ind || cod_cle_nne_ind                      as ine,
       lib_pr1_ind                                         as "Prénom d'usage",
       decode(cod_sex_eta_civ, 'M', 'Masculin', 'Féminin') as "Genre à l'état civil",
       lib_pr_eta_civ                                      as "Prénom à l'état civil"
from individu i
where cod_etu in (:codesEtudiants)
 */