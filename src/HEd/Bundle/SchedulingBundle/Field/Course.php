<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class Course implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {
		
		$courses = array();
		
		$set_course = \Kula\Component\Database\DB::connect('read')->select('STUD_SECTION', 'sec')
			->fields('sec', array())
			->join('STUD_COURSE', 'crs', array('COURSE_ID', 'COURSE_TITLE', 'COURSE_NUMBER'), 'sec.COURSE_ID = crs.COURSE_ID')
			->predicate('sec.SECTION_ID', $param['SECTION_ID'])
			->execute()->fetch();
		$courses[$set_course['COURSE_ID']] = $set_course['COURSE_NUMBER'].' '.$set_course['COURSE_TITLE'];
		
		$courses_result = \Kula\Component\Database\DB::connect('read')->select('STUD_SECTION_COURSES', 'seccourses')
			->fields('seccourses', array('COURSE_ID'))
			->join('STUD_COURSE', 'crs', array('COURSE_NUMBER', 'COURSE_TITLE'), 'crs.COURSE_ID = seccourses.COURSE_ID')
			->predicate('seccourses.SECTION_ID', $param['SECTION_ID'])
			->order_by('COURSE_NUMBER', 'ASC');
		$courses_result = $courses_result->execute();
		$i = 0;
		
		while ($courses_row = $courses_result->fetch()) {
			$courses[$courses_row['COURSE_ID']] = $courses_row['COURSE_NUMBER'].' '.$courses_row['COURSE_TITLE'];
			
		$i++;
		}
		
		if ($courses_row) {
			return $courses;
		} else {
			return array();
		}
		
	}
	
}