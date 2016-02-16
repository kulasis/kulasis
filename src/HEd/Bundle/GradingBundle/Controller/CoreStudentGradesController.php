<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreStudentGradesController extends Controller {
  
  public function gradesAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student.Status');
    
    return $this->render('KulaHEdGradingBundle:CoreStudentGrades:grades.html.twig', array());
  }
  
  
}