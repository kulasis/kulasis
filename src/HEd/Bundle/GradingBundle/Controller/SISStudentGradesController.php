<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class StudentGradesController extends Controller {
	
	public function gradesAction() {
		$this->authorize();
		$this->setRecordType('STUDENT_STATUS');
		
		return $this->render('KulaHEdCourseHistoryBundle:StudentGrades:grades.html.twig', array());
	}
	
	
}