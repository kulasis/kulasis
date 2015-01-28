<?php

namespace Kula\Bundle\Core\StaffBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class StaffController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STAFF');
		
		$staff = array();
		if ($this->record->getSelectedRecordID()) {
			// Get Staff
			$staff = $this->db()->select('STUD_STAFF', 'staff')
				->fields('staff', array('STAFF_ID', 'ABBREVIATED_NAME', 'CONVERSION'))
				->predicate('staff.STAFF_ID', $this->record->getSelectedRecordID())
				->execute()->fetch();
		}
		
		return $this->render('KulaStaffBundle:Staff:index.html.twig', array('staff' => $staff));
	}
	
	public function staff_orgtermsAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STAFF');
		
		$stafforgterms = $this->db()->select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm')
			->fields('stafforgtrm', array('STAFF_ORGANIZATION_TERM_ID', 'ORGANIZATION_TERM_ID', 'CONVERSION'))
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = stafforgtrm.ORGANIZATION_TERM_ID')
			->join('CORE_TERM', 'term', null, 'orgterms.TERM_ID = term.TERM_ID')
			->predicate('stafforgtrm.STAFF_ID', $this->record->getSelectedRecordID())
			->order_by('START_DATE', 'DESC', 'term')
			->execute()->fetchAll();
		
		return $this->render('KulaStaffBundle:Staff:orgterms.html.twig', array('stafforgterms' => $stafforgterms));	
	}
	
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
		
		return $this->render('schedule.html.twig', array('classes' => $classes));	
	}
	
	public function staff_chooserAction() {
		$this->authorize();
		$data = \Kula\Bundle\Core\StaffBundle\Chooser\StaffChooser::createChooserMenu($this->request->query->get('q'));
		return $this->JSONResponse($data);
	}
	
	public function staff_organizationterm_chooserAction() {
		$this->authorize();
		$data = \Kula\Bundle\Core\StaffBundle\Chooser\StaffOrganizationTermChooser::createChooserMenu($this->request->query->get('q'));
		return $this->JSONResponse($data);
	}
	
	public function addAction() {
		$this->authorize();
		$this->setSubmitMode($this->tpl, 'search');
		
		$constituents = array();
		
		if ($this->request->request->get('add')['STUD_STAFF_ORGANIZATION_TERMS']['new']['STAFF_ID']) {
			$staff_exists = \Kula\Component\Database\DB::connect('read')->select('STUD_STAFF')
				->fields(null, array('STAFF_ID'))
				->predicate('STAFF_ID', $this->request->request->get('add')['STUD_STAFF_ORGANIZATION_TERMS']['new']['STAFF_ID'])
				->execute()->fetch();
			if ($staff_exists['STAFF_ID'] == '') {
				// get staff data
				$staff_addition = $this->request->request->get('add')['STUD_STAFF'];
				$staff_addition['new']['STAFF_ID'] = $this->request->request->get('add')['STUD_STAFF_ORGANIZATION_TERMS']['new']['STAFF_ID'];
				// Post data
				$staff_poster = new \Kula\Component\Database\Poster(array('STUD_STAFF' => $staff_addition));
			}
			// Add organization term staff
			$staff_orgterm_addition = array();
			$staff_orgterm_addition['STAFF_ID'] = $this->request->request->get('add')['STUD_STAFF_ORGANIZATION_TERMS']['new']['STAFF_ID'];
			$staff_orgterm_addition['ORGANIZATION_TERM_ID'] = $this->focus->getOrganizationTermIDs()[0];
			// Post data
			$staff_orgterm_poster = new \Kula\Component\Database\Poster(array('STUD_STAFF_ORGANIZATION_TERMS' => array('new' => $staff_orgterm_addition)));
			$staff_orgterm_id = $staff_orgterm_poster->getResultForTable('insert', 'STUD_STAFF_ORGANIZATION_TERMS')['new'];
			return $this->forward('offering_staff', array('record_type' => 'STAFF', 'record_id' => $staff_orgterm_addition['STAFF_ID']), array('record_type' => 'STAFF', 'record_id' => $staff_orgterm_addition['STAFF_ID']));
		}
		
		if ($this->request->request->get('search')) {
			$query = \Kula\Component\Database\Searcher::prepareSearch($this->request->request->get('search'), 'CONS_CONSTITUENT', 'CONSTITUENT_ID');
			$query = $query->fields('CONS_CONSTITUENT', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'));
			$query = $query->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', null, 'stafforgterm.STAFF_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
			$query = $query->predicate('stafforgterm.STAFF_ORGANIZATION_TERM_ID', null);
			$query = $query->order_by('LAST_NAME', 'ASC');
			$query = $query->order_by('FIRST_NAME', 'ASC');
			$query = $query->range(0, 100);
			$constituents = $query->execute()->fetchAll();
		}
		
		return $this->render('KulaStaffBundle:Staff:add.html.twig', array('constituents' => $constituents));
	}
	
	public function add_constituentAction() {
		$this->authorize();
		$this->formAction('sis_offering_staff_create_constituent');
		return $this->render('KulaStaffBundle:Staff:add_constituent.html.twig');
	}
	
	public function create_constituentAction() {
		$this->authorize();
		$connect = \Kula\Component\Database\DB::connect('write');
		
		$connect->beginTransaction();
		// get constituent data
		$constituent_addition = $this->request->request->get('add')['CONS_CONSTITUENT'];
		// Post data
		$constituent_poster = new \Kula\Component\Database\Poster(array('CONS_CONSTITUENT' => $constituent_addition));
		// Get new constituent ID
		$constituent_id = $constituent_poster->getResultForTable('insert', 'CONS_CONSTITUENT')['new'];
		// get staff data
		$staff_addition = $this->request->request->get('add')['STUD_STAFF'];
		$staff_addition['new']['STAFF_ID'] = $constituent_id;
		// Post data
		$staff_poster = new \Kula\Component\Database\Poster(array('STUD_STAFF' => $staff_addition));
		// Add organization term staff
		$staff_orgterm_addition = array();
		$staff_orgterm_addition['STAFF_ID'] = $constituent_id;
		$staff_orgterm_addition['ORGANIZATION_TERM_ID'] = $this->focus->getOrganizationTermIDs()[0];
		// Post data
		$staff_orgterm_poster = new \Kula\Component\Database\Poster(array('STUD_STAFF_ORGANIZATION_TERMS' => array('new' => $staff_orgterm_addition)));
		$staff_orgterm_id = $staff_orgterm_poster->getResultForTable('insert', 'STUD_STAFF_ORGANIZATION_TERMS')['new'];
		if ($staff_orgterm_id) {
			$connect->commit();
			return $this->forward('sis_offering_staff', array('record_type' => 'STAFF', 'record_id' => $constituent_id), array('record_type' => 'STAFF', 'record_id' => $constituent_id));
		} else {
			$connect->rollback();
			throw new \Kula\Component\Database\PosterFormException('Changes not saved.');	
		}
	}
	
	public function deleteAction() {
		$this->authorize();
		$this->setRecordType('STAFF_SCHOOL_TERM');
		
		$rows_affected = $this->db()->delete('STUD_STAFF_ORGANIZATION_TERMS')
				->predicate('STAFF_ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())->execute();
		
		if ($rows_affected == 1) {
			$this->flash->add('success', 'Deleted staff from organization term.');
		}
		
		return $this->forward('offering_staff');
	}
	
	
}