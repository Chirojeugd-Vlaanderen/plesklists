<?php

// Copyright 1999-2015. Parallels IP Holdings GmbH. All Rights Reserved.
//
// I got this code from
// https://github.com/plesk/api-examples/blob/master/php/PleskApiClient.php
//
// It can be used under the terms of the Apache License Version 2, see
// https://github.com/plesk/api-examples/blob/master/LICENSE

/**
 * Client for Plesk API-RPC
 */
class CRM_Plesklists_Client {

  private $_host;
  private $_port;
  private $_protocol;
  private $_login;
  private $_password;
  private $_secretKey;

  /**
   * Create client
   *
   * @param string $host
   * @param int $port
   * @param string $protocol
   */
  public function __construct($host, $port = 8443, $protocol = 'https') {
    $this->_host = $host;
    $this->_port = $port;
    $this->_protocol = $protocol;
  }

  /**
   * Setup credentials for authentication
   *
   * @param string $login
   * @param string $password
   */
  public function setCredentials($login, $password) {
    $this->_login = $login;
    $this->_password = $password;
  }

  /**
   * Define secret key for alternative authentication
   *
   * @param string $secretKey
   */
  public function setSecretKey($secretKey) {
    $this->_secretKey = $secretKey;
  }

  /**
   * Perform API request
   *
   * @param string $request
   * @return SimpleXMLElement the parsed result
   * @throws Exception
   * 
   * If the client is not configured, or if the result contains an error
   * message, an exception is thrown.
   */
  public function request($request) {
    if (empty($this->_login) || empty($this->_host) || empty($this->_password)) {
      throw new Exception('Access to the Plesk list server is not configured.');
    }
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, "$this->_protocol://$this->_host:$this->_port/enterprise/control/agent.php");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_getHeaders());
    curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

    $result = curl_exec($curl);
    curl_close($curl);

    if ($result === FALSE) {
      throw new Exception("No reply from Plesk API.");
    }
    $data = new SimpleXMLElement($result);
    if ($data->system->status == 'error') {
      throw new Exception($data->system->errtext);
    }

    return $data;
  }

  /**
   * Retrieve list of headers needed for request
   *
   * @return array
   */
  private function _getHeaders() {
    $headers = array(
      "Content-Type: text/xml",
      "HTTP_PRETTY_PRINT: TRUE",
    );

    if ($this->_secretKey) {
      $headers[] = "KEY: $this->_secretKey";
    }
    else {
      $headers[] = "HTTP_AUTH_LOGIN: $this->_login";
      $headers[] = "HTTP_AUTH_PASSWD: $this->_password";
    }

    return $headers;
  }

}
