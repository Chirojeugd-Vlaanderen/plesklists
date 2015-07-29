<?php

/**
 * This class accesses the mailing list api.
 */
class CRM_Plesklists_ListAccess {
  /**
   * Calls the plesk api.
   *
   * @param string $request xml-request to send to the api.
   * @returns SimpleXMLElement the parsed API result.
   * @throws Exception
   * 
   * If the client is not properly configured, or if the API returns an
   * error, an exception will be thrown.
   */
  private function pleskApi($request) {
    $host = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');
    $login = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_login');
    $password = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_password');

    $client = new CRM_Plesklists_Client($host);
    $client->setCredentials($login, $password);

    return $client->request($request);
  }

  /**
   * Returns an array containing all lists on the plesk server, with their
   * corresponding CiviCRM Group ID (if any).
   */
  public function getLists() {
    $host = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');

    $request = <<<EOF
<packet>
  <maillist>
    <get-list>
      <filter><site-name>$host</site-name></filter>
    </get-list>
  </maillist>
</packet>
EOF;

    $data = $this->pleskApi($request);
    $result = array();

    // I suspect that there are better ways to parse the response...
    $plesk_result = (array)($data->maillist->{'get-list'});

    foreach ($plesk_result["result"] as $list_object) {
      $id = CRM_Utils_Array::first((array)($list_object->id));
      $name = CRM_Utils_Array::first((array)($list_object->name));
      $result[$id] = array("id" => $id, "name" => $name);
    }
    return $result;
  }

  /**
   * Returns the site-ID of the plesk site with given $site_name.
   * @param string $site_name
   *    Plesk site name.
   * @return int
   *    Site-ID.
   */
  private function getSiteId($site_name)
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

    $data = $this->pleskApi($request);
    return CRM_Utils_Array::first((array)($data->site->get->result->id));
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
  public function createList($name, $admin_email, $password) {
    $host = CRM_Core_BAO_Setting::getItem('plesklists', 'plesklist_host');
    $site_id = $this->getSiteId($host);
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

    $data = $this->pleskApi($request);
    return CRM_Utils_Array::first((array)($data->maillist->{'add-list'}->result->id));
  }

  /**
   * Deletes the mailing list with the given $name.
   *
   * @param string $name
   *    list name.
   */
  public function deleteList($name) {
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
    $this->pleskApi($request);
  }

  /**
   * Returns all e-mail addresses in a given list.
   *
   * @param string $list_name (sanitized)
   * @return array
   */
  public function getListEmails($list_name) {
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

    $data = $this->pleskApi($request);
    $result = (array)($data->maillist->{'get-members'}->result->id);
    return $result;
  }

  /**
   * Adds e-mail addresses to a Plesk mailing list.
   *
   * @param string $list_name
   * @param array $emails
   */
  public function addListEmails($list_name,$emails) {
    // create the request using clumsy text manipluation :-$
    $request = "
      <packet>
        <maillist>\n";

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

    $this->pleskApi($request);
  }

  /**
   * Removes e-mail addresses to a Plesk mailing list.
   *
   * @param string $list_name
   * @param array $emails
   */
  public function removeListEmails($list_name,$emails) {
    // create the request using clumsy text manipluation :-$
    $request = "
      <packet>
        <maillist>\n";

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

    $this->pleskApi($request);
  }
}
