<?php

namespace Kula\HEd\Bundle\SchoolBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreSchoolSetupController extends Controller {
  
  public function generalAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
      
    $schoolterm = array();
    $school = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $school = $this->db()->db_select('STUD_SCHOOL', 'school')
        ->fields('school')
        ->condition('SCHOOL_ID', $this->focus->getSchoolIDs())
        ->execute()
        ->fetch();

      $schoolterm = $this->db()->db_select('STUD_SCHOOL_TERM', 'schoolterm')
        ->fields('schoolterm')
        ->condition('SCHOOL_TERM_ID', $this->record->getSelectedRecordID())
        ->execute()
        ->fetch();
      
      if ($school['SCHOOL_ID'] == null) {
        $this->newPoster()->add('HEd.School', 'new', array('HEd.School.ID' => $this->focus->getSchoolIDs()))->process();
      }
      
      if ($schoolterm['SCHOOL_TERM_ID'] == null) {
        $this->newPoster()->add('HEd.School.Term', 'new', array('HEd.School.Term.ID' => $this->record->getSelectedRecordID()))->process();
      }
    }
    
    return $this->render('KulaHEdSchoolBundle:CoreSchoolSetup:general.html.twig', array('schoolterm' => $schoolterm, 'school' => $school));
    
  }
  
  public function levelsAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
    
    $levels = array();
    if ($this->record->getSelectedRecordID()) {
      
      $levels = $this->db()->db_select('STUD_SCHOOL_TERM_LEVEL', 'school_term_level')
        ->fields('school_term_level')
        ->condition('school_term_level.ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
        ->orderBy('level')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdSchoolBundle:CoreSchoolSetup:levels.html.twig', array('levels' => $levels));
    
  }
  
  public function gradelevelsAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
     
    $gradelevels = array();
    if ($this->record->getSelectedRecordID()) {
      
      $gradelevels = $this->db()->db_select('STUD_SCHOOL_TERM_GRADE_LEVEL', 'school_term_grade_level')
        ->fields('school_term_grade_level')
        ->condition('school_term_grade_level.ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
        ->orderBy('GRADE')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdSchoolBundle:CoreSchoolSetup:gradelevels.html.twig', array('gradelevels' => $gradelevels));
    
  }
  
  public function fteAction($level_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
    
    $fte = array();
    
    $fte = $this->db()->db_select('STUD_SCHOOL_TERM_LEVEL_FTE', 'school_term_level_fte')
        ->fields('school_term_level_fte')
        ->condition('school_term_level_fte.SCHOOL_TERM_LEVEL_ID', $level_id)
        ->orderBy('FTE')
        ->orderBy('CREDIT_TOTAL')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchoolBundle:CoreSchoolSetup:fte.html.twig', array('fte' => $fte, 'school_term_level_id' => $level_id));
  }
  
}