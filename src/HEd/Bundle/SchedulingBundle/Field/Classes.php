<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class Classes implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {
		
		$classes = array();
		$classes_result = \Kula\Component\Database\DB::connect('read')->select('STUD_STUDENT_CLASSES', 'classes')
			->fields('classes', array('STUDENT_CLASS_ID'))
			->join('STUD_SECTION', 'sec', array('SECTION_NUMBER'), 'classes.SECTION_ID = sec.SECTION_ID')
			->join('STUD_COURSE', 'crs', array('COURSE_TITLE'), 'crs.COURSE_ID = sec.COURSE_ID')
			->join('STUD_STUDENT_STATUS', 'status', null, 'status.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
			->left_join('STUD_STUDENT_COURSE_HISTORY', 'crshis', array('COURSE_HISTORY_ID'), 'crshis.STUDENT_CLASS_ID = classes.STUDENT_CLASS_ID')
			->predicate('status.STUDENT_ID', $param['STUDENT_ID'])
			->order_by('TERM_ABBREVIATION', 'ASC')
			->order_by('ORGANIZATION_ABBREVIATION', 'ASC')
			->order_by('COURSE_TITLE', 'ASC');
		$classes_result = $classes_result->execute();
		$i = 0;
		while ($classes_row = $classes_result->fetch()) {
			if ($classes_row['COURSE_HISTORY_ID'])
				$msg = 'Selected';
			else
				$msg = 'Not Selected';
			$classes[$msg][$classes_row['STUDENT_CLASS_ID']] = $classes_row['TERM_ABBREVIATION'] . ' / ' . $classes_row['ORGANIZATION_ABBREVIATION'] . ' / ' . $classes_row['SECTION_NUMBER'] . ' / ' . $classes_row['COURSE_TITLE'];
			
		$i++;
		}
		
		return $classes;
		
	}
	
}