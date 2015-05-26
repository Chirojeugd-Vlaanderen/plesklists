<?php

/**
 * Plesklists.Delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_plesklists_Delete($params) {
  // The CiviCRM API expects an id in the $params for a delete operation.
  // So we first have to look up the list name via the ID.
  
  $custom_field_id = CRM_Core_BAO_Setting::getItem('plesklists', 'custom_field_id');
  $custom_field_name = "custom_$custom_field_id";

  $civi_api_result = civicrm_api3('Plesklists', 'get', array(
    'id' => $params["id"],
    // W00t! We can do chaining ;-)  
    'api.Group.get' => array("return" => $custom_field_name),
  ));
  
  if ($civi_api_result["count"] > 0) {
    $plesk_list = CRM_Utils_Array::first($civi_api_result["values"]);
    foreach ($plesk_list['api.Group.get']['values'] as $group) {
      // There really should be no more than one group that's attached
      // to a plesk list. But you never know - therefore 'foreach'.
      civicrm_api3('Group', 'create', array(
        "id" => $group['id'],
        "$custom_field_name" => '',
      ));            
    }
    CRM_Plesklists_Helper::getInstance()->deleteList($plesk_list['name']);
  }
  
  return civicrm_api3_create_success(1, $params, 'Plesklists', 'delete');
}

