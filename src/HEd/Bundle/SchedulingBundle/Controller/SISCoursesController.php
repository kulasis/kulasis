<?php

namespace Kula\Bundle\HEd\OfferingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class CoursesController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('COURSE');
		$course = array();
		if ($this->record->getSelectedRecordID()) {
			
			// Get Rooms
			$course = $this->db()->select('STUD_COURSE')
				->predicate('COURSE_ID', $this->record->getSelectedRecordID())
				->execute()->fetch();
		}
		
		return $this->render('KulaHEdOfferingBundle:Courses:index.html.twig', array('course' => $course));
	}
	
	public function prerequisitesAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('COURSE');
		$prerequisites = array();
		if ($this->record->getSelectedRecordID()) {
			
			// Get course info
			$course_info = $this->db()->select('STUD_COURSE')
				->fields(null, array('MARK_SCALE_ID'))
				->predicate('COURSE_ID', $this->record->getSelectedRecordID())
				->execute()->fetch();
			
			// Get Prerequisites
			$prerequisites = $this->db()->select('STUD_COURSE_PREREQUISITES')
				->predicate('COURSE_ID', $this->record->getSelectedRecordID())
				->execute()->fetchAll();
		}
		
		return $this->render('KulaHEdOfferingBundle:Courses:prerequisites.html.twig', array('course_info' => $course_info, 'prerequisites' => $prerequisites));
	}
	
	public function corequisitesAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('COURSE');
		$corequisites = array();
		if ($this->record->getSelectedRecordID()) {
			
			$query_conditions = new \Kula\Component\Database\Query\Predicate('OR');
			$query_conditions = $query_conditions->predicate('COURSE_ID', $this->record->getSelectedRecordID());
			$query_conditions = $query_conditions->predicate('COREQUISITE_COURSE_ID', $this->record->getSelectedRecordID());
			
			// Get Rooms
			$corequisites = $this->db()->select('STUD_COURSE_COREQUISITES')
				->predicate($query_conditions)
				->execute()->fetchAll();
		}
		
		return $this->render('KulaHEdOfferingBundle:Courses:corequisites.html.twig', array('corequisites' => $corequisites));
	}
	
	public function addAction() {
		$this->authorize();
		$this->setRecordType('COURSE', 'Y');
		$this->formAction('sis_offering_courses_create');
		return $this->render('KulaHEdOfferingBundle:Courses:add.html.twig');
	}
	
	public function createAction() {
		$this->authorize();
		$this->processForm();
		$id = $this->poster->getResultForTable('insert', 'STUD_COURSE')[0];
		return $this->forward('sis_offering_courses', array('record_type' => 'COURSE', 'record_id' => $id), array('record_type' => 'COURSE', 'record_id' => $id));
	}
	
	public function deleteAction() {
		$this->authorize();
		$this->setRecordType('COURSE');
		
		$rows_affected = $this->db()->delete('STUD_COURSE')
				->predicate('COURSE_ID', $this->record->getSelectedRecordID())->execute();
		
		if ($rows_affected == 1) {
			$this->flash->add('success', 'Deleted course.');
		}
		
		return $this->forward('sis_offering_courses');
	}
	
	public function chooserAction() {
		$this->authorize();
		$data = \Kula\Bundle\HEd\OfferingBundle\Chooser\CoursesChooser::createChooserMenu($this->request->query->get('q'));
		return $this->JSONResponse($data);
	}

}