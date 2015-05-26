<?php

/**
 * Plesklists.Get API.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_plesklists_Get($params) {
  $all_lists = CRM_Plesklists_Helper::getInstance()->getLists();
  $result = CRM_Plesklists_Helper::getInstance()->filterLists($all_lists, $params);
  return civicrm_api3_create_success($result, $params, 'Plesklists', 'get');
}

