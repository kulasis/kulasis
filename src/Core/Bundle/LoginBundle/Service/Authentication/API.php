<?php

namespace Kula\Core\Bundle\LoginBundle\Service\Authentication;

class API {

	private $db;

	public function __construct($db, $cache, $auth_local) {
		$this->db = $db;
    $this->cache = $cache;
    $this->auth_local = $auth_local;
	}

  public function authenticateApplication($app_id, $app_secret, $host, $ip) {

    $app = $this->db->db_select('CORE_INTG_API_APPS', 'apps')
      ->fields('apps', array('INTG_API_APP_ID'))
      ->condition('HOST', $host)
      ->condition('IP_ADDRESS', $ip)
      ->condition('APP_ID', $app_id)
      ->condition('APP_SECRET', $app_secret)
      ->execute()->fetch();

    if ($app['INTG_API_APP_ID'] != '') {
      // Successful login
      $token = bin2hex(openssl_random_pseudo_bytes(15));
      // Load cache with
      $login = array(
        'app_id' => $app_id,
        'token' => $token,
        'ip' => $ip,
        'host' => $host,
        'last_used' => time()
      );
      $this->cache->add('api.'.$token, $login);
      return $login;
    } else {
      // Unsuccessful
      return false;
    }

  }

  public function verifyApplicationToken($token, $host, $ip) {

    if ($this->cache->exists('api.'.$token)) {
      $login = $this->cache->get('api.'.$token);

      if (time() - $login['last_used'] <= 1200) {

        // Update last used
        $login['last_used'] = time();
        $this->cache->add('api.'.$token, $login);

        return true;
      }

    }

    return false;
  }

  public function authenticateUser($username, $password = null) {

  	// Get post info
    /*
      username => username to authenticate (required)
      password => password (optional)
    
      returns User ID
    */
    $or_predicate = $this->db->db_or();
    $or_predicate = $or_predicate->condition('USERNAME', $username);
    $or_predicate = $or_predicate->condition('USERNAME', $username.'@ocac.edu');

    $user = $this->db->db_select('CORE_USER', 'user')
      ->fields('user', array('USER_ID', 'USERNAME', 'PASSWORD'))
      ->condition($or_predicate)
      ->execute()->fetch();

    if ($user['USER_ID'] > 0) {
      // check for password, if one
      if ($password != '') {

        if ($this->auth_local->verifyPassword($password, $user['PASSWORD'])) {

          // Successful login
          $token = $user['USER_ID'].'-'.bin2hex(openssl_random_pseudo_bytes(15));
          // Load cache with
          $login = array(
            'user_id' => $user['USER_ID'],
            'username' => $username,
            'last_used' => time(),
            'token' => $token
          );
          $this->cache->add('api_user.'.$token, $login);

          return $login;
        } 

      } else {

        // Successful login
        $token = $user['USER_ID'].'-'.bin2hex(openssl_random_pseudo_bytes(15));
        // Load cache with
        $login = array(
          'user_id' => $user['USER_ID'],
          'username' => $username,
          'last_used' => time(),
          'token' => $token
        );
        $this->cache->add('api_user.'.$token, $login);

        return $login;
      }

    } 

  }

  public function verifyEmail($email_address) {

    if (!$this->authenticateUser($email_address)) {

      // see if email address exists
      $email_address = $this->db->db_select('CONS_EMAIL_ADDRESS', 'email')
        ->fields('email', array('EMAIL_ADDRESS', 'CONSTITUENT_ID'))
        ->condition('email.ACTIVE', 1)
        ->condition('email.UNDELIVERABLE', 0)
        ->condition('email.EMAIL_ADDRESS', $email_address)
        ->orderBy('EFFECTIVE_DATE', 'DESC', 'email')
        ->execute()->fetch();
      if ($email_address['CONSTITUENT_ID'] != '') {

        // create user
        $user = $this->db->db_insert('CORE_USER')
          ->fields(array(
            'USER_ID' => $email_address['CONSTITUENT_ID'],
            'USERNAME' => $email_address['EMAIL_ADDRESS'],
            'CREATED_TIMESTAMP' => date('Y-m-d H:i:s')
          ))->execute();

        return $user;


      } // end if on email address existing

    } // end if on account not existing

  }

  public function verifyLoggedInUser($token) {

    if ($this->cache->exists('api_user.'.$token)) {

      $login = $this->cache->get('api_user.'.$token);

      if (time() - $login['last_used'] <= 1200) {

        // Update last used
        $login['last_used'] = time();
        $this->cache->add('api_user.'.$token, $login);

        return true;
      }

    }

    return false;

  }


}