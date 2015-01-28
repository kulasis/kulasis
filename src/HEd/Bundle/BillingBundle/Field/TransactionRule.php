<?php

namespace Kula\Bundle\HEd\StudentBillingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class TransactionRule implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array(
			'NEWSTU' => 'New Student',
			'ALLSTU' => 'All Students',
			'TUITION' => 'Tuition',
			'AUDIT' => 'Audit',
			'OVERLOAD' => 'Overload',
			'LATE' => 'Late Fee',
			'ADDDROP' => 'Add/Drop Fee'
		);
		
		return $menu;
		
	}
	
}