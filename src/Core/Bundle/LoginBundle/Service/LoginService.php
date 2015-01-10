<?php

namespace Kula\Core\Bundle\LoginBundle\Service;

class LoginService {
	
	protected $database;
	protected $poster_factory;
	protected $session;
	protected $flash;
	protected $auth_google;
	protected $auth_ldap;
	protected $auth_local;
	
	protected $record;
	
	
	
	public function __construct(\Kula\Component\Database\Connection $db, 
															$auth_google,
															$auth_ldap,
															$auth_local) {
		$this->database = $db;
		$this->auth_google = $auth_google;
		$this->auth_ldap = $auth_ldap;
		$this->auth_local = $auth_local;
	}
	
	public function login($username, $password = null) {
		
		$or_predicate = new \Kula\Component\Database\Query\Predicate('OR');
		$or_predicate = $or_predicate->predicate('USERNAME', $username);
		$or_predicate = $or_predicate->predicate('USERNAME', $username.'@ocac.edu');
		$or_predicate = $or_predicate->predicate('USERNAME', $this->auth_google->getEmailAddress());
		
		// check if both username and password fields completed
		if (($username && $password) || $this->auth_google->getEmailAddress()) {
			// get password for username
			$result = $this->database->select('CORE_USER', 'user')
				->fields('user', array('USER_ID', 'USERNAME', 'PASSWORD', 'ALLOW_AUTH_LOCAL', 'ALLOW_AUTH_LDAP', 'ALLOW_AUTH_GOOGLE'))
				->left_join('CORE_SYSTEM_LDAP', 'ldap', array('SERVER_NAME', 'SERVER_ADDRESS', 'DOMAIN_APPEND'), 'ldap.LDAP_ID = user.LDAP_ID')
				->predicate($or_predicate)
				->execute()->fetch();
			
			// Make sure username exists
			if ($result['USERNAME']) {
				// Authenticate using authentication method; Priority: Local, LDAP, Google API
				if ($result['ALLOW_AUTH_LOCAL'] == 'Y') {
					if ($this->auth_local->verifyPassword($password, $result['PASSWORD'])) {
						$this->establishSession($result['USER_ID']);
						return true;
					}
				}
				if ($result['ALLOW_AUTH_LDAP'] == 'Y') {
					try {
						
						if ($this->auth_ldap->authenticate($result['USERNAME'], $password, $result['SERVER_ADDRESS'], $result['SERVER_NAME'], $result['DOMAIN_APPEND'])) {

							$this->establishSession($result['USER_ID']);
							return true;
						}

					} catch (LDAPConnectionException $e) {
						$flash->add('error', $e->getMessage());
					}
					
				}
				if ($result['ALLOW_AUTH_GOOGLE'] == 'Y') {
					if ($email = $this->auth_google->getEmailAddress()) {
						$this->establishSession($result['USER_ID']);
						return true;
					}
				}
					
			}
		}
		return false;
	}
	
	public function changeRole($new_role_id) {
		$this->kula_session->changeRole($new_role_id);
	}
	
	public function logout() {
		if ($this->kula_session->get('session_id') != '') {
			try {
			new \Kula\Component\Database\Poster(null, array('LOG_SESSION' => array($this->kula_session->get('session_id') => array('OUT_TIME' => date('Y-m-d H:i:s')))));
			} catch (\Exception $e) {  }
		}
		
		$this->session->invalidate();
		
		return true;
	}
	
	public function establishSession($user_id) {
		$this->kula_session->loadUser($user_id);
	}
	
}