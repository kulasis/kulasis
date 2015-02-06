<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISStudentGradesController extends Controller {
  
  public function gradesAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student.Status');
    
    return $this->render('KulaHEdGradingBundle:SISStudentGrades:grades.html.twig', array());
  }
  
  
}