<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreCourseFeesController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Course');
    
    $fees = array();
    $refund_fees = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      // Get Course Fees
      $fees = $this->db()->db_select('BILL_COURSE_FEE', 'BILL_COURSE_FEE')
        ->fields('BILL_COURSE_FEE', array('AMOUNT', 'LEVEL', 'CODE_ID', 'COURSE_FEE_ID'))
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
        ->orderBy('CODE', 'ASC')
      ->execute()->fetchAll();
    
      $refund_fees = $this->db()->db_select('BILL_COURSE_FEE_REFUND', 'BILL_COURSE_FEE_REFUND')
        ->fields('BILL_COURSE_FEE_REFUND', array('AMOUNT', 'LEVEL', 'CODE_ID', 'END_DATE', 'COURSE_FEE_REFUND_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = BILL_COURSE_FEE_REFUND.ORGANIZATION_TERM_ID')
        ->join('BILL_CODE', 'code', 'code.CODE_ID = BILL_COURSE_FEE_REFUND.CODE_ID')
        ->fields('code', array('CODE_DESCRIPTION'))
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->condition('COURSE_ID', $this->record->getSelectedRecordID())
        ->condition('orgterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermID())
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('BILL_COURSE_FEE_REFUND.END_DATE', 'ASC')
        ->orderBy('CODE', 'ASC')
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
    
    return $this->render('KulaHEdBillingBundle:CoreCourseFees:fees_index.html.twig', array('fees' => $fees, 'refund_fees' => $refund_fees, 'organization_term_id' => $this->focus->getOrganizationTermID(), 'organization_term_id_display' => $org_term['TERM_ABBREVIATION'] . ' / ' . $org_term['ORGANIZATION_NAME']));
    
  }
  
  public function sectionFeesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Section');
    
    $fees = array();
    $refund_fees = array();
    $course_fees = array();
    $course_refund_fees = array();
    $discount_fees = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $fees = $this->db()->db_select('BILL_SECTION_FEE', 'BILL_SECTION_FEE')
        ->fields('BILL_SECTION_FEE', array('AMOUNT', 'LEVEL', 'CODE_ID', 'SECTION_FEE_ID'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = BILL_SECTION_FEE.CODE_ID')
        ->fields('code', array('CODE_DESCRIPTION'))
        ->condition('SECTION_ID', $this->record->getSelectedRecordID())
        ->orderBy('CODE', 'ASC')
      ->execute()->fetchAll();
      
      $course_fees = $this->db()->db_select('BILL_COURSE_FEE', 'BILL_COURSE_FEE')
        ->fields('BILL_COURSE_FEE', array('AMOUNT', 'LEVEL', 'CODE_ID', 'COURSE_FEE_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = BILL_COURSE_FEE.ORGANIZATION_TERM_ID')
        ->join('BILL_CODE', 'code', 'code.CODE_ID = BILL_COURSE_FEE.CODE_ID')
        ->fields('code', array('CODE_DESCRIPTION'))
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('STUD_SECTION', 'sec', 'sec.COURSE_ID = BILL_COURSE_FEE.COURSE_ID')
        ->condition('sec.SECTION_ID', $this->record->getSelectedRecordID())
        ->condition('orgterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermID())
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('CODE', 'ASC')
      ->execute()->fetchAll();
      
      $course_refund_fees = $this->db()->db_select('BILL_COURSE_FEE_REFUND', 'BILL_COURSE_FEE_REFUND')
        ->fields('BILL_COURSE_FEE_REFUND', array('AMOUNT', 'LEVEL', 'CODE_ID', 'END_DATE', 'COURSE_FEE_REFUND_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = BILL_COURSE_FEE_REFUND.ORGANIZATION_TERM_ID')
        ->join('BILL_CODE', 'code', 'code.CODE_ID = BILL_COURSE_FEE_REFUND.CODE_ID')
        ->fields('code', array('CODE_DESCRIPTION'))
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->join('STUD_SECTION', 'sec', 'sec.COURSE_ID = BILL_COURSE_FEE_REFUND.COURSE_ID')
        ->condition('sec.SECTION_ID', $this->record->getSelectedRecordID())
        ->condition('orgterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermID())
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('BILL_COURSE_FEE_REFUND.END_DATE', 'ASC')
        ->orderBy('CODE', 'ASC')
      ->execute()->fetchAll();
      
      $refund_fees = $this->db()->db_select('BILL_SECTION_FEE_REFUND', 'BILL_SECTION_FEE')
        ->fields('BILL_SECTION_FEE', array('END_DATE', 'AMOUNT', 'LEVEL', 'CODE_ID', 'SECTION_FEE_REFUND_ID'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = BILL_SECTION_FEE.CODE_ID')
        ->fields('code', array('CODE_DESCRIPTION'))
        ->condition('SECTION_ID', $this->record->getSelectedRecordID())
        ->orderBy('BILL_SECTION_FEE.END_DATE', 'ASC')
        ->orderBy('CODE', 'ASC')
      ->execute()->fetchAll();

      $discount_fees = $this->db()->db_select('BILL_SECTION_FEE_DISCOUNT', 'discount')
        ->fields('discount', array('SECTION_FEE_DISCOUNT_ID', 'AMOUNT', 'END_DATE', 'DISCOUNT', 'CODE_ID'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = discount.CODE_ID')
        ->fields('code', array('CODE_DESCRIPTION'))
        ->condition('SECTION_ID', $this->record->getSelectedRecordID())
        ->orderBy('END_DATE', 'ASC')
        ->orderBy('DISCOUNT', 'ASC')
        ->orderBy('CODE', 'ASC')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaCoreBillingBundle:CoreCourseFees:section.html.twig', array('fees' => $fees, 'course_fees' => $course_fees, 'course_refund_fees' => $course_refund_fees, 'refund_fees' => $refund_fees, 'discount_fees' => $discount_fees));
    
  }

}