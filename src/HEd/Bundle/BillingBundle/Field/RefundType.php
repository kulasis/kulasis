<?php

namespace Kula\Bundle\HEd\StudentBillingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class RefundType implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array(
			'TUITION' => 'Tuition',
			'COURSEFEE' => 'Course Fees'
		);
		
		return $menu;
		
	}
	
}