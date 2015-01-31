<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\KulaCoreFrameworkBundle\Controller\Controller;

class SISBillingController extends Controller {
  
  public function balancesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('STUDENT');
    
    $transactions = array();
    $terms_with_balances = array();
    $status = array();
    $transactions_total = 0;
    $terms_total = 0;
    
    if ($this->record->getSelectedRecordID()) {
      
      $selected_record = $this->record->getSelectedRecord();
      if (isset($selected_record['STUDENT_STATUS_ID'])) {
      
      $status = $this->db()->select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'PAYMENT_PLAN', 'TUITION_RATE_ID'))
        ->join('STUD_STUDENT', 'stu', array('STUDENT_ID', 'BILLING_NOTES'), 'stu.STUDENT_ID = stustatus.STUDENT_ID')
        ->predicate('STUDENT_STATUS_ID', $this->record->getSelectedRecord()['STUDENT_STATUS_ID'])
        ->execute()->fetch();
      }
      
      $terms_with_balances = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array())
        ->expressions(array('SUM(AMOUNT)' => 'total_amount', 'SUM(APPLIED_BALANCE)' => 'total_applied_balance'))
        ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
        ->predicate('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->group_by('ORGANIZATION_NAME')
        ->group_by('TERM_ABBREVIATION')
        ->order_by('START_DATE', 'ASC', 'term')
        ->execute()->fetchAll();
      
      foreach($terms_with_balances as $term_with_balance) {
        $terms_total += $term_with_balance['total_amount'];
      }

      $transactions = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'VOIDED', 'APPLIED_BALANCE'))
        ->join('BILL_CODE', 'code', array('CODE', 'CODE_TYPE'), 'code.CODE_ID = transactions.CODE_ID')
        ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->left_join('CORE_TERM', 'term', array('START_DATE', 'TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
        ->predicate('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->predicate('transactions.APPLIED_BALANCE', 0, '!=')
        ->order_by('START_DATE', 'DESC', 'term')
        ->order_by('TRANSACTION_DATE', 'DESC', 'transactions')
        ->execute()->fetchAll();
    
        foreach($transactions as $transaction) {
          $transactions_total += $transaction['AMOUNT'];
        }
        
    }
    
    return $this->render('KulaHEdStudentBillingBundle:Billing:balances.html.twig', array('terms_wtih_balances' => $terms_with_balances, 'transactions' => $transactions, 'transactions_total' => $transactions_total, 'terms_total' => $terms_total, 'status' => $status));
  }
  
}