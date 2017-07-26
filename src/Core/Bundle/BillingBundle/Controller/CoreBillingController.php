<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreBillingController extends Controller {
  
  public function balancesAction() {
    $this->authorize();
    $this->processForm();

    if ($this->request->get('_route') == 'Core_Billing_ConstituentBilling_Balances') {
      $this->setRecordType('Core.Constituent');
    } else {
      $this->setRecordType('Core.HEd.Student');
    }
    
    $transactions = array();
    $terms_with_balances = array();
    $status = array();
    $transactions_total = 0;
    $terms_total = 0;
    
    if ($this->record->getSelectedRecordID()) {
      
      $selected_record = $this->record->getSelectedRecord();
      if (isset($selected_record['STUDENT_STATUS_ID'])) {
      
      $status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'PAYMENT_PLAN', 'TUITION_RATE_ID'))
        ->join('STUD_STUDENT', 'stu', 'stu.STUDENT_ID = stustatus.STUDENT_ID')
        ->fields('stu', array('STUDENT_ID', 'BILLING_NOTES'))
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecord()['STUDENT_STATUS_ID'])
        ->execute()->fetch();
      }
      
      $terms_with_balances = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->expression('SUM(AMOUNT)', 'total_amount')
        ->expression('SUM(APPLIED_BALANCE)', 'total_applied_balance')
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION', 'ORGANIZATION_NAME'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->groupBy('ORGANIZATION_NAME')
        ->groupBy('ORGANIZATION_ABBREVIATION')
        ->groupBy('TERM_ABBREVIATION')
        ->groupBy('START_DATE')
        ->orderBy('term.START_DATE', 'ASC')
        ->execute()->fetchAll();
      
      foreach($terms_with_balances as $term_with_balance) {
        $terms_total = bcadd($term_with_balance['total_amount'], $terms_total, 2);
      }

      $transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'VOIDED', 'APPLIED_BALANCE'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
        ->fields('code', array('CODE', 'CODE_TYPE'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('START_DATE', 'TERM_ABBREVIATION'))
        ->condition('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('transactions.APPLIED_BALANCE', 0, '!=')
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('transactions.TRANSACTION_DATE', 'DESC')
        ->execute()->fetchAll();
    
        foreach($transactions as $transaction) {
          $transactions_total = bcadd($transaction['AMOUNT'], $transactions_total, 2);
        }
        
    }
    
    return $this->render('KulaCoreBillingBundle:CoreBilling:balances.html.twig', array('terms_wtih_balances' => $terms_with_balances, 'transactions' => $transactions, 'transactions_total' => $transactions_total, 'terms_total' => $terms_total, 'status' => $status));
  }
  
}