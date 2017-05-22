<?php

namespace Kula\Core\Bundle\LoginBundle\Service;

class LoginService {
  
  protected $db;
  protected $poster_factory;
  protected $session;
  protected $flash;
  protected $auth_google;
  protected $auth_ldap;
  protected $auth_local;
  
  protected $record;
  
  
  
  public function __construct(\Kula\Core\Component\DB\DB $db, $schema, $session, $auth_google, $auth_ldap, $auth_local) {
    $this->db = $db;
    $this->schema = $schema;
    $this->session = $session;
    $this->auth_google = $auth_google;
    $this->auth_ldap = $auth_ldap;
    $this->auth_local = $auth_local;
  }
  
  public function login($username, $password = null) {
    
    $or_predicate = $this->db->db_or();
    $or_predicate = $or_predicate->condition('USERNAME', $username);
    $or_predicate = $or_predicate->condition('USERNAME', $username.'@ocac.edu');
    $or_predicate = $or_predicate->condition('USERNAME', $this->auth_google->getEmailAddress());
    
    // check if both username and password fields completed
    if (($username && $password) || $this->auth_google->getEmailAddress()) {
      // get password for username
      $result = $this->db->db_select('CORE_USER', 'user')
        ->fields('user', array('USER_ID', 'USERNAME', 'PASSWORD', 'ALLOW_AUTH_LOCAL', 'ALLOW_AUTH_LDAP', 'ALLOW_AUTH_GOOGLE', 'FAILED_ATTEMPTS'))
        ->leftJoin('CORE_SYSTEM_LDAP', 'ldap', 'ldap.LDAP_ID = user.LDAP_ID')
        ->fields('ldap', array('SERVER_NAME', 'SERVER_ADDRESS', 'DOMAIN_APPEND'))
        ->condition($or_predicate)
        ->execute()->fetch();
      
      // Make sure username exists
      if ($result['USERNAME'] AND $result['FAILED_ATTEMPTS'] <= 3) {
        // Authenticate using authentication method; Priority: Local, LDAP, Google API
        if ($result['ALLOW_AUTH_LOCAL'] == 1) {
          if ($this->auth_local->verifyPassword($password, $result['PASSWORD'])) {
            $this->establishSession($result['USER_ID']);
            $this->resetFailedLoginAttempt($result['USER_ID']);
            return true;
          }
        }
        if ($result['ALLOW_AUTH_LDAP'] == 1) {
          try {
            
            if ($this->auth_ldap->authenticate($result['USERNAME'], $password, $result['SERVER_ADDRESS'], $result['SERVER_NAME'], $result['DOMAIN_APPEND'])) {

              $this->establishSession($result['USER_ID']);
              $this->resetFailedLoginAttempt($result['USER_ID']);
              return true;
            }

          } catch (LDAPConnectionException $e) {
            $flash->add('error', $e->getMessage());
          }
          
        }
        if ($result['ALLOW_AUTH_GOOGLE'] == 1) {
          if ($email = $this->auth_google->getEmailAddress()) {
            $this->establishSession($result['USER_ID']);
            $this->resetFailedLoginAttempt($result['USER_ID']);
            return true;
          }
        }
        // Didn't authenticate; log failed attempt
        $this->addFailedLoginAttempt($result['USER_ID'], $result['FAILED_ATTEMPTS']);
          
      }
    }
    return false;
  }

  public function addFailedLoginAttempt($user_id, $failed_attempts) {
    $this->db->db_update('CORE_USER')->fields(array('FAILED_ATTEMPTS' => $failed_attempts + 1))->condition('USER_ID', $user_id)->execute();
  }

  public function resetFailedLoginAttempt($user_id) {
    $this->db->db_update('CORE_USER')->fields(array('FAILED_ATTEMPTS' => 0))->condition('USER_ID', $user_id)->execute();
  }
  
  public function changeRole($new_role_id) {
    $this->session->changeRole($new_role_id);
  }
  
  public function logout() {
    if ($this->session->get('session_id') != '') {
      $this->session->logClosedSession($this->session->get('session_id'));
    }
    
    $this->session->invalidate();
    
    return true;
  }
  
  public function establishSession($user_id) {
    $this->session->loadUser($user_id);
  }
  
}