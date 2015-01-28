<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class DegreeController extends Controller {
	
	public function degreesAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STUDENT');
		
		$degrees = array();
		
		if ($this->record->getSelectedRecordID()) {
		$degrees = $this->db()->select('STUD_STUDENT_DEGREES')
			->fields(null, array('STUDENT_DEGREE_ID', 'EFFECTIVE_DATE', 'DEGREE_ID', 'DEGREE_AWARDED', 'EXPECTED_COMPLETION_TERM_ID', 'GRADUATION_DATE', 'CONFERRED_DATE'))
			->predicate('STUDENT_ID', $this->record->getSelectedRecordID())
			->execute()->fetchAll();		
		}

		return $this->render('KulaHEdCourseHistoryBundle:Degree:degrees.html.twig', array('degrees' => $degrees));	
	}
	
	public function detailAction($id, $sub_id) {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STUDENT');
		
		$degree = $this->db()->select('STUD_STUDENT_DEGREES')
			->fields(null, array('STUDENT_DEGREE_ID', 'DEGREE_AWARDED', 'GRADUATION_DATE', 'CONFERRED_DATE', 'EXPECTED_GRADUATION_DATE'))
			->join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'degree.DEGREE_ID = STUD_STUDENT_DEGREES.DEGREE_ID')
			->predicate('STUDENT_DEGREE_ID', $sub_id)
			->execute()->fetch();
		
		$majors = $this->db()->select('STUD_STUDENT_DEGREES_MAJORS')
			->fields(null, array('STUDENT_MAJOR_ID', 'STUDENT_DEGREE_ID', 'MAJOR_ID'))
			->predicate('STUDENT_DEGREE_ID', $sub_id)
			->execute()->fetchAll();
		
		$minors = $this->db()->select('STUD_STUDENT_DEGREES_MINORS')
			->fields(null, array('STUDENT_MINOR_ID', 'STUDENT_DEGREE_ID', 'MINOR_ID'))
			->predicate('STUDENT_DEGREE_ID', $sub_id)
			->execute()->fetchAll();
		
		$concentrations = $this->db()->select('STUD_STUDENT_DEGREES_CONCENTRATIONS')
			->fields(null, array('STUDENT_CONCENTRATION_ID', 'STUDENT_DEGREE_ID', 'CONCENTRATION_ID'))
			->predicate('STUDENT_DEGREE_ID', $sub_id)
			->execute()->fetchAll();
		
		return $this->render('KulaHEdCourseHistoryBundle:Degree:degrees_detail.html.twig', array('student_degree_id' => $sub_id, 'degree' => $degree, 'majors' => $majors, 'minors' => $minors, 'concentrations' => $concentrations));		
	}
	
}