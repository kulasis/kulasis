<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISTuitionRatesController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SCHOOL_TERM', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->session->get('organization_ids'),
            'TERM_ID' => $this->session->get('term_id')
           )
         )
    );
    
    $rates = array();
    if ($this->record->getSelectedRecordID()) {
      
      $rates = $this->db()->select('BILL_TUITION_RATE', 'tuitionrates')
        ->predicate('tuitionrates.ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
        ->order_by('TUITION_RATE_NAME')
        ->execute()->fetchAll();
      
    }
    
    
    return $this->render('KulaHEdStudentBillingBundle:TuitionRates:index.html.twig', array('rates'=> $rates));
  }
  
  public function transactionsAction($tuition_rate_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SCHOOL_TERM', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->session->get('organization_ids'),
            'TERM_ID' => $this->session->get('term_id')
           )
         )
    );
    
    $transactions = array();
    if ($this->record->getSelectedRecordID()) {
      $transactions = $this->db()->select('BILL_TUITION_RATE_TRANSACTIONS', 'tuitionratetransactions')
        ->join('BILL_CODE', 'code', null, 'code.CODE_ID = tuitionratetransactions.TRANSACTION_CODE_ID')
        ->predicate('tuitionratetransactions.TUITION_RATE_ID', $tuition_rate_id)
        ->order_by('CODE_DESCRIPTION')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBillingBundle:TuitionRates:transactions.html.twig', array('transactions' => $transactions, 'tuition_rate_id' => $tuition_rate_id));
  }
  
  public function studentsAction($tuition_rate_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SCHOOL_TERM', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->session->get('organization_ids'),
            'TERM_ID' => $this->session->get('term_id')
           )
         )
    );
    
    $students = array();
    if ($this->record->getSelectedRecordID()) {
      $students = $this->db()->select('BILL_TUITION_RATE_STUDENTS', 'tuitionrategrades')
        ->predicate('tuitionrategrades.TUITION_RATE_ID', $tuition_rate_id)
        ->order_by('LEVEL')
        ->order_by('ENTER_CODE')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBillingBundle:TuitionRates:students.html.twig', array('students' => $students, 'tuition_rate_id' => $tuition_rate_id));
  }
  
  public function refundsAction($tuition_rate_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SCHOOL_TERM', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->session->get('organization_ids'),
            'TERM_ID' => $this->session->get('term_id')
           )
         )
    );
    
    $refunds = array();
    if ($this->record->getSelectedRecordID()) {
      $refunds = $this->db()->select('BILL_TUITION_RATE_REFUND', 'tuitionraterefund')
        ->predicate('tuitionraterefund.TUITION_RATE_ID', $tuition_rate_id)
        ->order_by('REFUND_TYPE')
        ->order_by('END_DATE')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBillingBundle:TuitionRates:refunds.html.twig', array('refunds' => $refunds, 'tuition_rate_id' => $tuition_rate_id));
  }
  
}