<?php

namespace Kula\Bundle\HEd\StudentBillingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class BillingMode implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array(
			'STAND' => 'Standard',
			'HOUR' => 'Hourly'
		);
		
		return $menu;
		
	}
	
}