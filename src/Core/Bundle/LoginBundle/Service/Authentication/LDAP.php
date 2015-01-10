<?php

namespace Kula\Core\Bundle\LoginBundle\Service\Authentication;

class LDAP {
	
	public function authenticate($username, $password, $ldap_server_address, $ldap_server_name, $ldap_append_string) {
		// returns true when user/pass enable bind to LDAP (Windows 2k).
		$ldap_username = substr($username, 0, stripos($username, '@')) . $ldap_append_string;
		$auth_user = strtolower($ldap_username);
		  if ($connect = @ldap_connect($ldap_server_address)) {
			  if ($bind = @ldap_bind($connect, $auth_user, $password)) {
				   @ldap_close($connect);
				   return true;
			   } else {
				   return false;
			   }
		   } else {
				 throw new LDAPConnectionException('Unable to connect to LDAP server ('.$ldap_server_name.').');
		   }
		 @ldap_close($connect);
		 return false;
	}
	
}

class LDAPConnectionException extends \Exception { }