<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISHoldsSetupController extends Controller {
  
  public function hold_codesAction() {
    $this->authorize();
    $this->processForm();
    
    $hold_codes = $this->db()->db_select('STUD_HOLD')
      ->fields('STUD_HOLD')
      ->orderBy('HOLD_CODE')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdStudentBundle:SISHoldsSetup:hold_codes.html.twig', array('hold_codes' => $hold_codes));
  }
  
}