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
  $all_lists = CRM_Plesklists_Helper::getInstance()->getListGroups();

  foreach ($all_lists as $group_id => $list_name) {
    $list_members = CRM_Plesklists_Helper::getInstance()->getListEmails($list_name);
    $group_members = CRM_Plesklists_Helper::getInstance()->getGroupEmails($group_id);
    // array_filter removes the empty e-mail addresses (of people without
    // any e-mail address), see #17.
    $to_be_added = array_filter(array_diff($group_members, $list_members));
    $to_be_deleted = array_diff($list_members, $group_members);
    CRM_Plesklists_Helper::getInstance()->addListEmails($list_name, $to_be_added);
    CRM_Plesklists_Helper::getInstance()->removeListEmails($list_name, $to_be_deleted);
  }

  return civicrm_api3_create_success(1, $params, "Plesklists", "sync");
}

