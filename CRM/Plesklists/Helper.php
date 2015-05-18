<?php

/**
 * Helper methods for the plesklists extension.
 */
class CRM_Plesklists_Helper {
  /**
   * Returns all plesk lists.
   *
   * @return array
   *
   * The result is an array, mapping ID's of CiviCRM groups to plesk list names.
   */
  public static function getLists() {
    $custom_field_id = CRM_Core_BAO_Setting::getItem('plesklists', 'custom_field_id');
    $api_result = civicrm_api3('Group', 'get', array(
      'sequential' => 1,
      'return' => "id,custom_$custom_field_id",
      "custom_$custom_field_id" => array('IS NOT NULL' => 1),
    ));

    // This will only work if CRM-16036 is fixed. A patch exists, but it has
    // some security issues:
    // https://github.com/civicrm/civicrm-core/compare/master...johanv:CRM-16036-api_search_custom_fields_3rd_attempt

    $result = array();

    foreach ($api_result['values'] as $val) {
      $result[$val['id']] = $val["custom_$custom_field_id"];
    }

    return $result;
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
    $host = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');
    $login = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_login');
    $password = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_password');

    $client = new CRM_Plesklists_Client($host);
    $client->setCredentials($login, $password);


    $request = <<<EOF
<packet>
  <maillist>
    <get-members>
      <filter>
        <list-name>$list_name</list-name>
      </filter>
    </get-members>
  </maillist>
</packet>
EOF;

    $response = $client->request($request);

    // TODO: error handling

    $data = new SimpleXMLElement($response);
    $result = (array)($data->maillist->{'get-members'}->result->id);
    return($result);
  }

  /**
   * Adds e-mail addresses to a Plesk mailing list.
   *
   * @param string $list_name
   * @param array $emails
   */
  public static function addListEmails($list_name,$emails) {
    $host = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');
    $login = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_login');
    $password = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_password');

    $client = new CRM_Plesklists_Client($host);
    $client->setCredentials($login, $password);

    // create the request using clumsy text manipluation :-$
    
    $request = "
      <packet>
        <maillist>\n";

    // TODO: sanitize $list_name
    foreach ($emails as $email) {
      $request .= "
        <add-member>
          <filter>
            <list-name>$list_name</list-name>
          </filter>
          <id>$email</id>
        </add-member>\n";
    }

    $request .= "
        </maillist>
      </packet>\n";

    $response = $client->request($request);

    $data = new SimpleXMLElement($response);
    // TODO: error handling
  }

  /**
   * Removes e-mail addresses to a Plesk mailing list.
   *
   * @param string $list_name
   * @param array $emails
   */
  public static function removeListEmails($list_name,$emails) {
    $host = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');
    $login = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_login');
    $password = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_password');

    $client = new CRM_Plesklists_Client($host);
    $client->setCredentials($login, $password);

    // create the request using clumsy text manipluation :-$
    
    $request = "
      <packet>
        <maillist>\n";

    // TODO: sanitize $list_name
    foreach ($emails as $email) {
      $request .= "
        <del-member>
          <filter>
            <list-name>$list_name</list-name>
          </filter>
          <id>$email</id>
        </del-member>\n";
    }

    $request .= "
        </maillist>
      </packet>\n";

    $response = $client->request($request);

    $data = new SimpleXMLElement($response);
    // TODO: error handling
  }


}
