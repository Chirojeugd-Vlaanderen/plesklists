<?php

/**
 * Singleton class with helper methods for the plesklists extension.
 *
 * TODO: This class needs some refactoring.
 * TODO: I have no clue why I made this a singleton.
 */
class CRM_Plesklists_Helper {

  private static $instance;
  private $list_access;

  private function __construct() {
    // We could use dependency injection for this at some point in the
    // future:
    $this->list_access = new CRM_Plesklists_ListAccess();
  }

  public static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Returns an array with an element for each CiviCRM group connected to
   * a plesk mailing list.
   *
   * @return array
   *
   * The result is an array, mapping ID's of CiviCRM groups to plesk list names.
   * List names with an invalid format are ignored.
   */
  public function getListGroups() {
    $custom_field_id = CRM_Core_BAO_Setting::getItem('plesklists', 'custom_field_id');
    $api_result = civicrm_api3('Group', 'get', array(
      'sequential' => 1,
      'return' => "id,custom_$custom_field_id",
      "custom_$custom_field_id" => array('IS NOT NULL' => 1),
    ));

    // As long as CRM-16036 is not fixed, the API call above will return all
    // CiviCRM groups, even those where the custom field is not set.
    // But the 'isValidListName' check below will ignore empty strings
    // anyway, so this is not really a problem.

    $result = array();

    foreach ($api_result['values'] as $val) {
      if ($this->isValidListName($val["custom_$custom_field_id"])) {
        $result[$val['id']] = $val["custom_$custom_field_id"];
      }
    }

    return $result;
  }

  /**
   * Applies filters from API $params to the collection of $lists.
   *
   * @param array $lists
   *    Lists as returned by our custom API.
   * @param array $params
   *    Params from the API request.
   *
   * @return array
   *    The result of applying the filters in $params to $lists.
   *
   * Only a very limited subset of the default API filters is supported ATM.
   */
  public function filterLists($lists, $params) {
    $list_keys = array(
      "id" => 1,
      "name" => 1,
      "group_id" => 1,
    );
    $filters = array_intersect_key($params, $list_keys);

    $result = array();
    foreach ($lists as $list_id => $list) {
      $ok = TRUE;
      foreach ($filters as $key => $value) {
        // At the moment we only support filters of the form
        // KEY = VALUE.
        // TODO: implement other operations
        if ($list[$key] != $value) {
          $ok = FALSE;
        }
      }
      if ($ok) {
        $result[$list_id] = $list;
      }
    }

    return $result;
  }

  /**
   * Checks whether $name is a valid list name.
   *
   * @param string $name
   * @return bool
   */
  public function isValidListName($name) {
    // Just guessing the format of a mailman list name.
    $pattern = '/^[A-Za-z0-9._%+-]+$/';

    return preg_match($pattern, $name);
  }

  /**
   * Returns all e-mail addresses of contacts in a group.
   *
   * Ignores e-mail addresses where 'is_opt_out' is set.
   *
   * @param string $group_id
   * @return array
   */
  public function getGroupEmails($group_id) {
    $api_result = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'return' => 'email,is_opt_out',
      'group' => $group_id,
    ));

    $result = array();

    foreach ($api_result['values'] as $val) {
      if ($val['is_opt_out'] == 0) {
        $result[] = $val['email'];
      }
    }

    return $result;
  }
  
  // The functions below are more or less proxies to the list_access
  // object. This is intended. By providing another list_access, we could
  // (at some point in the future) use this module for accessing other
  // systems, like e.g. the mailman 3 API.

  /**
   * Returns an array containing all lists on the plesk server, with their
   * corresponding CiviCRM Group ID (if any).
   */
  public function getLists() {
    $lists = $this->list_access->getLists();
    $list_groups = array_flip($this->getListGroups());

    foreach ($lists as $list_id => $list) {
      $name = $list['name'];
      if (isset($list_groups[$name])) {
        $list["group_id"] = $list_groups[$name];
        $lists[$list_id] = $list;
      }
    }
    return $lists;
  }

  /**
   * Creates a new mailing list with a given $name.
   *
   * @param string $name
   *    list name.
   * @param string $admin_email
   *    email address of list admin.
   * @param string $password
   *    password for list admin. The password cannot use special characters,
   *    because I don't know how to escape them for the API call.
   * @return array
   *    List-array, ready for CiviCRM use. :-)
   */
  public function createList($name, $admin_email, $password) {
    $id = $this->list_access->createList($name, $admin_email, $password);
    return array(
      'id' => $id,
      'name' => $name
    );
  }

  /**
   * Deletes the mailing list with the given $name.
   *
   * @param string $name
   *    list name.
   */
  public function deleteList($name) {
    $this->list_access->deleteList($name);
  }

  /**
   * Returns all e-mail addresses in a given list.
   *
   * @param string $list_name
   * @return array
   */
  public function getListEmails($list_name) {
    // Normally the format of $list_name has been checked before.
    // But to be sure, we'll send it through htmlspecialchars.
    $clean_list_name = htmlspecialchars($list_name);
    return $this->list_access->getListEmails($clean_list_name);
  }

  /**
   * Adds e-mail addresses to a Plesk mailing list.
   *
   * @param string $list_name
   * @param array $emails
   */
  public function addListEmails($list_name, $emails) {
    if (count($emails) == 0) {
      return;
    }
    // prevent script injection:
    $clean_list_name = htmlspecialchars($list_name);
    $clean_emails = array();

    foreach ($emails as $email) {
      // prevent script injection:
      $clean_emails[] = htmlspecialchars($email);
    }

    $this->list_access->addListEmails($clean_list_name, $clean_emails);
  }

  /**
   * Removes e-mail addresses to a Plesk mailing list.
   *
   * @param string $list_name
   * @param array $emails
   */
  public function removeListEmails($list_name, $emails) {
    if (count($emails) == 0) {
      return;
    }    
    // prevent script injection:
    $clean_list_name = htmlspecialchars($list_name);
    $clean_emails = array();

    foreach ($emails as $email) {
      // prevent script injection:
      $clean_emails[] = htmlspecialchars($email);
    }

    $this->list_access->removeListEmails($clean_list_name, $clean_emails);
  }

}
