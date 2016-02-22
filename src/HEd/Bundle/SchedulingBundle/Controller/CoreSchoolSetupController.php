<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreSchoolSetupController extends Controller {
  
  
  public function registrationAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->focus->getSchoolIDs(),
            'TERM_ID' => $this->focus->getTermID()
           )
         )
    );
    
    $reg = $this->db()->db_select('STUD_SCHOOL_TERM', 'schlterm')
      ->fields('schlterm')
      ->condition('schlterm.SCHOOL_TERM_ID', $this->record->getSelectedRecordID())
      ->execute()->fetch();
    
    $gradelevels = array();
    if ($this->record->getSelectedRecordID()) {
      
      $gradelevels = $this->db()->db_select('STUD_SCHOOL_TERM_REG_GRADE_LEVEL', 'school_term_grade_level')
        ->fields('school_term_grade_level')
        ->condition('school_term_grade_level.ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
        ->orderBy('LEVEL')
        ->orderBy('GRADE')
        ->orderBy('MIN_HOURS')
        ->execute()->fetchAll();
      
    }
        
    return $this->render('KulaHEdSchedulingBundle:CoreSchoolSetup:RegistrationGradeLevels.html.twig', array('gradelevels' => $gradelevels, 'reg' => $reg));
    
  }
  
}