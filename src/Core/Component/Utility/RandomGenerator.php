<?php

namespace Kula\Core\Component\Utility;

class RandomGenerator {

	public static function string($length){
	  $possible = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	  $str = "";
	  while (strlen($str) < $length) {
	    $str .= strtolower(substr($possible,(rand() % strlen($possible)),1));
	  }
	  return ($str);
	}

}