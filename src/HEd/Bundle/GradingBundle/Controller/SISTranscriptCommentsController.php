<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class TranscriptCommentsController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STUDENT');
		
		$comments = array();
		
		if ($this->record->getSelectedRecordID()) {
		$comments = $this->db()->select('STUD_STUDENT_COURSE_HISTORY_COMMENT')
			->fields(null, array('STUDENT_COURSE_HISTORY_COMMENT_ID', 'STUDENT_ID', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'LEVEL', 'COMMENTS'))
			->predicate('STUDENT_ID', $this->record->getSelectedRecordID())
			->execute()->fetchAll();		
		}

		return $this->render('KulaHEdCourseHistoryBundle:TranscriptComments:comments.html.twig', array('comments' => $comments));	
	}
	
	public function detailAction($id, $sub_id) {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('STUDENT');
		
		$degree = $this->db()->select('STUD_STUDENT_DEGREES')
			->fields(null, array('STUDENT_DEGREE_ID', 'DEGREE_AWARDED', 'GRADUATION_DATE', 'CONFERRED_DATE'))
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
		
		return $this->render('KulaHEdCourseHistoryBundle:TranscriptComments:degrees_detail.html.twig', array('student_degree_id' => $sub_id, 'degree' => $degree, 'majors' => $majors, 'minors' => $minors));		
	}
	
}