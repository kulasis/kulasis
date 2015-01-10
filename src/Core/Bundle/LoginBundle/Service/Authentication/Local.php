<?php

namespace Kula\Core\Bundle\LoginBundle\Service\Authentication;

class Local {
	
	/**
	 * @param string Password to Hash
	 * @return string Hashed password to store
	 */
	public function createHashForPassword($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	public function verifyPassword($password, $hash) {
		return password_verify($password, $hash);
	}
	
}