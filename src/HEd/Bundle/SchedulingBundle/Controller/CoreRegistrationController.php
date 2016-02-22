<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreRegistrationController extends Controller {

  public function studentRegistrationAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student');
    
    $reg = array();
    
    if ($this->record->getSelectedRecordID()) {
      // Get Statuses
      $reg = $this->db()->db_select('STUD_STUDENT_REGISTRATION', 'stureg')
        ->fields('stureg', array('REGISTRATION_ID','ORGANIZATION_TERM_ID', 'GRADE', 'LEVEL', 'ENTER_CODE', 'OPEN_REGISTRATION', 'CLOSE_REGISTRATION'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'stureg.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'orgterm.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('CORE_TERM', 'term', 'orgterm.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
        ->orderBy('START_DATE', 'ASC', 'term')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdSchedulingBundle:CoreRegistration:student_registration.html.twig', array('reg' => $reg));
    
  }

}