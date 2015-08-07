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
  // Use API to get the lists, then sync all returned lists.
  // The Plesklists API ignores 'return' at the moment, but let's
  // now it is there if someone adds return support in the future.
  $params['return'] = 'group_id,name';
  $get_result = civicrm_api3('Plesklists', 'get', $params);
  if ($get_result[is_error] > 0) {
    return $get_result;
  }

  foreach ($get_result['values'] as $list) {
    // $group_id can be null for lists on the server that are not connected
    // to a CiviCRM group. Those lists can be ignored.
    if (!isset($list['group_id'])) {
      contiue;
    }

    $group_id = $list['group_id'];
    $list_name = $list['name'];

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
