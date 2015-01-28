<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class Standing implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array();
		
		//$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
		//$or_condition = $or_condition->predicate('scale.INACTIVE_AFTER', null)
		//	->predicate('scale.INACTIVE_AFTER', date('Y-m-d'), '>');
		
		$result = \Kula\Component\Database\DB::connect('read')->select('STUD_STANDING', 'standing')
	->fields('standing', array('STANDING_ID','STANDING_DESCRIPTION'))
	//->predicate($or_condition)
	->order_by('STANDING_CODE', 'ASC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['STANDING_ID']] = $row['STANDING_DESCRIPTION'];
		}
		
		return $menu;
		
	}
	
}