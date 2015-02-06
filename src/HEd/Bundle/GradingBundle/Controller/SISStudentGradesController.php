<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISStudentGradesController extends Controller {
  
  public function gradesAction() {
    $this->authorize();
    $this->setRecordType('STUDENT_STATUS');
    
    return $this->render('KulaHEdCourseHistoryBundle:StudentGrades:grades.html.twig', array());
  }
  
  
}