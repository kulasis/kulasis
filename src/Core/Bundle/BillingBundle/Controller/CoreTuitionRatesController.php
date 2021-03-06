<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreTuitionRatesController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
    
    $rates = array();
    if ($this->record->getSelectedRecordID()) {
      
      $rates = $this->db()->db_select('BILL_TUITION_RATE', 'tuitionrates')
        ->fields('tuitionrates')
        ->condition('tuitionrates.ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
        ->orderBy('TUITION_RATE_NAME')
        ->execute()->fetchAll();
      
    }
    
    
    return $this->render('KulaCoreBillingBundle:CoreTuitionRates:index.html.twig', array('rates'=> $rates));
  }
  
  public function transactionsAction($tuition_rate_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
    
    $transactions = array();
    if ($this->record->getSelectedRecordID()) {
      $transactions = $this->db()->db_select('BILL_TUITION_RATE_TRANSACTIONS', 'tuitionratetransactions')
        ->fields('tuitionratetransactions')
        ->join('BILL_CODE', 'code', 'code.CODE_ID = tuitionratetransactions.TRANSACTION_CODE_ID')
        ->condition('tuitionratetransactions.TUITION_RATE_ID', $tuition_rate_id)
        ->orderBy('CODE_DESCRIPTION')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreBillingBundle:CoreTuitionRates:transactions.html.twig', array('transactions' => $transactions, 'tuition_rate_id' => $tuition_rate_id));
  }
  
  public function studentsAction($tuition_rate_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
    
    $students = array();
    $degrees = array();
    if ($this->record->getSelectedRecordID()) {
      $students = $this->db()->db_select('BILL_TUITION_RATE_STUDENTS', 'tuitionrategrades')
        ->fields('tuitionrategrades')
        ->condition('tuitionrategrades.TUITION_RATE_ID', $tuition_rate_id)
        ->condition('tuitionrategrades.DEGREE_ID', null)
        ->orderBy('LEVEL')
        ->orderBy('ENTER_CODE')
        ->execute()->fetchAll();
      
      $degrees = $this->db()->db_select('BILL_TUITION_RATE_STUDENTS', 'tuitionrategrades')
        ->fields('tuitionrategrades')
        ->condition('tuitionrategrades.TUITION_RATE_ID', $tuition_rate_id)
        ->isNotNull('tuitionrategrades.DEGREE_ID')
        ->join('STUD_DEGREE', 'degree', 'degree.DEGREE_ID = tuitionrategrades.DEGREE_ID')
        ->orderBy('DEGREE_NAME')
        ->execute()->fetchAll();
    }
    return $this->render('KulaCoreBillingBundle:CoreTuitionRates:students.html.twig', array('students' => $students, 'tuition_rate_id' => $tuition_rate_id, 'degrees' => $degrees));
  }
  
  public function refundsAction($tuition_rate_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
    
    $refunds = array();
    if ($this->record->getSelectedRecordID()) {
      $refunds = $this->db()->db_select('BILL_TUITION_RATE_REFUND', 'tuitionraterefund')
        ->fields('tuitionraterefund')
        ->condition('tuitionraterefund.TUITION_RATE_ID', $tuition_rate_id)
        ->orderBy('REFUND_TYPE')
        ->orderBy('END_DATE')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreBillingBundle:CoreTuitionRates:refunds.html.twig', array('refunds' => $refunds, 'tuition_rate_id' => $tuition_rate_id));
  }
  
  public function transactionRefundsAction($tuition_rate_id, $transaction_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
    
    $refunds = array();
    if ($this->record->getSelectedRecordID()) {
      $refunds = $this->db()->db_select('BILL_TUITION_RATE_TRANS_REFUND', 'tuitionraterefund')
        ->fields('tuitionraterefund')
        ->condition('tuitionraterefund.TUITION_RATE_TRANSACTION_ID', $transaction_id)
        ->orderBy('END_DATE')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaCoreBillingBundle:CoreTuitionRates:trans_refunds.html.twig', array('refunds' => $refunds, 'transaction_id' => $transaction_id, 'tuition_rate_id' => $tuition_rate_id));
  }
  
}