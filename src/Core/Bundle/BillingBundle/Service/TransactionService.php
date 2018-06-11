<?php

namespace Kula\Core\Bundle\BillingBundle\Service;

use Kula\Core\Bundle\FrameworkBundle\Exception\DisplayException;

class TransactionService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;
  
  protected $session;
  
  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory) {
    $this->database = $db;
    $this->posterFactory = $poster_factory;
    $this->db_options = array();
  }

  public function setDBOptions($options = array()) {
    $this->db_options = $options;
  }

  public function addDiscount($discount_id, $constituent_id, $organization_term_id, $class_id, $payment_id) {

    // Get discount code
    $discount_info = $this->database->db_select('BILL_SECTION_FEE_DISCOUNT', 'disc')
      ->fields('disc', array('SECTION_ID', 'CODE_ID', 'AMOUNT'))
      ->condition('disc.SECTION_FEE_DISCOUNT_ID', $discount_id)
      ->execute()->fetch();

    // Add transaction
    return $this->addTransaction($constituent_id, $organization_term_id, $discount_info['CODE_ID'], date('Y-m-d'), null, $discount_info['AMOUNT'] * -1, $payment_id, $class_id);

  }

  public function addTransaction($constituent_id, $organization_term_id, $transaction_code_id, $transaction_date, $transaction_description, $amount, $payment_id = null, $class_id = null, $refund = false) {
    
    // Get transaction code
    $transaction_code = $this->database->db_select('BILL_CODE', 'code')
      ->fields('code', array('CODE_TYPE', 'CODE_DESCRIPTION'))
      ->condition('code.CODE_ID', $transaction_code_id)
      ->execute()->fetch();
    if ($transaction_description) {
      $formatted_transaction_description = $transaction_code['CODE_DESCRIPTION'].' - '.$transaction_description;
    } else {
      $formatted_transaction_description = $transaction_code['CODE_DESCRIPTION'];
    }
    
    if ($transaction_code['CODE_TYPE'] == 'P' AND $refund === false)
      $amount = $amount * -1;
    if ($transaction_code['CODE_TYPE'] == 'P' AND $refund === true AND $amount < -1)
      $amount = $amount * -1;
    
    // Prepare & post payment data    
    return $this->posterFactory->newPoster()->add('Core.Billing.Transaction', 'new', array(
      'Core.Billing.Transaction.ConstituentID' => $constituent_id,
      'Core.Billing.Transaction.OrganizationTermID' => $organization_term_id,
      'Core.Billing.Transaction.CodeID' => $transaction_code_id,
      'Core.Billing.Transaction.TransactionDate' => $transaction_date,
      'Core.Billing.Transaction.Description' => $formatted_transaction_description,
      'Core.Billing.Transaction.Amount' => $amount, 
      'Core.Billing.Transaction.OriginalAmount' => $amount,
      'Core.Billing.Transaction.AppliedBalance' => $amount,
      'Core.Billing.Transaction.Posted' => 0,
      'Core.Billing.Transaction.PaymentID' => $payment_id,
      'Core.Billing.Transaction.StudentClassID' => $class_id
    ))->process($this->db_options)->getResult();
  }

  public function postTransaction($constituent_transaction_id) {
    return $this->posterFactory->newPoster()->edit('Core.Billing.Transaction', $constituent_transaction_id, array(
      'Core.Billing.Transaction.Posted' => 1,
      'Core.Billing.Transaction.ShowOnStatement' => 1
    ))->process($this->db_options)->getResult();
  }
  
  public function removeTransaction($constituent_transaction_id, $voided_reason, $transaction_date) {
    
    $transaction = $this->database->db_transaction();

    // Get transaction info
    $transaction_row = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('POSTED', 'CODE_ID', 'CONSTITUENT_ID', 'ORGANIZATION_TERM_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'VOIDED_REASON', 'STUDENT_CLASS_ID', 'AWARD_ID'))
      ->condition('CONSTITUENT_TRANSACTION_ID', $constituent_transaction_id)
      ->execute()->fetch();
    
    if ($transaction_row['POSTED'] == '1') {
      $new_amount = $transaction_row['AMOUNT'] * -1;
      
      if ($new_amount < 0)
        $transaction_description = 'REFUND '.$transaction_row['TRANSACTION_DESCRIPTION'];
      else
        $transaction_description = 'PAYBACK '.$transaction_row['TRANSACTION_DESCRIPTION'];
      
      // create return payment
      $return_payment_data = array(
        'Core.Billing.Transaction.ConstituentID' => $transaction_row['CONSTITUENT_ID'],
        'Core.Billing.Transaction.OrganizationTermID' => $transaction_row['ORGANIZATION_TERM_ID'],
        'Core.Billing.Transaction.CodeID' => $transaction_row['CODE_ID'],
        'Core.Billing.Transaction.TransactionDate' => $transaction_date,
        'Core.Billing.Transaction.Description' => $transaction_description,
        'Core.Billing.Transaction.Amount' => $new_amount, 
        'Core.Billing.Transaction.OriginalAmount' => $new_amount,
        'Core.Billing.Transaction.AppliedBalance' => 0,
        'Core.Billing.Transaction.RefundTransactionID' => $constituent_transaction_id,
        'Core.Billing.Transaction.VoidedReason' => $voided_reason,
        'Core.Billing.Transaction.Posted' => 1,
        'Core.Billing.Transaction.ShowOnStatement' => 0
      );
      
      if ($transaction_row['STUDENT_CLASS_ID']) $return_payment_data['Core.Billing.Transaction.StudentClassID'] = $transaction_row['STUDENT_CLASS_ID'];
      if ($transaction_row['AWARD_ID']) $return_payment_data['Core.Billing.Transaction.AwardID'] = $transaction_row['AWARD_ID'];
      
      $return_payment_affected = $this->posterFactory->newPoster()->add('Core.Billing.Transaction', 'new', $return_payment_data)->process()->getResult();

      // set as returned for existing transaction
      $original_transaction_poster = $this->posterFactory->newPoster()->edit('Core.Billing.Transaction', $constituent_transaction_id, array(
        'Core.Billing.Transaction.RefundTransactionID' => $return_payment_affected,
        'Core.Billing.Transaction.AppliedBalance' => 0,
        'Core.Billing.Transaction.ShowOnStatement' => 0
      ))->process($this->db_options)->getResult();
        
        // Has an FA award.  Need to set back to pending
        if ($transaction_row['AWARD_ID']) {
          $fa_poster = $this->posterFactory->newPoster()->edit('HEd.FAID.Student.Award', $transaction_row['AWARD_ID'], array(
            'HEd.FAID.Student.Award.AwardStatus' => 'PEND'
          ))->process($this->db_options)->getResult();
        }
        
      $transaction->commit();
      
      return $return_payment_affected;
      
    } else {
      // Void payment
      if ($transaction_row['VOIDED_REASON'] != '')
        $voided_reason = $transaction_row['VOIDED_REASON']. ' | '.$voided_reason;
      
      $voided_transaction_result = $this->posterFactory->newPoster()->edit('Core.Billing.Transaction', $constituent_transaction_id, array(
        'Core.Billing.Transaction.Amount' => 0.00, 
        'Core.Billing.Transaction.AppliedBalance' => 0.00, 
        'Core.Billing.Transaction.Posted' => 1, 
        'Core.Billing.Transaction.ShowOnStatement' => 0, 
        'Core.Billing.Transaction.Voided' => 1, 
        'Core.Billing.Transaction.VoidedReason' => $voided_reason, 
        'Core.Billing.Transaction.VoidedUserstamp' => (isset($this->session)) ? $this->session->get('user_id') : null, 
        'Core.Billing.Transaction.VoidedTimestamp' => date('Y-m-d H:i:s')
      ))->process($this->db_options)->getResult();

      $transaction->commit();
      
      return $voided_transaction_result;
    }
    
  }

  public function calculateBalance($transaction_id, $return_applied_balance = null) {

    // get payment amount
    $transaction = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transaction')
      ->fields('transaction', array('AMOUNT', 'PAYMENT_ID', 'CONSTITUENT_ID', 'REFUND_TRANSACTION_ID'))
      ->condition('transaction.CONSTITUENT_TRANSACTION_ID', $transaction_id)
      ->execute()->fetch();

    $refund_transaction_amount = 0.0;

    if ($transaction['PAYMENT_ID'] AND !$transaction['REFUND_TRANSACTION_ID']) {
      // get applied transactions
      $applied_trans = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
        ->expression('SUM(applied.AMOUNT)', 'total_applied_balance')
        ->join('BILL_CONSTITUENT_TRANSACTIONS', 'trans', 'trans.CONSTITUENT_TRANSACTION_ID = applied.CONSTITUENT_TRANSACTION_ID')
        ->condition('applied.CONSTITUENT_PAYMENT_ID', $transaction['PAYMENT_ID'])
        ->condition('trans.CONSTITUENT_ID', $transaction['CONSTITUENT_ID'])
        ->execute()->fetch();     
    } else {
      if ($transaction['REFUND_TRANSACTION_ID']) {
        // get refund transaction ID
        $refunded_transaction = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transaction')
          ->fields('transaction', array('AMOUNT'))
          ->condition('transaction.CONSTITUENT_TRANSACTION_ID', $transaction['REFUND_TRANSACTION_ID'])
          ->execute()->fetch();
        $refund_transaction_amount = $refunded_transaction['AMOUNT'];
      }
      // get applied transactions
      $applied_trans = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
        ->expression('SUM(AMOUNT)', 'total_applied_balance')
        ->condition('applied.CONSTITUENT_TRANSACTION_ID', $transaction_id)
        ->execute()->fetch();  
    }

    if ($transaction['PAYMENT_ID']) {
      $applied_balance = $applied_trans['total_applied_balance'] + $transaction['AMOUNT'] + $refund_transaction_amount;
    } else {
      $applied_balance = $applied_trans['total_applied_balance'] * -1 + $transaction['AMOUNT'] + $refund_transaction_amount;
    }

    $result = $this->posterFactory->newPoster()->edit('Core.Billing.Transaction', $transaction_id, array(
      'Core.Billing.Transaction.AppliedBalance' => $applied_balance
    ))->process($this->db_options)->getResult();

    if ($result > 0 AND $return_applied_balance === true) {
      return $applied_balance;
    } else {
      return $result;
    }

  }
  
}