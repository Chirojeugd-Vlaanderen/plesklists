<?php
/**
 * Plesklists.Create API
 *
 * @param array $params
 *    If you want to create a new list,
 *    $params should include 'name', 'admin_email' and 'password'
 *    keys, and 'group_id' is optional.
 *    For existing Plesk lists, only group_id can be updated.
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_plesklists_Create($params) {
  $custom_field_id = CRM_Core_BAO_Setting::getItem('plesklists', 'custom_field_id');
  $search_params = array();
  
  // First check whether the list already exists.
  if (isset($params['id'])) {
    $search_params['id'] = $params['id'];
  }
  else if (isset($params['name'])) {
    $search_params['name'] = $params['name'];
  }
  else if (isset($params['group_id'])) {
    $search_params['group_id'] = $params['group_id'];
  }
  else {
    throw API_Exception(ts('Please provide id, name or group_id.'));
  }  
  $result = civicrm_api3('Plesklists', 'get', $search_params);
  
  if ($result["count"] == 0) {
    // Create new list.
    $result_value = CRM_Plesklists_Helper::getInstance()->createList(
        $params['name'],
        $params['admin_email'],
        $params['password']);
  }
  else {
    $result_value = CRM_Utils_Array::first($result["values"]);
  }
  
  if (isset($params["group_id"])) {
    // If the list was already linked to another group, delete the old
    // link first.
    if (isset($result_value["group_id"])) {
      civicrm_api3('Group', 'create', array(
        'id' => $result_value['group_id'],
        "custom_$custom_field_id" => '',
      ));
    }
    civicrm_api3('Group', 'create', array(
      'id' => $params['group_id'],
      "custom_$custom_field_id" => $result_value['name'],
    ));
    $result_value["group_id"] = $params["group_id"];
  }

  return civicrm_api3_create_success(
      $result_value, 
      $params, 
      'Plesklists', 
      'create');
}