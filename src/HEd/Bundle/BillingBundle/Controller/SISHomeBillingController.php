<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\KulaCoreFrameworkBundle\Controller\Controller;

class SISHomeBillingController extends Controller {
  
  private function _transactionChanges() {
    $this->processForm();
    
    if ($this->request->request->get('void')) {
      $constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
      
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
      $constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
      
      $post = $this->request->request->get('post');
      
      foreach($post as $table => $row_info) {
        foreach($row_info as $row_id => $row) {
          $constituent_billing_service->postTransaction($row_id);
        }
      }
    }
  }
  
  public function pendingAction() {
    
    $this->_transactionChanges();
    
    $query_conditions_or = new \Kula\Component\Database\Query\Predicate('OR');
    $query_conditions_or = $query_conditions_or->predicate('transactions.POSTED', null);
    $query_conditions_or = $query_conditions_or->predicate('transactions.POSTED', 'N');
    
    $transactions = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'CONSTITUENT_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'ORIGINAL_AMOUNT', 'VOIDED', 'VOIDED_REASON', 'APPLIED_BALANCE', 'POSTED', 'CODE_ID', 'VOIDED_TIMESTAMP'))
      ->join('BILL_CODE', 'code', array('CODE_TYPE', 'CODE'), 'code.CODE_ID = transactions.CODE_ID')
      ->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'), 'constituent.CONSTITUENT_ID = transactions.CONSTITUENT_ID')
      ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'transactions.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->left_join('CORE_ORGANIZATION', 'organization', array('ORGANIZATION_ABBREVIATION'), 'orgterms.ORGANIZATION_ID = organization.ORGANIZATION_ID')
      ->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'orgterms.TERM_ID = term.TERM_ID')
      ->left_join('CORE_USER', 'user', array('USERNAME'), 'user.USER_ID = transactions.CREATED_USERSTAMP')
      ->predicate($query_conditions_or)
      ->predicate('transactions.CREATED_USERSTAMP', $this->session->get('user_id'))
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdStudentBillingBundle:HomeBilling:pending.html.twig', array('transactions' => $transactions));
  }
  
  public function all_pendingAction() {
    $this->_transactionChanges();
    
    $query_conditions_or = new \Kula\Component\Database\Query\Predicate('OR');
    $query_conditions_or = $query_conditions_or->predicate('transactions.POSTED', null);
    $query_conditions_or = $query_conditions_or->predicate('transactions.POSTED', 'N');
    
    $transactions = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'CONSTITUENT_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'ORIGINAL_AMOUNT', 'VOIDED', 'VOIDED_REASON', 'APPLIED_BALANCE', 'POSTED', 'CODE_ID', 'VOIDED_TIMESTAMP'))
      ->join('BILL_CODE', 'code', array('CODE_TYPE', 'CODE'), 'code.CODE_ID = transactions.CODE_ID')
      ->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'), 'constituent.CONSTITUENT_ID = transactions.CONSTITUENT_ID')
      ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'transactions.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->left_join('CORE_ORGANIZATION', 'organization', array('ORGANIZATION_ABBREVIATION'), 'orgterms.ORGANIZATION_ID = organization.ORGANIZATION_ID')
      ->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'orgterms.TERM_ID = term.TERM_ID')
      ->left_join('CORE_USER', 'user', array('USERNAME'), 'user.USER_ID = transactions.CREATED_USERSTAMP')
      ->predicate($query_conditions_or)
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdStudentBillingBundle:HomeBilling:pending.html.twig', array('transactions' => $transactions));
  }
  
  public function transaction_detailAction($constituent_transaction_id) {
    $this->authorize();
    
    $edit_post = $this->request->get('edit');
    
    if (isset($edit_post['BILL_CONSTITUENT_CHARGES'])) {
      $charge_detail_poster = new \Kula\Component\Database\PosterFactory;
      $return_charge_poster = $charge_detail_poster->newPoster(null, array('BILL_CONSTITUENT_CHARGES' => $edit_post['BILL_CONSTITUENT_CHARGES']));
    }
    
    $transaction = array();
    $applied_transactions = array();
    $applied_transactions_total = 0;
    
    if ($this->record->getSelectedRecordID()) {
      $transaction = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'CONSTITUENT_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'ORIGINAL_AMOUNT', 'VOIDED', 'VOIDED_REASON', 'APPLIED_BALANCE', 'POSTED', 'CODE_ID', 'VOIDED_TIMESTAMP'))
        ->join('BILL_CODE', 'code', array('CODE_TYPE'), 'code.CODE_ID = transactions.CODE_ID')
        ->left_join('STUD_STUDENT_STATUS', 'status', null, 'status.STUDENT_STATUS_ID = transactions.STUDENT_STATUS_ID')
        ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'status.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
        ->left_join('CORE_ORGANIZATION', 'organization', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = organization.ORGANIZATION_ID')
        ->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'orgterms.TERM_ID = term.TERM_ID')
        ->left_join('CORE_USER', 'user', array('USERNAME'), 'user.USER_ID = transactions.VOIDED_USERSTAMP')
        ->predicate('transactions.CONSTITUENT_TRANSACTION_ID', $constituent_transaction_id)
        ->execute()->fetch();
      
      $query_conditions_or = new \Kula\Component\Database\Query\Predicate('OR');
      $query_conditions_or = $query_conditions_or->predicate('CHARGE_TRANSACTION_ID', $constituent_transaction_id);
      $query_conditions_or = $query_conditions_or->predicate('PAYMENT_TRANSACTION_ID', $constituent_transaction_id);
      
      $applied_transactions = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS_APPLIED')
        ->predicate($query_conditions_or)
        ->execute()->fetchAll();
      
      foreach($applied_transactions as $index => $row) {
        $applied_transactions_total += $row['AMOUNT'];
      }
    }
    return $this->render('KulaHEdStudentBillingBundle:HomeBilling:transactions_detail.html.twig', array('transaction' => $transaction, 'applied_transactions' => $applied_transactions, 'applied_transactions_total' => $applied_transactions_total));
  }
  
}