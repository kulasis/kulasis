<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISTransactionsController extends Controller {
  
  public function transactionsAction() {
    $this->authorize();
    $this->setRecordType('STUDENT');
    
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
    
    $transactions = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $transactions = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE', ))
        ->join('BILL_CODE', 'code', array('CODE_TYPE', 'CODE'), 'code.CODE_ID = transactions.CODE_ID')
        ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
        ->predicate('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->predicate('transactions.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
        ->order_by('TRANSACTION_DATE', 'DESC', 'transactions')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdStudentBillingBundle:Transactions:transactions.html.twig', array('transactions' => $transactions));
  }
  
  public function historyAction() {
    $this->authorize();
    $this->setRecordType('STUDENT');
    
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
    
    $transactions = array();
    
    if ($this->record->getSelectedRecordID()) {
      
      $transactions = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE'))
        ->join('BILL_CODE', 'code', array('CODE_TYPE', 'CODE'), 'code.CODE_ID = transactions.CODE_ID')
        ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
        ->predicate('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->order_by('START_DATE', 'DESC', 'term')
        ->order_by('TRANSACTION_DATE', 'DESC', 'transactions')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdStudentBillingBundle:Transactions:transactions.html.twig', array('transactions' => $transactions));
  }

  public function transaction_detailAction($constituent_transaction_id) {
    $this->authorize();
    $this->setRecordType('STUDENT');
    
    $edit_post = $this->request->get('edit');
    
    if (isset($edit_post['BILL_CONSTITUENT_TRANSACTIONS'])) {
      
      // set balance amount
      foreach($edit_post['BILL_CONSTITUENT_TRANSACTIONS'] as $row_id => $row) {
        if (isset($row['AMOUNT']))
          $edit_post['BILL_CONSTITUENT_TRANSACTIONS'][$row_id]['APPLIED_BALANCE'] = $row['AMOUNT'];
      }
      
      $charge_detail_poster = new \Kula\Component\Database\PosterFactory;
      $return_charge_poster = $charge_detail_poster->newPoster(null, array('BILL_CONSTITUENT_TRANSACTIONS' => $edit_post['BILL_CONSTITUENT_TRANSACTIONS']));
    }
    
    $transaction = array();
    $applied_transactions = array();
    $applied_transactions_total = 0;
    
    if ($this->record->getSelectedRecordID()) {
      $transaction = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'CONSTITUENT_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'ORIGINAL_AMOUNT', 'VOIDED', 'VOIDED_REASON', 'APPLIED_BALANCE', 'POSTED', 'CODE_ID', 'VOIDED_TIMESTAMP', 'SHOW_ON_STATEMENT', 'ORGANIZATION_TERM_ID'))
        ->join('BILL_CODE', 'code', array('CODE_TYPE'), 'code.CODE_ID = transactions.CODE_ID')
        ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'transactions.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
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
    
    return $this->render('KulaHEdStudentBillingBundle:Transactions:transactions_detail.html.twig', array('transaction' => $transaction, 'applied_transactions' => $applied_transactions, 'applied_transactions_total' => $applied_transactions_total));
  }
  
  public function add_chargeAction() {
    return $this->add('C');
  }
  
  public function add_paymentAction() {
    return $this->add('P');
  }
  
  public function add($code_type) {
    $this->authorize();
    $this->setRecordType('STUDENT');
    
    if ($this->record->getSelectedRecordID()) {
        
      if ($this->request->request->get('add')) {
        
        $constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
        $add = $this->request->request->get('add');
        $constituent_billing_service->addTransaction($this->record->getSelectedRecord()['STUDENT_ID'], $add['BILL_CONSTITUENT_TRANSACTIONS']['new_num']['ORGANIZATION_TERM_ID'], $add['BILL_CONSTITUENT_TRANSACTIONS']['new_num']['CODE_ID'], $add['BILL_CONSTITUENT_TRANSACTIONS']['new_num']['TRANSACTION_DATE'], $add['BILL_CONSTITUENT_TRANSACTIONS']['new_num']['TRANSACTION_DESCRIPTION'], $add['BILL_CONSTITUENT_TRANSACTIONS']['new_num']['AMOUNT']);
        
        return $this->forward('sis_student_billing_transactions', array('record_type' => 'STUDENT', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'STUDENT', 'record_id' => $this->record->getSelectedRecordID()));
      }
        
    }
    
    return $this->render('KulaHEdStudentBillingBundle:Transactions:transactions_add.html.twig', array('code_type' => $code_type));
  }
  
}