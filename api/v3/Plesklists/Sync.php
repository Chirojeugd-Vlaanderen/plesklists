<?php

/**
 * Plesklists.sync API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_plesklists_sync($params) {
  $all_lists = CRM_Plesklists_Helper::getListGroups();

  foreach ($all_lists as $group_id => $list_name) {
    $list_members = CRM_Plesklists_Helper::getListEmails($list_name);
    $group_members = CRM_Plesklists_Helper::getGroupEmails($group_id);
    $to_be_added = array_diff($group_members, $list_members);
    $to_be_deleted = array_diff($list_members, $group_members);
    CRM_Plesklists_Helper::addListEmails($list_name, $to_be_added);
    CRM_Plesklists_Helper::removeListEmails($list_name, $to_be_deleted);
  }

  return civicrm_api3_create_success($response, $params, "Plesklists", "sync");
}

