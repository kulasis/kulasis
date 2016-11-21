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
  }

  public function addPayment($constituent_id, $payee_constituent_id, $payment_method, $payment_date, $payment_number, $amount, $note) {

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
    ))->process()->getResult();

  }

  public function addAppliedPayment($payment_id, $transaction_id, $amount, $note, $locked = 'N') {

    // Prepare & post payment data    
    return $this->posterFactory->newPoster()->add('Core.Billing.Payment.Applied', 'new', array(
      'Core.Billing.Payment.PaymentID' => $payment_id,
      'Core.Billing.Payment.TransactionID' => $transaction_id,
      'Core.Billing.Payment.Amount' => $amount,
      'Core.Billing.Payment.OriginalAmount' => $amount,
      'Core.Billing.Payment.Locked' => $locked,
      'Core.Billing.Payment.Note' => $note
    ))->process()->getResult();

  }

  public function applyMerchantResponse($payment_id, $payment_number, $amount, $payment_timestamp, $merchant_response) {

    return $this->posterFactory->newPoster()->edit('Core.Billing.Payment', $payment_id, array(
      'Core.Billing.Payment.Amount' => $amount, 
      'Core.Billing.Payment.MerchantResponse' => $merchant_response,
      'Core.Billing.Payment.PaymentNumber' => $payment_number,
      'Core.Billing.Payment.PaymentTimestamp' => $payment_timestamp
    ))->process()->getResult();

  }
  
}