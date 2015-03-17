<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISCourseFeesController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Course');
    
    $fees = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      // Get Rooms
      $fees = $this->db()->db_select('BILL_COURSE_FEE', 'BILL_COURSE_FEE')
        ->fields('BILL_COURSE_FEE', array('AMOUNT', 'CODE_ID', 'COURSE_FEE_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = BILL_COURSE_FEE.ORGANIZATION_TERM_ID')
        ->join('BILL_CODE', 'code', 'code.CODE_ID = BILL_COURSE_FEE.CODE_ID')
        ->fields('code', array('CODE_DESCRIPTION'))
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->condition('COURSE_ID', $this->record->getSelectedRecordID())
        ->condition('orgterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermID())
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('CODE_DESCRIPTION', 'ASC')
      ->execute()->fetchAll();
    }
    
    // Get organization term and name for adding
    $org_term = $this->db()->db_select('CORE_ORGANIZATION_TERMS', 'orgterms')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'orgterms.TERM_ID = term.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermID())
      ->execute()->fetch();
    
    return $this->render('KulaHEdBillingBundle:SISCourseFees:fees_index.html.twig', array('fees' => $fees, 'organization_term_id' => $this->focus->getOrganizationTermID(), 'organization_term_id_display' => $org_term['TERM_ABBREVIATION'] . ' / ' . $org_term['ORGANIZATION_NAME']));
    
  }
  
  public function sectionFeesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Section');
    
    $fees = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $fees = $this->db()->db_select('BILL_SECTION_FEE', 'BILL_SECTION_FEE')
        ->fields('BILL_SECTION_FEE', array('AMOUNT', 'CODE_ID', 'SECTION_FEE_ID'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = BILL_SECTION_FEE.CODE_ID')
        ->fields('code', array('CODE_DESCRIPTION'))
        ->condition('SECTION_ID', $this->record->getSelectedRecordID())
        ->orderBy('CODE_DESCRIPTION', 'ASC')
      ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdBillingBundle:SISCourseFees:section.html.twig', array('fees' => $fees));
    
  }

}