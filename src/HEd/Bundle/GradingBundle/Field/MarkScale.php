<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class MarkScale implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array();
		
		$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
		$or_condition = $or_condition->predicate('scale.INACTIVE_AFTER', null)
			->predicate('scale.INACTIVE_AFTER', date('Y-m-d'), '>');
		
		$result = \Kula\Component\Database\DB::connect('read')->select('STUD_MARK_SCALE', 'scale')
	->fields('scale', array('MARK_SCALE_ID','MARK_SCALE_NAME'))
	->predicate($or_condition)
	->order_by('MARK_SCALE_NAME', 'ASC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['MARK_SCALE_ID']] = $row['MARK_SCALE_NAME'];
		}
		
		return $menu;
		
	}
	
}