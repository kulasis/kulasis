<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class ScheduleController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STUDENT_STATUS');
		
		// set start date
		$term_info = $this->db()->select('CORE_TERM')
			->fields(null, array('START_DATE', 'END_DATE'))
			->join('CORE_ORGANIZATION_TERMS', null, null, 'CORE_TERM.TERM_ID = CORE_ORGANIZATION_TERMS.TERM_ID')
			->predicate('ORGANIZATION_TERM_ID', $this->record->getSelectedRecord()['ORGANIZATION_TERM_ID'])
			->execute()->fetch();
		
		if ($term_info['START_DATE'] < date('Y-m-d'))
			$drop_date = date('Y-m-d');
		else
			$drop_date = $term_info['START_DATE'];
		
		if ($this->request->request->get('drop')) {
			
			$schedule_service = new \Kula\Bundle\HEd\SchedulingBundle\ScheduleService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
			
			$classes_to_delete = $this->request->request->get('drop')['STUD_STUDENT_CLASSES'];
			$drop_date = date('Y-m-d', strtotime($this->request->request->get('non')['STUD_STUDENT_CLASSES']['DROP_DATE']));
			
			foreach($classes_to_delete as $class_id => $class_row) {
				$schedule_service->dropClassForStudentStatus($class_id, $drop_date);
			}
			
		}
		
		return $this->render('KulaHEdSchedulingBundle:Schedule:index.html.twig', array('drop_date' => $drop_date, 'classes' => $this->_currentSchedule()));
	}
	
	public function gradesAction() {
		$this->authorize();
		$this->setRecordType('STUDENT_STATUS');
		
		// Add new grades
		if ($this->request->request->get('add')) {
			$course_history_service = new \Kula\Bundle\HEd\CourseHistoryBundle\CourseHistoryService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record);
			$new_grades = $this->request->request->get('add')['STUD_STUDENT_COURSE_HISTORY']['new'];
			foreach($new_grades as $student_class_id => $mark) {
				if (isset($mark['MARK']))
					$course_history_service->insertCourseHistoryForClass($student_class_id, $mark['MARK']);
			}
		}
		
		// Edit grades
		$edit_request = $this->request->request->get('edit');
		if (isset($edit_request['STUD_STUDENT_COURSE_HISTORY'])) {
			$course_history_service = new \Kula\Bundle\HEd\CourseHistoryBundle\CourseHistoryService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record);
			$edit_grades = $this->request->request->get('edit')['STUD_STUDENT_COURSE_HISTORY'];
			foreach($edit_grades as $student_course_history_id => $mark) {
				if (isset($mark['MARK']) AND $mark['MARK'] != '') 
					$course_history_service->updateCourseHistoryForClass($student_course_history_id, $mark['MARK'], $mark['COMMENTS']);
				else
					$course_history_service->deleteCourseHistoryForClass($student_course_history_id);
			}
		}
		
		$classes = array();
		
		$classes = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED', 'DROPPED', 'DROP_DATE'))
			->join('STUD_SECTION', 'section', array('SECTION_ID', 'SECTION_NUMBER'), 'class.SECTION_ID = section.SECTION_ID')
			->join('STUD_COURSE', 'course', array('COURSE_NUMBER', 'COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', null, 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
			->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
			->left_join('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', array('COURSE_HISTORY_ID', 'MARK', 'COMMENTS'), 'coursehistory.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->left_join('STUD_MARK_SCALE_MARKS', 'scalemarks', array('ALLOW_COMMENTS', 'REQUIRE_COMMENTS'), 'scalemarks.MARK = coursehistory.MARK AND class.MARK_SCALE_ID = scalemarks.MARK_SCALE_ID')
			->predicate('class.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
			->order_by('DROPPED', 'ASC')
			->order_by('SECTION_NUMBER', 'ASC')
			->order_by('DROP_DATE', 'ASC')
			->execute()->fetchAll();
		
		return $this->render('KulaHEdSchedulingBundle:Schedule:grades.html.twig', array('classes' => $classes));
	}
	
	public function historyAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STUDENT_STATUS');
		
		$classes = array();
		
		$classes = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'DROP_DATE', 'CREATED_TIMESTAMP'))
			->join('STUD_SECTION', 'section', array('SECTION_ID', 'SECTION_NUMBER', 'CREDITS'), 'class.SECTION_ID = section.SECTION_ID')
			->join('STUD_COURSE', 'course', array('COURSE_NUMBER', 'COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->predicate('class.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
			->order_by('SECTION_NUMBER', 'ASC')
			->execute()->fetchAll();
		
		return $this->render('KulaHEdSchedulingBundle:Schedule:history.html.twig', array('classes' => $classes));
	}
	
	public function detailAction($id, $sub_id) {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STUDENT_STATUS');
			
		$class = array();
		
		$class = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('STUDENT_CLASS_ID', 'CHANGE_REASON', 'CHANGE_NOTES', 'DEGREE_REQ_GRP_ID'))
			->predicate('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
			->predicate('STUDENT_CLASS_ID', $sub_id)
			->execute()->fetch();
				
		return $this->render('KulaHEdSchedulingBundle:Schedule:schedule_detail.html.twig', array('class' => $class));	
	}
	
	public function addAction() {
		$this->authorize();
		$this->setRecordType('STUDENT_STATUS');
		$start_date = '';
		
		if ($this->request->request->get('add')) {	
			$schedule_service = new \Kula\Bundle\HEd\SchedulingBundle\ScheduleService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);

			if (isset($this->request->request->get('add')['STUD_STUDENT_CLASSES']['new']['SECTION_ID']))
				$new_classes = $this->request->request->get('add')['STUD_STUDENT_CLASSES']['new']['SECTION_ID'];
			$start_date = date('Y-m-d', strtotime($this->request->request->get('add')['STUD_STUDENT_CLASSES']['new']['START_DATE']));
			
			if (isset($new_classes)) {
				foreach($new_classes as $new_class) {
					$schedule_service->addClassForStudentStatus($this->record->getSelectedRecordID(), $new_class, $start_date);
				}
			}
		}
		
		if ($this->request->request->get('wait_list')) {
			$schedule_service = new \Kula\Bundle\HEd\SchedulingBundle\ScheduleService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
			
			if (isset($this->request->request->get('wait_list')['STUD_STUDENT_WAIT_LIST']['SECTION_ID']))
				$new_classes = $this->request->request->get('wait_list')['STUD_STUDENT_WAIT_LIST']['SECTION_ID'];
			
			if (isset($new_classes)) {
				foreach($new_classes as $new_class) {
					$schedule_service->addWaitListClassForStudentStatus($this->record->getSelectedRecordID(), $new_class);
				}
			}
		}
		
		if ($this->request->request->get('add')) {
			return $this->forward('sis_student_schedule', array('record_type' => 'STUDENT_STATUS', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'STUDENT_STATUS', 'record_id' => $this->record->getSelectedRecordID()));
		}
		
		if ($this->request->request->get('wait_list')) {
			return $this->forward('sis_student_schedule_waitlist', array('record_type' => 'STUDENT_STATUS', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'STUDENT_STATUS', 'record_id' => $this->record->getSelectedRecordID()));
		}
		
		$search_classes = array();
		
		if ($this->request->request->get('search')) {
			$current_section_ids = array();
			$predicate_or = new \Kula\Component\Database\Query\Predicate('OR');
			$predicate_or = $predicate_or->predicate('DROPPED', null)->predicate('DROPPED', 'N');
			
			// Get current classes
			$current_section_ids_result = $this->db()->select('STUD_STUDENT_CLASSES')
				->fields(null, array('SECTION_ID'))
				->predicate('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
				->predicate($predicate_or)
				->execute();
			while ($row = $current_section_ids_result->fetch()) {
				$current_section_ids[] = $row['SECTION_ID'];
			}
			
			$query = \Kula\Component\Database\Searcher::prepareSearch($this->request->request->get('search'), 'STUD_SECTION', 'SECTION_ID');
			$query = $query->fields('STUD_SECTION', array('SECTION_ID', 'SECTION_NUMBER', 'CAPACITY', 'ENROLLED_TOTAL', 'CREDITS', 'WAIT_LISTED_TOTAL'));
			$query = $query->join('STUD_COURSE', 'course', array('COURSE_NUMBER','COURSE_TITLE'), 'STUD_SECTION.COURSE_ID = course.COURSE_ID');
			$query = $query->left_join('STUD_SECTION_MEETINGS', 'meetings', array('MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'), 'meetings.SECTION_ID = STUD_SECTION.SECTION_ID');
			$query = $query->left_join('STUD_ROOM', 'rooms', array('ROOM_NUMBER'), 'rooms.ROOM_ID = meetings.ROOM_ID');
			$query = $query->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', null, 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = STUD_SECTION.STAFF_ORGANIZATION_TERM_ID');
			$query = $query->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgterm.STAFF_ID');
			$query = $query->predicate('STUD_SECTION.STATUS', null);
			$query = $query->predicate('STUD_SECTION.ORGANIZATION_TERM_ID', $this->record->getSelectedRecord()['ORGANIZATION_TERM_ID']);
			if (count($current_section_ids) > 0) $query = $query->predicate('STUD_SECTION.SECTION_ID', $current_section_ids, 'NOT IN');
			$query = $query->order_by('SECTION_NUMBER', 'ASC');
			$query = $query->range(0, 100);
			$search_classes = $query->execute()->fetchAll();
			
			foreach($search_classes as $key => $class) {
				$search_classes[$key]['meets'] = '';
				if ($class['MON'] == 'Y') $search_classes[$key]['meets'] .= 'M';
				if ($class['TUE'] == 'Y') $search_classes[$key]['meets'] .= 'T';
				if ($class['WED'] == 'Y') $search_classes[$key]['meets'] .= 'W';
				if ($class['THU'] == 'Y') $search_classes[$key]['meets'] .= 'R';
				if ($class['FRI'] == 'Y') $search_classes[$key]['meets'] .= 'F';
				if ($class['SAT'] == 'Y') $search_classes[$key]['meets'] .= 'S';
				if ($class['SUN'] == 'Y') $search_classes[$key]['meets'] .= 'U';
			}
			
			// set start date
			$term_info = $this->db()->select('CORE_TERM')
				->fields(null, array('START_DATE'))
				->join('CORE_ORGANIZATION_TERMS', null, null, 'CORE_TERM.TERM_ID = CORE_ORGANIZATION_TERMS.TERM_ID')
				->predicate('ORGANIZATION_TERM_ID', $this->record->getSelectedRecord()['ORGANIZATION_TERM_ID'])
				->execute()->fetch();
			
			if ($term_info['START_DATE'] < date('Y-m-d'))
				$start_date = date('Y-m-d');
			else
				$start_date = $term_info['START_DATE'];
			
		} else {
			$this->setSubmitMode($this->tpl, 'search');
			
		}
		
		return $this->render('KulaHEdSchedulingBundle:Schedule:add.html.twig', array('search_classes' => $search_classes, 'classes' => $this->_currentSchedule(), 'start_date' => $start_date));	
	}
	
	public function waitlistAction() {
		$this->authorize();
		$this->setRecordType('STUDENT_STATUS');
		
		if ($this->request->request->get('drop')) {
			
			$schedule_service = new \Kula\Bundle\HEd\SchedulingBundle\ScheduleService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
			
			$classes_to_delete = $this->request->request->get('drop')['STUD_STUDENT_WAIT_LIST'];
			
			foreach($classes_to_delete as $class_id => $class_row) {
				$schedule_service->dropWaitListClassForStudentStatus($class_id);
			}
		}
		
		$classes = array();
		
		$classes = $this->db()->select('STUD_STUDENT_WAIT_LIST', 'waitlist')
			->fields('waitlist', array('STUDENT_WAIT_LIST_ID', 'ADDED_TIMESTAMP'))
			->join('STUD_SECTION', 'section', array('SECTION_ID', 'SECTION_NUMBER'), 'waitlist.SECTION_ID = section.SECTION_ID')
			->join('STUD_COURSE', 'course', array('COURSE_NUMBER', 'COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', null, 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
			->left_join('STUD_SECTION_MEETINGS', 'meetings', array('MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'), 'meetings.SECTION_ID = section.SECTION_ID')
			->left_join('STUD_ROOM', 'rooms', array('ROOM_NUMBER'), 'rooms.ROOM_ID = meetings.ROOM_ID')
			->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
			->predicate('waitlist.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
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
		
		return $this->render('KulaHEdSchedulingBundle:Schedule:waitlist.html.twig', array('classes' => $classes));
	}
	
	public function calculateTotalsAction() {
		$this->authorize();
		$this->setRecordType('STUDENT_STATUS');
		
		$student_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\StudentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
		
		$student_billing_service->processBilling($this->record->getSelectedRecordID(), 'Schedule Changed');
		
		return $this->forward('sis_student_schedule', array('record_type' => 'STUDENT_STATUS', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'STUDENT_STATUS', 'record_id' => $this->record->getSelectedRecordID()));
	}
	
	private function _currentSchedule() {
		$classes = array();
		
		$predicate_or = new \Kula\Component\Database\Query\Predicate('OR');
		$predicate_or = $predicate_or->predicate('class.DROPPED', null)->predicate('class.DROPPED', 'N');
		
		$classes = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'LEVEL', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED', 'COURSE_ID'))
			->join('STUD_SECTION', 'section', array('SECTION_ID', 'SECTION_NUMBER'), 'class.SECTION_ID = section.SECTION_ID')
			->join('STUD_COURSE', 'course', array('COURSE_NUMBER', 'COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
			->left_join('STUD_COURSE', 'course2', array('COURSE_NUMBER' => 'second_COURSE_NUMBER', 'COURSE_TITLE'  => 'second_COURSE_TITLE'), 'course2.COURSE_ID = class.COURSE_ID')
			->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', null, 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
			->left_join('STUD_SECTION_MEETINGS', 'meetings', array('MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'), 'meetings.SECTION_ID = section.SECTION_ID')
			->left_join('STUD_ROOM', 'rooms', array('ROOM_NUMBER'), 'rooms.ROOM_ID = meetings.ROOM_ID')
			->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
			->predicate('class.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
			->predicate($predicate_or)
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
		
		return $classes;	
	}
}