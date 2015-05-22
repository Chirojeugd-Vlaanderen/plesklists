<?php

/**
 * Helper methods for the plesklists extension.
 *
 * TODO: This class needs some refactoring.
 */
class CRM_Plesklists_Helper {
  /**
   * Returns an array with an element for each CiviCRM group connected to
   * a plesk mailing list.
   *
   * @return array
   *
   * The result is an array, mapping ID's of CiviCRM groups to plesk list names.
   * List names with an invalid format are ignored.
   */
  public static function getListGroups() {
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
      if (CRM_Plesklists_Helper::isValidListName($val["custom_$custom_field_id"])) {
        $result[$val['id']] = $val["custom_$custom_field_id"];
      }
    }

    return $result;
  }

  /**
   * Calls the plesk api.
   *
   * @param string $request xml-request to send to the api.
   * @returns the API result as string.
   */
  public static function pleskApi($request) {
    $host = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');
    $login = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_login');
    $password = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_password');

    $client = new CRM_Plesklists_Client($host);
    $client->setCredentials($login, $password);

    // TODO: Error handling.
    return $client->request($request);
  }

  /**
   * Returns an array containing all lists on the plesk server, with their
   * corresponding CiviCRM Group ID (if any).
   */
  public static function getLists() {
    $host = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');

    $request = <<<EOF
<packet>
  <maillist>
    <get-list>
      <filter>
        <site-name>$host</site-name>
      </filter>
    </get-list>
  </maillist>
</packet>
EOF;

    $response = CRM_Plesklists_Helper::pleskApi($request);
    return CRM_Plesklists_Helper::handleGetListResponse($response);
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
   * @return int
   *    ID of the mailing list on the Plesk server.
   */
  public static function createList($name, $admin_email, $password) {
    $host = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');
    $site_id = CRM_Plesklists_Helper::getSiteId($host);
    $clean_name = htmlspecialchars($name);
    $clean_admin_email = htmlspecialchars($admin_email);
    $clean_password = htmlspecialchars($password);
    $request = <<<EOF
<packet>
  <maillist>
    <add-list>
      <site-id>$site_id</site-id>
      <name>$clean_name</name>
      <password>$clean_password</password>
      <admin-email>$clean_admin_email</admin-email>
    </add-list>
  </maillist>
</packet>
EOF;

    $response = CRM_Plesklists_Helper::pleskApi($request);
    $data = new SimpleXMLElement($response);
    return CRM_Utils_Array::first((array)($data->maillist->{'add-list'}->result->id));
  }

  /**
   * Deletes the mailing list with the given $name.
   *
   * @param string $name
   *    list name.
   */
  public static function deleteList($name) {
    $clean_name = htmlspecialchars($name);
    $request = <<<EOF
<packet>
  <maillist>
    <del-list>
      <filter>
        <name>$clean_name</name>
      </filter>
    </del-list>
  </maillist>
</packet>
EOF;
    CRM_Plesklists_Helper::pleskApi($request);
  }

  /**
   * Returns the site-ID of the plesk site with given $site_name.
   * @param string $site_name
   *    Plesk site name.
   * @return int
   *    Site-ID.
   */
  public static function getSiteId($site_name)
  {
    $request = <<<EOF
<packet>
  <site>
    <get>
      <filter>
        <name>$site_name</name>
      </filter>
      <dataset>
        <gen_info/>
      </dataset>
    </get>
  </site>
</packet>
EOF;

    $response = CRM_Plesklists_Helper::pleskApi($request);
    $data = new SimpleXMLElement($response);
    return CRM_Utils_Array::first((array)($data->site->get->result->id));
  }

  /**
   * Handles the response of a get-list action on the Plesk API.
   *
   * @param type $response
   *
   * @return array
   *    an array containing the plesk list entities that our
   *    custom API returns.
   */
  private static function handleGetListResponse($response) {
    $result = array();
    $list_groups = array_flip(CRM_Plesklists_Helper::getListGroups());

    // I suspect that there are better ways to parse the response...
    $data = new SimpleXMLElement($response);
    $plesk_result = (array)($data->maillist->{'get-list'});

    foreach ($plesk_result["result"] as $list_object) {
      $id = CRM_Utils_Array::first((array)($list_object->id));
      $name = CRM_Utils_Array::first((array)($list_object->name));
      $list_array = array("id" => $id, "name" => $name);

      if (isset($list_groups[$name])) {
        $list_array["group_id"] = $list_groups[$name];
      }
      $result[$id] = $list_array;
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
  public static function filterLists($lists, $params) {
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
  public static function isValidListName($name) {
    // Just guessing the format of a mailman list name.
    $pattern='/^[A-Za-z0-9._%+-]+$/';

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
  public static function getGroupEmails($group_id) {
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

  /**
   * Returns all e-mail addresses in a given list.
   *
   * @param string $list_name
   * @return array
   */
  public static function getListEmails($list_name) {
    // Normally the format of $list_name has been checked before.
    // But to be sure, we'll send it through htmlspecialchars.
    $clean_list_name = htmlspecialchars($list_name);

    $request = <<<EOF
<packet>
  <maillist>
    <get-members>
      <filter>
        <list-name>$clean_list_name</list-name>
      </filter>
    </get-members>
  </maillist>
</packet>
EOF;

    $response = CRM_Plesklists_Helper::pleskApi($request);

    $data = new SimpleXMLElement($response);
    $result = (array)($data->maillist->{'get-members'}->result->id);
    return $result;
  }

  /**
   * Adds e-mail addresses to a Plesk mailing list.
   *
   * @param string $list_name
   * @param array $emails
   */
  public static function addListEmails($list_name,$emails) {
    // prevent script injection:
    $clean_list_name = htmlspecialchars($list_name);

    // create the request using clumsy text manipluation :-$
    $request = "
      <packet>
        <maillist>\n";

    foreach ($emails as $email) {
      // prevent script injection:
      $email = htmlspecialchars($email);

      $request .= "
        <add-member>
          <filter>
            <list-name>$clean_list_name</list-name>
          </filter>
          <id>$email</id>
        </add-member>\n";
    }

    $request .= "
        </maillist>
      </packet>\n";

    CRM_Plesklists_Helper::pleskApi($request);
  }

  /**
   * Removes e-mail addresses to a Plesk mailing list.
   *
   * @param string $list_name
   * @param array $emails
   */
  public static function removeListEmails($list_name,$emails) {
    // prevent script injection:
    $clean_list_name = htmlspecialchars($list_name);

    // create the request using clumsy text manipluation :-$
    $request = "
      <packet>
        <maillist>\n";

    foreach ($emails as $email) {
      // prevent script injection:
      $email = htmlspecialchars($email);

      $request .= "
        <del-member>
          <filter>
            <list-name>$clean_list_name</list-name>
          </filter>
          <id>$email</id>
        </del-member>\n";
    }

    $request .= "
        </maillist>
      </packet>\n";

    CRM_Plesklists_Helper::pleskApi($request);
  }

}
