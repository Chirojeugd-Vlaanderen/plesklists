<?php

/**
 * Plesklists.Configure API specification.
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_plesklists_Configure_spec(&$spec) {
  $spec['host']['api.required'] = 1;
  $spec['login']['api.required'] = 1;
  $spec['password']['api.required'] = 1;
}

/**
 * Plesklists.Configure API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_plesklists_Configure($params) {
    // Store form values in CiviCRM settings.
    CRM_Core_BAO_Setting::setItem($params['host'], 'plesklists', 'plesklist_host');
    CRM_Core_BAO_Setting::setItem($params['login'], 'plesklists', 'plesklist_login');
    CRM_Core_BAO_Setting::setItem($params['password'], 'plesklists', 'plesklist_password');
    
    return civicrm_api3_create_success(1, $params, "Plesklists", "configure");
}

