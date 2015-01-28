<?php

namespace Kula\Bundle\HEd\OfferingBundle\Chooser;

class CoursesChooser extends \Kula\Component\Chooser\Chooser {
	
	public static function search($q) {
		
		$query_conditions = new \Kula\Component\Database\Query\Predicate('OR');
		$query_conditions = $query_conditions->predicate('COURSE_TITLE', $q.'%', 'LIKE');
		$query_conditions = $query_conditions->predicate('COURSE_NUMBER', $q.'%', 'LIKE');
		$query_conditions = $query_conditions->predicate('SHORT_TITLE', $q.'%', 'LIKE');
		
		$data = array();
		
		$search = self::db()->select('STUD_COURSE', 'course')
			->fields('course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE'))
			->where($query_conditions)
			->order_by('COURSE_NUMBER', 'ASC');
		$search = $search	->execute();
		while ($row = $search->fetch()) {
			self::addToChooserMenu($row['COURSE_ID'], $row['COURSE_NUMBER'].' / '.$row['COURSE_TITLE']);
		}
		
	}
	
	public static function choice($id) {
		$row = self::db()->select('STUD_COURSE', 'course')
			->fields('course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE'))
			->predicate('course.COURSE_ID', $id)
			->execute()
			->fetch();
		return self::currentValue($row['COURSE_ID'], $row['COURSE_NUMBER'].' / '.$row['COURSE_TITLE']);
	}
	
	public static function searchRoute() {
		return 'sis_offering_course_chooser';
	}
	
}