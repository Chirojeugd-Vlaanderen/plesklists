<?php

/**
 * Plesklists.Get API.
 *
 * For the moment this just returns all plesk lists with their
 * CiviCRM group IDs.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_plesklists_Get($params) {
  $all_lists = CRM_Plesklists_Helper::getListGroups();
  $returnValues = array();

  foreach ($all_lists as $group_id => $list_name) {
    $returnValues[$group_id] = array (
      'id' => $group_id,
      'plesk_list' => $list_name,
    );
  }

  return civicrm_api3_create_success($returnValues, $params, 'Plesklists', 'get');
}

