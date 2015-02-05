<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISTuitionRatesController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.Organization.School.Term', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->session->get('organization_ids'),
            'TERM_ID' => $this->session->get('term_id')
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
    
    
    return $this->render('KulaHEdBillingBundle:SISTuitionRates:index.html.twig', array('rates'=> $rates));
  }
  
  public function transactionsAction($tuition_rate_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.Organization.School.Term', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->session->get('organization_ids'),
            'TERM_ID' => $this->session->get('term_id')
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
    
    return $this->render('KulaHEdBillingBundle:SISTuitionRates:transactions.html.twig', array('transactions' => $transactions, 'tuition_rate_id' => $tuition_rate_id));
  }
  
  public function studentsAction($tuition_rate_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.Organization.School.Term', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->session->get('organization_ids'),
            'TERM_ID' => $this->session->get('term_id')
           )
         )
    );
    
    $students = array();
    if ($this->record->getSelectedRecordID()) {
      $students = $this->db()->db_select('BILL_TUITION_RATE_STUDENTS', 'tuitionrategrades')
        ->fields('tuitionrategrades')
        ->condition('tuitionrategrades.TUITION_RATE_ID', $tuition_rate_id)
        ->orderBy('LEVEL')
        ->orderBy('ENTER_CODE')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdBillingBundle:SISTuitionRates:students.html.twig', array('students' => $students, 'tuition_rate_id' => $tuition_rate_id));
  }
  
  public function refundsAction($tuition_rate_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.Organization.School.Term', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->session->get('organization_ids'),
            'TERM_ID' => $this->session->get('term_id')
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
    
    return $this->render('KulaHEdBillingBundle:SISTuitionRates:refunds.html.twig', array('refunds' => $refunds, 'tuition_rate_id' => $tuition_rate_id));
  }
  
}