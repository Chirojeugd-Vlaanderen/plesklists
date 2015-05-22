<?php

/**
 * Plesklists.Create API
 *
 * @param array $params
 *    $params should include 'name', 'admin_email' and 'password'
 *    keys. 'group_id' is optional.
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_plesklists_Create($params) {
  $custom_field_id = CRM_Core_BAO_Setting::getItem('plesklists', 'custom_field_id');   
  
  $id = CRM_Plesklists_Helper::createList(
      $params['name'],
      $params['admin_email'],
      $params['password']);

  $result_value = array(
    'id' => $id,
    'name' => $params['name'],
  );

  if (isset($params["group_id"])) {
    civicrm_api3('Group', 'create', array(
      'id' => $params['group_id'],
      "custom_$custom_field_id" => $params['name'],
    ));
    $result_value["group_id"] = $params["group_id"];
  }
  
  return civicrm_api3_create_success($result_value, $params, 'Plesklists', 'create');
}

