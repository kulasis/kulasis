<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class TeacherSectionController extends Controller {
	
	public function rosterAction() {
		$this->authorize();
		$this->setRecordType('SECTION');
		
		$dropped_conditions = new \Kula\Component\Database\Query\Predicate('OR');
		$dropped_conditions = $dropped_conditions->predicate('class.DROPPED', null);
		$dropped_conditions = $dropped_conditions->predicate('class.DROPPED', 'N');
		
		$students = array();
		
		$students = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED'))
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID', 'LEVEL', 'GRADE', 'ENTER_CODE'), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
			->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
			->join('CONS_CONSTITUENT', 'constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
			->left_join('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentrations', array('CONCENTRATION_ID'), 'stuconcentrations.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
			->predicate('class.SECTION_ID', $this->record->getSelectedRecordID())
			->predicate($dropped_conditions)
			->order_by('DROPPED', 'ASC')
			->order_by('LAST_NAME', 'ASC')
			->order_by('FIRST_NAME', 'ASC')
			->execute()->fetchAll();
		
		return $this->render('KulaHEdSchedulingBundle:TeacherSection:roster.html.twig', array('students' => $students));
	}

	public function dropped_rosterAction() {
		$this->authorize();
		$this->setRecordType('SECTION');
		
		$students = array();
		
		$students = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
			->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED'))
			->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID', 'LEVEL', 'GRADE', 'ENTER_CODE'), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
			->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
			->join('CONS_CONSTITUENT', 'constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
			->left_join('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentrations', array('CONCENTRATION_ID'), 'stuconcentrations.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
			->predicate('class.SECTION_ID', $this->record->getSelectedRecordID())
			->predicate('class.DROPPED', 'Y')
			->order_by('DROPPED', 'ASC')
			->order_by('LAST_NAME', 'ASC')
			->order_by('FIRST_NAME', 'ASC')
			->execute()->fetchAll();
		
		return $this->render('KulaHEdSchedulingBundle:TeacherSection:roster.html.twig', array('students' => $students));
	}

}