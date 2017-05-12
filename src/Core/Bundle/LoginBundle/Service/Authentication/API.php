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
      ->fields('apps', array('INTG_API_APP_ID', 'LAST_TOKEN', 'LAST_TOKEN_TIMESTAMP'))
      ->condition('HOST', $host)
      ->condition('IP_ADDRESS', $ip)
      ->condition('APP_ID', $app_id)
      ->condition('APP_SECRET', $app_secret)
      ->execute()->fetch();

    if ($app['INTG_API_APP_ID'] != '') {

      if (time() - $app['LAST_TOKEN_TIMESTAMP'] <= 1200) {

        $this->db->db_update('CORE_INTG_API_APPS')->fields(array(
          'LAST_TOKEN_TIMESTAMP' => time()
        ))->condition('INTG_API_APP_ID', $app['INTG_API_APP_ID'])
        ->execute();

        $login = array(
          'app_id' => $app_id,
          'token' => $app['LAST_TOKEN'],
          'ip' => $ip,
          'host' => $host,
          'last_used' => time()
        );
      } else {

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

        $application_session_id = $this->db->db_insert('LOG_SESSION', array('target' => 'additional'))->fields(array(
          'API_APPLICATION_ID' => $app['INTG_API_APP_ID'],
          'IN_TIME' => date('Y-m-d H:i:s'),
          'AUTH_METHOD' => 'API',
          'TOKEN' => $token
        ))->execute();

        $_SESSION['application_session_id'] = $application_session_id;

        // Update last token
        $this->db->db_update('CORE_INTG_API_APPS')->fields(array(
          'LAST_TOKEN' => $token,
          'LAST_TOKEN_TIMESTAMP' => time()
        ))->condition('INTG_API_APP_ID', $app['INTG_API_APP_ID'])
        ->execute();
      }

      //$this->cache->add('api.'.$token, $login);
      return $login;
    } else {
      // Unsuccessful
      return false;
    }

  }

  public function verifyApplicationToken($token, $host, $ip) {

    $app = $this->db->db_select('CORE_INTG_API_APPS', 'apps')
      ->fields('apps', array('INTG_API_APP_ID', 'LAST_TOKEN_TIMESTAMP'))
      ->condition('HOST', $host)
      ->condition('IP_ADDRESS', $ip)
      ->condition('LAST_TOKEN', $token)
      ->execute()->fetch();

    if ($app['INTG_API_APP_ID'] != '' AND time() - $app['LAST_TOKEN_TIMESTAMP'] <= 1200) {

      $this->db->db_update('CORE_INTG_API_APPS')->fields(array(
        'LAST_TOKEN_TIMESTAMP' => time()
      ))->condition('LAST_TOKEN', $token)
      ->execute();

      $this->intg_api_app_id = $app['INTG_API_APP_ID'];

      $_SESSION['application_session_id'] = $this->db->db_select('LOG_SESSION', 'session', array('target' => 'additional'))
        ->fields('session', array('SESSION_ID'))
        ->condition('TOKEN', $token)
        ->condition('API_APPLICATION_ID', $app['INTG_API_APP_ID'])
        ->execute()->fetch()['SESSION_ID'];
      
      return true;
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
      ->fields('user', array('USER_ID', 'USERNAME', 'PASSWORD', 'LAST_TOKEN_TIMESTAMP', 'LAST_TOKEN'))
      ->condition($or_predicate)
      ->execute()->fetch();

    if ($user['USER_ID'] > 0) {
      // check for password, if one
      if ($password != '') {

        if ($this->auth_local->verifyPassword($password, $user['PASSWORD'])) {

          if ($user['USER_ID'] != '' AND time() - $user['LAST_TOKEN_TIMESTAMP'] <= 1200) {
            // Update last used
            $this->db->db_update('CORE_USER')->fields(array(
              'LAST_TOKEN_TIMESTAMP' => time()
            ))->condition('USER_ID', $user['USER_ID'])->execute();

            $login = array(
              'user_id' => $user['USER_ID'],
              'username' => $user['USERNAME'],
              'last_used' => time(),
              'token' => $user['LAST_TOKEN']
            );

            return $login;
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

            $user_session_id = $this->db->db_insert('LOG_SESSION', array('target' => 'additional'))->fields(array(
              'USER_ID' => $user['USER_ID'],
              'IN_TIME' => date('Y-m-d H:i:s'),
              'AUTH_METHOD' => 'API',
              'API_APPLICATION_ID' => isset($this->intg_api_app_id) ? $this->intg_api_app_id : null,
              'TOKEN' => $token
            ))->execute();

            $_SESSION['user_session_id'] = $user_session_id;

            // Update last token
            $this->db->db_update('CORE_USER')->fields(array(
              'LAST_TOKEN' => $token,
              'LAST_TOKEN_TIMESTAMP' => time()
            ))->condition('USER_ID', $user['USER_ID'])
            ->execute();
          }

          return $login;
        } 

      } else {

        if ($user['USER_ID'] != '' AND time() - $user['LAST_TOKEN_TIMESTAMP'] <= 1200) {
          // Update last used
          $this->db->db_update('CORE_USER')->fields(array(
            'LAST_TOKEN_TIMESTAMP' => time()
          ))->condition('USER_ID', $user['USER_ID'])->execute();

          $login = array(
            'user_id' => $user['USER_ID'],
            'username' => $user['USERNAME'],
            'last_used' => time(),
            'token' => $user['LAST_TOKEN']
          );

          return $login;
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

          $user_session_id = $this->db->db_insert('LOG_SESSION', array('target' => 'additional'))->fields(array(
            'USER_ID' => $user['USER_ID'],
            'IN_TIME' => date('Y-m-d H:i:s'),
            'AUTH_METHOD' => 'API',
            'API_APPLICATION_ID' => isset($this->intg_api_app_id) ? $this->intg_api_app_id : null,
            'TOKEN' => $token
          ))->execute();

          $_SESSION['user_session_id'] = $user_session_id;

          // Update last token
          $this->db->db_update('CORE_USER')->fields(array(
            'LAST_TOKEN' => $token,
            'LAST_TOKEN_TIMESTAMP' => time()
          ))->condition('USER_ID', $user['USER_ID'])
          ->execute();

          return $login;
        }
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

    $app = $this->db->db_select('CORE_USER', 'user')
      ->fields('user', array('USER_ID', 'LAST_TOKEN_TIMESTAMP'))
      ->condition('LAST_TOKEN', $token)
      ->execute()->fetch();

    if ($app['USER_ID'] != '' AND time() - $app['LAST_TOKEN_TIMESTAMP'] <= 1200) {
      // Update last used
      $this->db->db_update('CORE_USER')->fields(array(
        'LAST_TOKEN_TIMESTAMP' => time()
      ))->condition('LAST_TOKEN', $token)
      ->execute();

      $_SESSION['user_session_id'] = $this->db->db_select('LOG_SESSION', 'session', array('target' => 'additional'))
        ->fields('session', array('SESSION_ID'))
        ->condition('TOKEN', $token)
        ->condition('USER_ID', $app['USER_ID'])
        ->execute()->fetch()['SESSION_ID'];

      return $app['USER_ID'];
    }

    return false;

  }


}