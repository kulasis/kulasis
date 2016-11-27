<?php

namespace Kula\Core\Bundle\BillingBundle\Service;

class PaymentService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;
  
  protected $session;
  
  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory,
                              $record = null, 
                              $session = null) {
    $this->database = $db;
    $this->record = $record;
    $this->posterFactory = $poster_factory;
    $this->session = $session;
    $this->db_options = array();
  }

  public function setDBOptions($options = array()) {
    $this->db_options = $options;
  }

  public function addPayment($constituent_id, $payee_constituent_id, $payment_method, $payment_date, $payment_number, $amount, $note = null) {

    // Prepare & post payment data    
    return $this->posterFactory->newPoster()->add('Core.Billing.Payment', 'new', array(
      'Core.Billing.Payment.ConstituentID' => $constituent_id,
      'Core.Billing.Payment.PayeeConstituentID' => $payee_constituent_id,
      'Core.Billing.Payment.PaymentType' => 'P',
      'Core.Billing.Payment.PaymentMethod' => $payment_method,
      'Core.Billing.Payment.PaymentDate' => $payment_date,
      'Core.Billing.Payment.PaymentTimestamp' => $payment_date,
      'Core.Billing.Payment.PaymentNumber' => $payment_number,
      'Core.Billing.Payment.Amount' => $amount, 
      'Core.Billing.Payment.OriginalAmount' => $amount,
      'Core.Billing.Payment.AppliedBalance' => $amount * -1,
      'Core.Billing.Payment.Note' => $note,
      'Core.Billing.Payment.Posted' => 0
    ))->process($this->db_options)->getResult();

  }

  public function addAppliedPayment($payment_id, $transaction_id, $amount, $note, $locked = 0) {

    // Prepare & post payment data    
    return $this->posterFactory->newPoster()->add('Core.Billing.Payment.Applied', 'new', array(
      'Core.Billing.Payment.Applied.PaymentID' => $payment_id,
      'Core.Billing.Payment.Applied.TransactionID' => $transaction_id,
      'Core.Billing.Payment.Applied.Amount' => $amount,
      'Core.Billing.Payment.Applied.OriginalAmount' => $amount,
      'Core.Billing.Payment.Applied.Locked' => $locked,
      'Core.Billing.Payment.Applied.Note' => $note
    ))->process($this->db_options)->getResult();

  }

  public function lockAppliedPayments($payment_id) {

    $applied_payments = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
      ->fields('applied', array('CONSTITUENT_APPLIED_PAYMENT_ID'))
      ->condition('applied.CONSTITUENT_PAYMENT_ID', $payment_id)
      ->execute();
    while ($applied_payment = $applied_payments->fetch()) {
      $this->posterFactory->newPoster()->edit('Core.Billing.Payment.Applied', $applied_payment['CONSTITUENT_APPLIED_PAYMENT_ID'], array(
      'Core.Billing.Payment.Applied.Locked' => 1
      ))->process($this->db_options);
    }

  }

  public function applyMerchantResponse($payment_id, $payment_number, $amount, $payment_timestamp, $merchant_response) {

    return $this->posterFactory->newPoster()->edit('Core.Billing.Payment', $payment_id, array(
      'Core.Billing.Payment.Amount' => $amount, 
      'Core.Billing.Payment.MerchantResponse' => $merchant_response,
      'Core.Billing.Payment.PaymentNumber' => $payment_number,
      'Core.Billing.Payment.PaymentTimestamp' => $payment_timestamp
    ))->process($this->db_options)->getResult();

  }

  public function calculateBalance($payment_id) {

    // get applied transactions
    $applied_trans = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
      ->expression('SUM(AMOUNT)', 'total_applied_balance')
      ->condition('applied.CONSTITUENT_PAYMENT_ID', $payment_id)
      ->execute()->fetch();

    // get payment amount
    $payment = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS', 'payment')
      ->fields('payment', array('AMOUNT'))
      ->condition('payment.CONSTITUENT_PAYMENT_ID', $payment_id)
      ->execute()->fetch();

    return $this->posterFactory->newPoster()->edit('Core.Billing.Payment', $payment_id, array(
      'Core.Billing.Payment.AppliedBalance' => 
        $applied_trans['total_applied_balance'] * -1 + $payment['AMOUNT']
    ))->process($this->db_options)->getResult();

  }
  
}