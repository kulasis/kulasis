<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class Mark implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array();
		
		$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
		$or_condition = $or_condition->predicate('marks.INACTIVE_AFTER', null)
			->predicate('marks.INACTIVE_AFTER', date('Y-m-d'), '>');
		
		$result = \Kula\Component\Database\DB::connect('read')->select('STUD_MARK_SCALE_MARKS', 'marks')
	->fields('marks', array('MARK'))
	->predicate('marks.MARK_SCALE_ID', $param['MARK_SCALE_ID'])
	->predicate($or_condition)
	->order_by('SORT', 'ASC');
		
		if (isset($param['TEACHER']) AND $param['TEACHER']) {
			$result = $result->predicate('marks.ALLOW_TEACHER', 'Y');
		}
			
		$result = $result->execute();
		while ($row = $result->fetch()) {
			$menu[$row['MARK']] = $row['MARK'];
		}
		
		return $menu;
		
	}
	
}