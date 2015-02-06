<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISHomeBillingController extends Controller {
  
  private function transactionChanges() {
    $this->processForm();
    
    if ($this->request->request->get('void')) {
      $constituent_billing_service = $this->get('kula.HEd.billing.student');
      
      $void = $this->request->request->get('void');
      $non = $this->request->request->get('non');
        
      if (isset($non['BILL_CONSTITUENT_TRANSACTIONS']['TRANSACTION_DATE']))
        $transaction_date = $non['BILL_CONSTITUENT_TRANSACTIONS']['TRANSACTION_DATE'];
      else 
        $transaction_date = null;
      
      if (isset($non['BILL_CONSTITUENT_TRANSACTIONS']['VOIDED_REASON']))
        $reason = $non['BILL_CONSTITUENT_TRANSACTIONS']['VOIDED_REASON'];
      else 
        $reason = null;
      
      foreach($void as $table => $row_info) {
        foreach($row_info as $row_id => $row) {
          $constituent_billing_service->removeTransaction($row_id, $reason, $transaction_date);
        }
      }
    }
    
    if ($this->request->request->get('post')) {
      $constituent_billing_service = $this->get('kula.HEd.billing.constituent');
      
      $post = $this->request->request->get('post');
      
      foreach($post as $table => $row_info) {
        foreach($row_info as $row_id => $row) {
          $constituent_billing_service->postTransaction($row_id);
        }
      }
    }
  }
  
  public function pendingAction() {
    
    $this->transactionChanges();
    
    $query_conditions_or = $this->db()->db_or();
    $query_conditions_or = $query_conditions_or->condition('transactions.POSTED', null);
    $query_conditions_or = $query_conditions_or->condition('transactions.POSTED', 0);
    
    $transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'CONSTITUENT_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'ORIGINAL_AMOUNT', 'VOIDED', 'VOIDED_REASON', 'APPLIED_BALANCE', 'POSTED', 'CODE_ID', 'VOIDED_TIMESTAMP'))
      ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
      ->fields('code', array('CODE_TYPE', 'CODE'))
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = transactions.CONSTITUENT_ID')
      ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'))
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'transactions.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'organization', 'orgterms.ORGANIZATION_ID = organization.ORGANIZATION_ID')
      ->fields('organization', array('ORGANIZATION_ABBREVIATION'))
      ->leftJoin('CORE_TERM', 'term', 'orgterms.TERM_ID = term.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->leftJoin('CORE_USER', 'user', 'user.USER_ID = transactions.CREATED_USERSTAMP')
      ->fields('user', array('USERNAME'))
      ->condition($query_conditions_or)
      ->condition('transactions.CREATED_USERSTAMP', $this->session->get('user_id'))
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdBillingBundle:SISHomeBilling:pending.html.twig', array('transactions' => $transactions));
  }
  
  public function all_pendingAction() {
    $this->transactionChanges();
    
    $query_conditions_or = $this->db()->db_or();
    $query_conditions_or = $query_conditions_or->condition('transactions.POSTED', null);
    $query_conditions_or = $query_conditions_or->condition('transactions.POSTED', 0);
    
    $transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'CONSTITUENT_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'ORIGINAL_AMOUNT', 'VOIDED', 'VOIDED_REASON', 'APPLIED_BALANCE', 'POSTED', 'CODE_ID', 'VOIDED_TIMESTAMP'))
      ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
      ->fields('code', array('CODE_TYPE', 'CODE'))
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = transactions.CONSTITUENT_ID')
      ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'))
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'transactions.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'organization', 'orgterms.ORGANIZATION_ID = organization.ORGANIZATION_ID')
      ->fields('organization', array('ORGANIZATION_ABBREVIATION'))
      ->leftJoin('CORE_TERM', 'term', 'orgterms.TERM_ID = term.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->leftJoin('CORE_USER', 'user', 'user.USER_ID = transactions.CREATED_USERSTAMP')
      ->fields('user', array('USERNAME'))
      ->condition($query_conditions_or)
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdBillingBundle:SISHomeBilling:pending.html.twig', array('transactions' => $transactions));
  }
  
  public function transaction_detailAction($constituent_transaction_id) {
    $this->authorize();
    
    $transaction = array();
    $applied_transactions = array();
    $applied_transactions_total = 0;
    
    if ($this->record->getSelectedRecordID()) {
      $transaction = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'CONSTITUENT_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'ORIGINAL_AMOUNT', 'VOIDED', 'VOIDED_REASON', 'APPLIED_BALANCE', 'POSTED', 'CODE_ID', 'VOIDED_TIMESTAMP'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
        ->fields('code', array('CODE_TYPE'))
        ->leftJoin('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = transactions.STUDENT_STATUS_ID')
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'status.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'organization', 'orgterms.ORGANIZATION_ID = organization.ORGANIZATION_ID')
        ->fields('organization', array('ORGANIZATION_NAME'))
        ->leftJoin('CORE_TERM', 'term', 'orgterms.TERM_ID = term.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->leftJoin('CORE_USER', 'user', 'user.USER_ID = transactions.VOIDED_USERSTAMP')
        ->fields('user', array('USERNAME'))
        ->condition('transactions.CONSTITUENT_TRANSACTION_ID', $constituent_transaction_id)
        ->execute()->fetch();
      
      $query_conditions_or = $this->db()->db_or();
      $query_conditions_or = $query_conditions_or->condition('CHARGE_TRANSACTION_ID', $constituent_transaction_id);
      $query_conditions_or = $query_conditions_or->condition('PAYMENT_TRANSACTION_ID', $constituent_transaction_id);
      
      $applied_transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS_APPLIED')
        ->condition($query_conditions_or)
        ->execute()->fetchAll();
      
      foreach($applied_transactions as $index => $row) {
        $applied_transactions_total += $row['AMOUNT'];
      }
    }
    return $this->render('KulaHEdBillingBundle:SISHomeBilling:transactions_detail.html.twig', array('transaction' => $transaction, 'applied_transactions' => $applied_transactions, 'applied_transactions_total' => $applied_transactions_total));
  }
  
}