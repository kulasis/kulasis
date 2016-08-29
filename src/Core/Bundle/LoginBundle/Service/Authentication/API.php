<?php

namespace Kula\Core\Bundle\LoginBundle\Service\Authentication;

class API {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}
	
	public function authenticate($api_key, $host, $ip) {

    $app = $this->db->db_select('CORE_INTG_API_APPS', 'apps')
      ->fields('apps', array('INTG_API_APP_ID'))
      ->condition('HOST', $host)
      ->condition('IP_ADDRESS', $ip)
      ->condition('API_KEY', $api_key)
      ->execute()->fetch();

    if ($app['INTG_API_APP_ID'] != '') {
      return true;
    } else {
      return false;
    }
		
	}


}