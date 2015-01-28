<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class DegreeProgram implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array();

		$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
		$or_condition = $or_condition->predicate('marks.INACTIVE_AFTER', null)
			->predicate('marks.INACTIVE_AFTER', date('Y-m-d'), '>');
		
		$result = \Kula\Component\Database\DB::connect('read')->select('STUD_STUDENT_DEGREES', 'studegrees')
	->fields('studegrees', array('STUDENT_DEGREE_ID', 'EFFECTIVE_DATE'))
	->join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'studegrees.DEGREE_ID = degree.DEGREE_ID')
	->predicate('studegrees.STUDENT_ID', $param['STUDENT_ID'])
	->order_by('EFFECTIVE_DATE', 'DESC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['STUDENT_DEGREE_ID']] = $row['DEGREE_NAME'];
			if ($row['EFFECTIVE_DATE']) $menu[$row['STUDENT_DEGREE_ID']] .= ' - '.date('m/d/Y', strtotime($row['EFFECTIVE_DATE']));
		}
		
		return $menu;
		
	}
	
}