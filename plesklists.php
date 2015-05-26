<?php

require_once 'plesklists.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function plesklists_civicrm_config(&$config) {
  _plesklists_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function plesklists_civicrm_xmlMenu(&$files) {
  _plesklists_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function plesklists_civicrm_install() {
  _plesklists_civix_civicrm_install();

  // Create custom field on group
  $group_result = civicrm_api3('CustomGroup', 'create', array(
    'sequential' => 1,
    'name' => 'group_plesklist_customfields',
    'title' => 'Plesklists custom fields',
    'extends' => 'Group',
    'style' => 'Inline',
    'collapse_display' => 1,
    'is_active' => 1,
    'api.CustomField.create' => array(
      'custom_group_id' => '$value.id',
      'name' => 'group_plesklist_customfields_list',
      'label' => 'Plesk mailing list',
      'data_type' => 'String',
      'html_type' => 'Text',
      'weight' => 1,
      'is_active' => 1,
      'is_searchable' => 1,
    )));
  // I should probably handle errors here :-)

  $custom_field_id = $group_result['values'][0]['api.CustomField.create']['id'];
  CRM_Core_BAO_Setting::setItem($custom_field_id, 'plesklists', 'custom_field_id');
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function plesklists_civicrm_uninstall() {
  _plesklists_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function plesklists_civicrm_enable() {
  _plesklists_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function plesklists_civicrm_disable() {
  _plesklists_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function plesklists_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _plesklists_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function plesklists_civicrm_managed(&$entities) {
  _plesklists_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function plesklists_civicrm_caseTypes(&$caseTypes) {
  _plesklists_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function plesklists_civicrm_angularModules(&$angularModules) {
_plesklists_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function plesklists_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _plesklists_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_validateForm().
 */
function plesklists_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == 'CRM_Group_Form_Edit') {
    $custom_field_name = _plesklists_civicrm_pleskListFormInfo($fields);
    $list_name = $fields[$custom_field_name];
    $error = _plesklists_civicrm_validateListName(
        $list_name,
        $form->_entityId);

    if (isset($error)) {
      $errors[$custom_field_name] = $error;
    }
  }
}

/**
 * Check if $list_name can be used as Plesk list for CiviCRM group $group_id.
 *
 * @param string $list_name
 * @param string $group_id
 * @return string
 *    NULL if everything OK, otherwise a translated error message.
 */
function _plesklists_civicrm_validateListName($list_name, $group_id) {
    if (!isset($list_name) || trim($list_name) === '') {
      // empty name is always OK.
      return NULL;
    }

    if (!CRM_Plesklists_Helper::getInstance()->isValidListName($list_name)) {
      return ts('Invalid mailing list name.');
    }

    $result = civicrm_api3('Plesklists', 'get', array('name' => $list_name));
    if ($result['count'] == 0) {
      return ts(
          'List %1 does not exist on Plesk server.', array(1 => $list_name));
    }

    $existing_group_id = $result["values"][0]["group_id"];
    if (!empty($existing_group_id) && $existing_group_id != $group_id) {
      return ts(
          'Plesk mailing list %1 is already linked to group %2',
          array(1 => $list_name, 2 => $result["values"][0]["group_id"]));
    }

    return NULL;
}

/**
 * Find the field name containing the plesk list from the group form $fields.
 *
 * @param type $fields
 * @return string
 */
function _plesklists_civicrm_pleskListFormInfo($fields) {
    $custom_field_id = CRM_Core_BAO_Setting::getItem('plesklists', 'custom_field_id');
    // I am not sure how to know the name of the form field. I guess it is
    // the only one of which the name starts with custom_{$custom_field_id}.
    $custom_field_name = CRM_Utils_Array::first(
        preg_grep("/^custom_$custom_field_id/", array_keys($fields)));
    return $custom_field_name;
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function plesklists_civicrm_preProcess($formName, &$form) {

}

*/
