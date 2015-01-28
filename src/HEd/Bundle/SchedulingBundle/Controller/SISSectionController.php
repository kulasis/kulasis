<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class SectionController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('SECTION');
		
		$section = $this->db()->select('STUD_SECTION', 'section')
			->fields('section', array('SECTION_ID','CREDITS', 'START_DATE', 'END_DATE', 'CAPACITY', 'MINIMUM', 'ENROLLED_TOTAL', 'WAIT_LISTED_TOTAL', 'MARK_SCALE_ID'))
			->predicate('section.SECTION_ID', $this->record->getSelectedRecordID())
			->execute()->fetch();
		
		$meeting_times = $this->db()->select('STUD_SECTION_MEETINGS')
			->predicate('SECTION_ID', $this->record->getSelectedRecordID())
			->execute()->fetchAll();
		
		return $this->render('KulaHEdSchedulingBundle:Section:index.html.twig', array('section' => $section, 'meeting_times' => $meeting_times));
	}
	
	public function coursesAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('SECTION');
		
		$courses = $this->db()->select('STUD_SECTION_COURSES', 'courses')
			->fields('courses', array('SECTION_COURSE_ID', 'COURSE_ID'))
			->predicate('courses.SECTION_ID', $this->record->getSelectedRecordID())
			->execute()->fetchAll();
		
		return $this->render('KulaHEdSchedulingBundle:Section:courses.html.twig', array('courses' => $courses));
	}
	
	public function staffAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('SECTION');
		
		$staff = $this->db()->select('STUD_SECTION_STAFF', 'staff')
			->fields('staff', array('SECTION_STAFF_ID', 'SECTION_ID', 'STAFF_ORGANIZATION_TERM_ID'))
			->predicate('staff.SECTION_ID', $this->record->getSelectedRecordID())
			->execute()->fetchAll();
		
		return $this->render('KulaHEdSchedulingBundle:Section:staff.html.twig', array('staff' => $staff));
	}
	
	public function rosterAction() {
		$this->authorize();
		$this->setRecordType('SECTION');
		
		$students = array();
		
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
		
		if ($this->request->request->get('delete')) {
			
			$schedule_service = new \Kula\HigherEd\Student\Schedule\Services\ScheduleService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
			
			$classes_to_delete = $this->request->request->get('delete')['STUD_STUDENT_CLASSES'];
			$drop_date = date('Y-m-d', strtotime($this->request->request->get('edit')['STUD_STUDENT_CLASSES']['DROP_DATE']));
			
			foreach($classes_to_delete as $class_id => $class_row) {
				$schedule_service->dropClassForStudentStatus($class_id, $drop_date);
			}
			
		}
		
		$students = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED'))
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID'), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
			->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
			->join('CONS_CONSTITUENT', 'constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
			->predicate('class.SECTION_ID', $this->record->getSelectedRecordID())
			->order_by('DROPPED', 'ASC')
			->order_by('LAST_NAME', 'ASC')
			->order_by('FIRST_NAME', 'ASC')
			->execute()->fetchAll();
		
		return $this->render('KulaHEdSchedulingBundle:Section:roster.html.twig', array('students' => $students, 'drop_date' => $drop_date));
	}
	
	public function waitlistAction() {
		$this->authorize();
		$this->setRecordType('SECTION');
		
		$students = array();
		
		if ($this->request->request->get('delete')) {
			
			$schedule_service = new \Kula\HigherEd\Student\Schedule\Services\ScheduleService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
			
			$classes_to_delete = $this->request->request->get('delete')['STUD_STUDENT_WAIT_LIST'];
			
			foreach($classes_to_delete as $class_id => $class_row) {
				$schedule_service->dropWaitListClassForStudentStatus($class_id);
			}
			
		}
		
		$students = $this->db()->select('STUD_STUDENT_WAIT_LIST', 'waitlist')
			->fields('waitlist', array('STUDENT_WAIT_LIST_ID', 'ADDED_TIMESTAMP'))
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID'), 'status.STUDENT_STATUS_ID = waitlist.STUDENT_STATUS_ID')
			->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
			->join('CONS_CONSTITUENT', 'constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
			->predicate('waitlist.SECTION_ID', $this->record->getSelectedRecordID())
			->order_by('ADDED_TIMESTAMP', 'ASC')
			->order_by('LAST_NAME', 'ASC')
			->order_by('FIRST_NAME', 'ASC')
			->execute()->fetchAll();
		
		return $this->render('KulaHEdSchedulingBundle:Section:waitlist.html.twig', array('students' => $students));
	}
	
	public function gradesAction() {
		$this->authorize();
		$this->setRecordType('SECTION');
		
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
		$edit = $this->request->request->get('edit');
		if (isset($edit['STUD_STUDENT_COURSE_HISTORY'])) {
			$course_history_service = new \Kula\Bundle\HEd\CourseHistoryBundle\CourseHistoryService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record);
			$edit_grades = $this->request->request->get('edit')['STUD_STUDENT_COURSE_HISTORY'];
			foreach($edit_grades as $student_course_history_id => $mark) {
				if (isset($mark['MARK']) AND $mark['MARK'] != '')
					$course_history_service->updateCourseHistoryForClass($student_course_history_id, $mark['MARK'], $mark['COMMENTS']);
				else
					$course_history_service->deleteCourseHistoryForClass($student_course_history_id);
			}
		}
		
		$students = array();
		
		$students = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('STUDENT_CLASS_ID', 'MARK_SCALE_ID', 'START_DATE', 'END_DATE', 'DROPPED'))
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID',), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
			->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
			->join('CONS_CONSTITUENT', 'constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
			->join('STUD_SECTION', 'section', null, 'section.SECTION_ID = class.SECTION_ID')		
			->left_join('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', array('MARK', 'COURSE_HISTORY_ID', 'COMMENTS'), 'coursehistory.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->left_join('STUD_MARK_SCALE_MARKS', 'scalemarks', array('ALLOW_COMMENTS', 'REQUIRE_COMMENTS'), 'scalemarks.MARK = coursehistory.MARK AND class.MARK_SCALE_ID = scalemarks.MARK_SCALE_ID')
			->predicate('class.SECTION_ID', $this->record->getSelectedRecordID())
			->order_by('DROPPED', 'ASC')
			->order_by('LAST_NAME', 'ASC')
			->order_by('FIRST_NAME', 'ASC')
			->execute()->fetchAll();
		
		if (isset($edit['STUD_SECTION'])) {
			
			foreach ($edit['STUD_SECTION'] as $section_id => $section_row) {
				
				if (isset($section_row['TEACHER_GRADES_COMPLETED']['checkbox']) AND $section_row['TEACHER_GRADES_COMPLETED']['checkbox'] == 'Y' AND $section_row['TEACHER_GRADES_COMPLETED']['checkbox_hidden'] != 'Y') {
					// Set as finalized
					$poster = new \Kula\Component\Database\PosterFactory;
					$info = array('STUD_SECTION' => array($this->record->getSelectedRecordID() => array(
						'TEACHER_GRADES_COMPLETED' => array('checkbox_hidden' => 'N', 'checkbox' => 'Y'),
						'TEACHER_GRADES_COMPLETED_USERSTAMP' => $this->session->get('user_id'),
						'TEACHER_GRADES_COMPLETED_TIMESTAMP' => date('Y-m-d H:i:s')
					)));

					$poster->newPoster(null, $info);
				}
				
				if (!isset($section_row['TEACHER_GRADES_COMPLETED']['checkbox']) AND $section_row['TEACHER_GRADES_COMPLETED']['checkbox_hidden'] == 'Y') {
					// Unset as finalized
					$poster = new \Kula\Component\Database\PosterFactory;
					$info = array('STUD_SECTION' => array($this->record->getSelectedRecordID() => array(
						'TEACHER_GRADES_COMPLETED' => array('checkbox_hidden' => 'Y', 'checkbox' => null),
						'TEACHER_GRADES_COMPLETED_USERSTAMP' => null,
						'TEACHER_GRADES_COMPLETED_TIMESTAMP' => null
					)));

					$poster->newPoster(null, $info);
				}  
				
			}
			
		}
		
		// Get submitted grades info
		$submitted_grades_info = $this->db()->select('STUD_SECTION', 'section')
			->fields('section', array('TEACHER_GRADES_COMPLETED', 'TEACHER_GRADES_COMPLETED_TIMESTAMP'))
			->left_join('CORE_USER', 'user', array('USERNAME'), 'user.USER_ID = section.TEACHER_GRADES_COMPLETED_USERSTAMP')
			->predicate('section.SECTION_ID', $this->record->getSelectedRecordID())
			->execute()->fetch();

		return $this->render('KulaHEdSchedulingBundle:Section:grades.html.twig', array('students' => $students, 'section_info' => $submitted_grades_info));
	}
	
	public function addAction() {
		$this->authorize();
		$this->setRecordType('SECTION', 'Y');
		$this->formAction('sis_offering_sections_create');
		return $this->render('KulaHEdSchedulingBundle:Section:add.html.twig');
	}
	
	public function createAction() {
		$this->authorize();
		
		$add = $this->request->request->get('add');
		
		foreach($add as $table => $add_row) {
			foreach($add_row as $key => $row) {
				
				// Get Course
				$course_info = $this->db()->select('STUD_COURSE', 'course')
					->fields('course', array('MARK_SCALE_ID', 'COURSE_NUMBER'))
					->predicate('course.COURSE_ID', $row['COURSE_ID'])
					->execute()->fetch();
				
				// Get last section number
				$section_number = $this->db()->select('STUD_SECTION', 'section')
					->fields('section', array('SECTION_NUMBER'))
					->predicate('section.COURSE_ID', $row['COURSE_ID'])
					->predicate('section.ORGANIZATION_TERM_ID', $row['hidden']['ORGANIZATION_TERM_ID'])
					->order_by('SECTION_NUMBER', 'DESC', 'section')
					->execute()->fetch();
				if ($section_number['SECTION_NUMBER']) {
					// Split section
					$split_section = explode('-', $section_number['SECTION_NUMBER']);
					$new_number = str_pad($split_section[1] + 1, 2, '0', STR_PAD_LEFT);
					$add[$table][$key]['SECTION_NUMBER'] = $course_info['COURSE_NUMBER'].'-'.$new_number;
				} else {
					$add[$table][$key]['SECTION_NUMBER'] = $course_info['COURSE_NUMBER'].'-01';
				}
				
				
				$add[$table][$key]['MARK_SCALE_ID'] = $course_info['MARK_SCALE_ID'];
			}
		}
		
		$this->poster = new \Kula\Component\Database\Poster($add);
		
		
		$id = $this->poster->getResultForTable('insert', 'STUD_SECTION')[0];
		return $this->forward('sis_offering_sections', array('record_type' => 'SECTION', 'record_id' => $id), array('record_type' => 'SECTION', 'record_id' => $id));
	}
	
	public function deleteAction() {
		$this->authorize();
		$this->setRecordType('SECTION');
		
		$rows_affected = $this->db()->delete('STUD_SECTION')
				->predicate('SECTION_ID', $this->record->getSelectedRecordID())->execute();
		
		if ($rows_affected == 1) {
			$this->flash->add('success', 'Deleted section.');
		}
		
		return $this->forward('sis_offering_sections');
	}
	
	public function inactivateAction() {
		$this->authorize();
		$this->setRecordType('SECTION');
		
		
		if ($this->record->getSelectedRecord()['STATUS'] == 'I') {
			$rows_affected = $this->db()->update('STUD_SECTION')
				->fields(array('STATUS' => null))
					->predicate('SECTION_ID', $this->record->getSelectedRecordID())->execute();
			$success_message = 'Activated section.';
		} else {
			$rows_affected = $this->db()->update('STUD_SECTION')
				->fields(array('STATUS' => 'I'))
					->predicate('SECTION_ID', $this->record->getSelectedRecordID())->execute();
			$success_message = 'Inactivated section.';
		}
		
		if ($rows_affected == 1) {
			$this->flash->add('success', $success_message);
			
			return $this->forward('sis_offering_sections', array('record_type' => 'SECTION', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'SECTION', 'record_id' => $this->record->getSelectedRecordID()));
		}
	}
	
	public function recalculate_section_totalsAction() {
		$this->authorize();
		
		$predicate_or = new \Kula\Component\Database\Query\Predicate('OR');
		$predicate_or = $predicate_or->predicate('DROPPED', null)->predicate('DROPPED', 'N');
		
		// Get Enrolled Totals
		$enrolled_totals = array();
		$enrolled_totals_result = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('SECTION_ID'))
			->expressions(array('COUNT(*)' => 'enrolled_total'))
			->join('STUD_SECTION', 'section', null, 'class.SECTION_ID = section.SECTION_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'section.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
			->predicate('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
			->predicate('orgterms.TERM_ID', $this->focus->getTermID())
			->predicate($predicate_or)
			->group_by('SECTION_ID', 'class')
			->execute();
		while ($enrolled_totals_row = $enrolled_totals_result->fetch()) {
			$enrolled_totals[$enrolled_totals_row['SECTION_ID']] = $enrolled_totals_row['enrolled_total'];
		}
				
		// Get Wait list Totals
		$waitlist_totals = array();
		$waitlist_totals_result = $this->db()->select('STUD_STUDENT_WAIT_LIST', 'waitlist')
			->fields('waitlist', array('SECTION_ID'))
			->expressions(array('COUNT(*)' => 'waitlist_total'))
			->join('STUD_SECTION', 'section', null, 'waitlist.SECTION_ID = section.SECTION_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'section.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
			->predicate('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
			->predicate('orgterms.TERM_ID', $this->focus->getTermID())
			->group_by('SECTION_ID', 'waitlist')
			->execute();
		while ($waitlist_totals_row = $waitlist_totals_result->fetch()) {
			$waitlist_totals[$waitlist_totals_row['SECTION_ID']] = $waitlist_totals_row['waitlist_total'];
		}
		
		// Loop through each section
		$sections_result = $this->db()->select('STUD_SECTION', 'section')
			->fields('section', array('SECTION_ID'))
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'section.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
			->predicate('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
			->predicate('orgterms.TERM_ID', $this->focus->getTermID())
			->execute();
		while ($sections_row = $sections_result->fetch()) {
			
			$section_data = array('STUD_SECTION' => array($sections_row['SECTION_ID'] => array(
				'ENROLLED_TOTAL' => $enrolled_totals[$sections_row['SECTION_ID']] > 0 ? $enrolled_totals[$sections_row['SECTION_ID']] : 0,
				'WAIT_LISTED_TOTAL' => $waitlist_totals[$sections_row['SECTION_ID']] > 0 ? $waitlist_totals[$sections_row['SECTION_ID']] : 0
			)));
			
			
			$poster_obj = new \Kula\Component\Database\Poster(null, $section_data);
			unset($poster_obj);
		}
		
		$this->flash->add('success', 'Recalculated section totals.');
		return $this->forward('sis_offering_sections');
		
	}
	
}