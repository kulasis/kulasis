<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class StaffController extends Controller {
	
	public function staff_scheduleAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STAFF_SCHOOL_TERM');
		
		$classes = array();
		
		if ($this->record->getSelectedRecordID()) {
		
		$classes = $this->db()->select('STUD_SECTION', 'section')
			->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
			->join('STUD_COURSE', 'course', array('COURSE_NUMBER', 'COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', null, 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
			->left_join('STUD_SECTION_MEETINGS', 'meetings', array('MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'), 'meetings.SECTION_ID = section.SECTION_ID')
			->left_join('STUD_ROOM', 'rooms', array('ROOM_NUMBER'), 'rooms.ROOM_ID = meetings.ROOM_ID')
			->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
			->predicate('section.STAFF_ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
			->order_by('SECTION_NUMBER', 'ASC')
			->execute()->fetchAll();
		
			foreach($classes as $key => $class) {
				$classes[$key]['meets'] = '';
				if ($class['MON'] == 'Y') $classes[$key]['meets'] .= 'M';
				if ($class['TUE'] == 'Y') $classes[$key]['meets'] .= 'T';
				if ($class['WED'] == 'Y') $classes[$key]['meets'] .= 'W';
				if ($class['THU'] == 'Y') $classes[$key]['meets'] .= 'R';
				if ($class['FRI'] == 'Y') $classes[$key]['meets'] .= 'F';
				if ($class['SAT'] == 'Y') $classes[$key]['meets'] .= 'S';
				if ($class['SUN'] == 'Y') $classes[$key]['meets'] .= 'U';
			}
		
		}
		
		return $this->render('KulaHEdSchedulingBundle:Staff:schedule.html.twig', array('classes' => $classes));	
	}
	
}