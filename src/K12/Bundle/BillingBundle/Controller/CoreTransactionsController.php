<?php

namespace Kula\K12\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreTransactionsController extends Controller {
  
  public function transactionsAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Student');
    
    if ($this->request->request->get('void')) {
      $constituent_billing_service = $this->get('kula.K12.billing.constituent');
      
      $void = $this->request->request->get('void');
      $non = $this->request->request->get('non');
        
      if (isset($non['K12.Billing.Transaction']['K12.Billing.Transaction.TransactionDate']))
        $transaction_date = $non['K12.Billing.Transaction']['K12.Billing.Transaction.TransactionDate'];
      else 
        $transaction_date = null;
      
      if (isset($non['K12.Billing.Transaction']['K12.Billing.Transaction.VoidedReason']))
        $reason = $non['K12.Billing.Transaction']['K12.Billing.Transaction.VoidedReason'];
      else 
        $reason = null;
      
      foreach($void as $table => $row_info) {
        foreach($row_info as $row_id => $row) {
          $constituent_billing_service->removeTransaction($row_id, $reason, $transaction_date);
        }
      }
    }
    
    $transactions = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE', ))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
        ->fields('code', array('CODE_TYPE', 'CODE'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('transactions.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
        ->orderBy('TRANSACTION_DATE', 'DESC', 'transactions')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaK12BillingBundle:CoreTransactions:transactions.html.twig', array('transactions' => $transactions));
  }
  
  public function historyAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Student');
    
    if ($this->request->request->get('void')) {
      $constituent_billing_service = $this->get('kula.K12.billing.constituent');
      
      $void = $this->request->request->get('void');
      $non = $this->request->request->get('non');
        
      if (isset($non['K12.Billing.Transaction']['TransactionDate']))
        $transaction_date = $non['K12.Billing.Transaction']['K12.Billing.Transaction.TransactionDate'];
      else 
        $transaction_date = null;
      
      if (isset($non['K12.Billing.Transaction']['K12.Billing.Transaction.VoidedReason']))
        $reason = $non['K12.Billing.Transaction']['K12.Billing.Transaction.VoidedReason'];
      else 
        $reason = null;
      
      foreach($void as $table => $row_info) {
        foreach($row_info as $row_id => $row) {
          $constituent_billing_service->removeTransaction($row_id, $reason, $transaction_date);
        }
      }
    }
    
    $transactions = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $transactions = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
        ->fields('code', array('CODE_TYPE', 'CODE'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('transactions.TRANSACTION_DATE', 'DESC')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaK12BillingBundle:CoreTransactions:transactions.html.twig', array('transactions' => $transactions));
  }

  public function transaction_detailAction($constituent_transaction_id) {
    $this->authorize();
    $this->setRecordType('Core.K12.Student');
    $this->processForm();

    $edit_post = $this->request->get('edit');
    
    if (isset($edit_post['K12.Billing.Transaction'])) {
      // set balance amount
      foreach($edit_post['K12.Billing.Transaction'] as $row_id => $row) {
        if (isset($row['K12.Billing.Transaction.Amount'])) {
          $charge_detail_poster = $this->newPoster()->edit('K12.Billing.Transaction', $row_id, array(
            'K12.Billing.Transaction.AppliedBalance' => $row['K12.Billing.Transaction.Amount']
          ))->process();
        }
      }
    }
    
    $transaction = array();
    $applied_transactions = array();
    $applied_transactions_total = 0;
    
    if ($this->record->getSelectedRecordID()) {
      $transaction = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'CONSTITUENT_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'ORIGINAL_AMOUNT', 'VOIDED', 'VOIDED_REASON', 'APPLIED_BALANCE', 'POSTED', 'CODE_ID', 'VOIDED_TIMESTAMP', 'SHOW_ON_STATEMENT', 'ORGANIZATION_TERM_ID'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = transactions.CODE_ID')
        ->fields('code', array('CODE_TYPE'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'transactions.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
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
      /*
      $applied_transactions = $this->db()->db_select('K12.Billing.Transaction_APPLIED')
        ->condition($query_conditions_or)
        ->execute()->fetchAll();
      
      foreach($applied_transactions as $index => $row) {
        $applied_transactions_total += $row['AMOUNT'];
      }
      */
    }
    
    return $this->render('KulaK12BillingBundle:CoreTransactions:transactions_detail.html.twig', array('transaction' => $transaction, 'applied_transactions' => $applied_transactions, 'applied_transactions_total' => $applied_transactions_total));
  }
  
  public function add_chargeAction() {
    return $this->add('C');
  }
  
  public function add_paymentAction() {
    return $this->add('P');
  }
  
  public function add($code_type) {
    $this->authorize();
    $this->setRecordType('Core.K12.Student');
    
    if ($this->record->getSelectedRecordID()) {
        
      if ($this->request->request->get('add')) {
        
        $constituent_billing_service = $this->get('kula.K12.billing.constituent');
        $add = $this->request->request->get('add');
        $constituent_billing_service->addTransaction($this->record->getSelectedRecord()['STUDENT_ID'], $add['K12.Billing.Transaction']['new_num']['K12.Billing.Transaction.OrganizationTermID'], $add['K12.Billing.Transaction']['new_num']['K12.Billing.Transaction.CodeID'], $add['K12.Billing.Transaction']['new_num']['K12.Billing.Transaction.TransactionDate'], $add['K12.Billing.Transaction']['new_num']['K12.Billing.Transaction.Description'], $add['K12.Billing.Transaction']['new_num']['K12.Billing.Transaction.Amount']);
        
        return $this->forward('core_K12_student_billing_transactions', array('record_type' => 'Core.K12.Student', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.K12.Student', 'record_id' => $this->record->getSelectedRecordID()));
      }
        
    }
    
    return $this->render('KulaK12BillingBundle:CoreTransactions:transactions_add.html.twig', array('code_type' => $code_type));
  }
  
}