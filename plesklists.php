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
