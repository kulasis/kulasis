<?php

namespace Kula\Core\Bundle\BillingBundle\Service;

class PaymentService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;
  
  protected $session;
  
  protected $transaction_service;

  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory,
                              $transaction_service) {
    $this->database = $db;
    $this->posterFactory = $poster_factory;
    $this->db_options = array();
    $this->transaction_service = $transaction_service;
  }

  public function setDBOptions($options = array()) {
    $this->db_options = $options;
  }

  public function addPayment($constituent_id, $payee_constituent_id, $payment_type, $payment_method, $payment_date, $payment_number, $amount, $note = null, $discount_proof = null, $refund = false) {

    if ($amount < 0 OR ($amount > 0 AND $refund === true)) {
      $amount = $amount * -1;
    } 

    // Prepare & post payment data    
    return $this->posterFactory->newPoster()->add('Core.Billing.Payment', 'new', array(
      'Core.Billing.Payment.ConstituentID' => $constituent_id,
      'Core.Billing.Payment.PayeeConstituentID' => $payee_constituent_id,
      'Core.Billing.Payment.PaymentType' => $payment_type,
      'Core.Billing.Payment.PaymentMethod' => $payment_method,
      'Core.Billing.Payment.PaymentDate' => $payment_date,
      'Core.Billing.Payment.PaymentTimestamp' => $payment_date,
      'Core.Billing.Payment.PaymentNumber' => $payment_number,
      'Core.Billing.Payment.Amount' => $amount, 
      'Core.Billing.Payment.OriginalAmount' => $amount,
      'Core.Billing.Payment.AppliedBalance' => $amount * -1,
      'Core.Billing.Payment.Note' => $note,
      'Core.Billing.Payment.DiscountProof' => $discount_proof,
      'Core.Billing.Payment.Posted' => 0
    ))->process($this->db_options)->getResult();

  }

  public function addAppliedPayment($payment_id, $transaction_id, $amount, $note = null, $locked = 0) {

    if ($amount < 0) {
      $amount = $amount * -1;
    }

    // check if doesn't exist
    $applied_payment = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
      ->fields('applied', array('CONSTITUENT_APPLIED_PAYMENT_ID'))
      ->condition('applied.CONSTITUENT_PAYMENT_ID', $payment_id)
      ->condition('applied.CONSTITUENT_TRANSACTION_ID', $transaction_id)
      ->execute()->fetch();

    if ($applied_payment['CONSTITUENT_APPLIED_PAYMENT_ID']) {

    // Prepare & post payment data    
      $this->posterFactory->newPoster()->edit('Core.Billing.Payment.Applied', $applied_payment['CONSTITUENT_APPLIED_PAYMENT_ID'], array(
        'Core.Billing.Payment.Applied.PaymentID' => $payment_id,
        'Core.Billing.Payment.Applied.TransactionID' => $transaction_id,
        'Core.Billing.Payment.Applied.Amount' => $amount,
        'Core.Billing.Payment.Applied.OriginalAmount' => $amount,
        'Core.Billing.Payment.Applied.Locked' => $locked,
        'Core.Billing.Payment.Applied.Note' => $note
      ))->process($this->db_options)->getResult();

    } else {

      // Prepare & post payment data    
      $this->posterFactory->newPoster()->add('Core.Billing.Payment.Applied', 'new', array(
        'Core.Billing.Payment.Applied.PaymentID' => $payment_id,
        'Core.Billing.Payment.Applied.TransactionID' => $transaction_id,
        'Core.Billing.Payment.Applied.Amount' => $amount,
        'Core.Billing.Payment.Applied.OriginalAmount' => $amount,
        'Core.Billing.Payment.Applied.Locked' => $locked,
        'Core.Billing.Payment.Applied.Note' => $note
      ))->process($this->db_options)->getResult();
    
    }

    $this->calculateBalanceForPayment($payment_id);

    return true;
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

  public function postPayment($payment_id) {

    // post applied transactions for payment
    $transactions_payments = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
      ->fields('trans', array('CONSTITUENT_TRANSACTION_ID'))
      ->condition('trans.PAYMENT_ID', $payment_id)
      ->execute();
    while ($transaction_payment = $transactions_payments->fetch()) {
      $this->transaction_service->postTransaction($transaction_payment['CONSTITUENT_TRANSACTION_ID']);
    }

    // post payment
    return $this->posterFactory->newPoster()->edit('Core.Billing.Payment', $payment_id, array(
      'Core.Billing.Payment.Posted' => 1
    ))->process($this->db_options)->getResult();
  }

  public function voidPayment($payment_id) {
    // void applied payments
    $applied_payments = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
      ->fields('applied', array('CONSTITUENT_APPLIED_PAYMENT_ID'))
      ->condition('applied.CONSTITUENT_PAYMENT_ID', $payment_id)
      ->execute();
    while ($applied_payment = $applied_payments->fetch()) {
      $this->posterFactory->newPoster()->edit('Core.Billing.Payment.Applied', $applied_payment['CONSTITUENT_APPLIED_PAYMENT_ID'], array(
      'Core.Billing.Payment.Applied.Amount' => 0
      ))->process($this->db_options);
    } 

    // void payment transactions
    $trans_payments = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
      ->fields('trans', array('CONSTITUENT_TRANSACTION_ID'))
      ->condition('trans.PAYMENT_ID', $payment_id)
      ->execute();
    while ($trans_payment = $trans_payments->fetch()) {

      // Void transaction service
      $this->transaction_service->removeTransaction($trans_payment['CONSTITUENT_TRANSACTION_ID'], 'Payment voided.', date('Y-m-d'));
    } 


    $payment_data['Core.Billing.Payment.Amount'] = 0;
    $payment_data['Core.Billing.Payment.Voided'] = 1;
    $payment_data['Core.Billing.Payment.Posted'] = 1;

    $result = $this->posterFactory->newPoster()->edit('Core.Billing.Payment', $payment_id, $payment_data)->process($this->db_options);

    $this->calculateBalanceForPayment($payment_id);

    return $result;
  }

  public function applyMerchantResponse($payment_id, $payment_number, $amount, $payment_timestamp, $merchant_response) {

    return $this->posterFactory->newPoster()->edit('Core.Billing.Payment', $payment_id, array(
      'Core.Billing.Payment.Amount' => $amount, 
      'Core.Billing.Payment.MerchantResponse' => $merchant_response,
      'Core.Billing.Payment.PaymentNumber' => $payment_number,
      'Core.Billing.Payment.PaymentTimestamp' => $payment_timestamp
    ))->process($this->db_options)->getResult();

  }

  private function calculateAppliedBalanceForPayment($payment_id) {

    // get payment amount
    $payment = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS', 'payment')
      ->fields('payment', array('AMOUNT', 'PAYMENT_TYPE'))
      ->condition('payment.CONSTITUENT_PAYMENT_ID', $payment_id)
      ->execute()->fetch();

    $applied_trans_total = 0;

    // get applied transactions
    $applied_trans_result = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
      ->fields('applied', array('AMOUNT', 'CONSTITUENT_TRANSACTION_ID'))
      ->condition('applied.CONSTITUENT_PAYMENT_ID', $payment_id)
      ->execute();
    while ($applied_trans_row = $applied_trans_result->fetch()) {
      $applied_trans_total += $applied_trans_row['AMOUNT'];
    }

    if ($payment['PAYMENT_TYPE'] == 'R') {
    // if refund payment, applied_trans_total is POSITIVE + regular payment is NEGATIVE
      $amount = $payment['AMOUNT'];
    } else {
    // if regular payment, applied_trans_total is POSITIVE + regular payment is POSITIVE (change to negative)
      $amount = $payment['AMOUNT'] * -1;
    }
    
    return $this->posterFactory->newPoster()->edit('Core.Billing.Payment', $payment_id, array(
      'Core.Billing.Payment.AppliedBalance' => $applied_trans_total + $amount
    ))->process($this->db_options)->getResult();

  }

  private function calculateAppliedBalanceForTransaction($transaction_id) {

    $result = null;
    // get applied transactions
    $applied_trans = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
      ->expression('SUM(AMOUNT)', 'total_applied_balance')
      ->condition('applied.CONSTITUENT_TRANSACTION_ID', $transaction_id)
      ->execute()->fetch();

    // get payment amount
    $charge = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'charge')
      ->fields('charge', array('AMOUNT', 'REFUND_TRANSACTION_ID', 'STUDENT_CLASS_ID', 'PAYMENT_ID'))
      ->leftJoin('BILL_CONSTITUENT_PAYMENTS', 'payment', 'payment.CONSTITUENT_PAYMENT_ID = charge.PAYMENT_ID')
      ->fields('payment', array('PAYMENT_TYPE'))
      ->condition('charge.CONSTITUENT_TRANSACTION_ID', $transaction_id)
      ->execute()->fetch();

      if ($charge['PAYMENT_TYPE'] == 'R') {
        // -NEGATIVE + POSITIVE
        $balance = $charge['AMOUNT'] + $applied_trans['total_applied_balance'];
      } else {
        // POSITIVE - POSITIVE
        $balance = $charge['AMOUNT'] - $applied_trans['total_applied_balance'];  
      } 

      return $this->posterFactory->newPoster()->edit('Core.Billing.Transaction', $transaction_id, array(
        'Core.Billing.Transaction.AppliedBalance' => $balance
      ))->process($this->db_options)->getResult();

  }
/*
  public function calculatePaymentsForCharge($charge_id) {

    // Get charge
    $charge = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
      ->fields('trans')
      ->join('BILL_CODE', 'code', 'code.CODE_ID = trans.CODE_ID')
      ->condition('code.CODE_TYPE', 'C')
      ->condition('trans.CONSTITUENT_TRANSACTION_ID', $charge_id)
      ->execute()->fetch();
    $balance = $charge['APPLIED_BALANCE'];
    //echo $charge_id.' '.$charge['ORGANIZATION_TERM_ID'].' Starting Balance of charge: '.$balance.'<br />';

    // Find payment that matches charge
    $payment = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS', 'payments')
      ->fields('payments')
      ->join('BILL_CONSTITUENT_TRANSACTIONS', 'trans', 'trans.PAYMENT_ID = payments.CONSTITUENT_PAYMENT_ID')
      ->fields('trans', array('ORGANIZATION_TERM_ID'))
      ->condition('payments.CONSTITUENT_ID', $charge['CONSTITUENT_ID'])
      ->condition('payments.APPLIED_BALANCE', $charge['APPLIED_BALANCE']*-1)
      ->condition('trans.ORGANIZATION_TERM_ID', $charge['ORGANIZATION_TERM_ID'])
      ->execute()->fetch();

    if ($payment['APPLIED_BALANCE']*-1 == $balance) {

      $this->addAppliedPayment($payment['CONSTITUENT_PAYMENT_ID'], 
                               $charge['CONSTITUENT_TRANSACTION_ID'], 
                               $payment['APPLIED_BALANCE'], null, 1);

      //echo $payment['CONSTITUENT_PAYMENT_ID'].' '.$payment['ORGANIZATION_TERM_ID'].' Applied Balance to matching charge: '.$payment['APPLIED_BALANCE'].'<br />';

    } elseif ($payment['AMOUNT'] == $charge['AMOUNT'] AND $charge['ORGANIZATION_TERM_ID'] == $payment['ORGANIZATION_TERM_ID']) {

      // remove all applied payments
      $applied_trans_result = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
        ->fields('applied', array('CONSTITUENT_APPLIED_PAYMENT_ID', 'CONSTITUENT_TRANSACTION_ID'))
        ->condition('applied.CONSTITUENT_PAYMENT_ID', $payment['CONSTITUENT_PAYMENT_ID'])
        ->condition('trans.ORGANIZATION_TERM_ID', $charge['ORGANIZATION_TERM_ID'])
        ->execute();
      while ($applied_trans_row = $applied_trans_result->fetch()) {
        $this->posterFactory->newPoster()->delete('Core.Billing.Payment.Applied', $applied_trans_row['CONSTITUENT_APPLIED_PAYMENT_ID'])->process($this->db_options)->getResult();

        $this->calculateBalanceForCharge($applied_trans_row['CONSTITUENT_TRANSACTION_ID']);
      }

      $this->addAppliedPayment($payment['CONSTITUENT_PAYMENT_ID'], 
                               $charge_id, 
                               $payment['AMOUNT'], null, 1);

      $this->calculateBalanceForPayment($payment_id);

      //echo $payment['CONSTITUENT_PAYMENT_ID'].' '.$payment['ORGANIZATION_TERM_ID'].' Removed existing charges and applied Balance to matching charge: '.$payment['APPLIED_BALANCE'].'<br />';

    } else {
      // Find oldest payments and loop through, applying payments until charge has no balance
      $payments_result = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS', 'payments')
        ->fields('payments')
        ->join('BILL_CONSTITUENT_TRANSACTIONS', 'trans', 'trans.PAYMENT_ID = payments.CONSTITUENT_PAYMENT_ID')
        ->fields('trans', array('ORGANIZATION_TERM_ID'))
        ->condition('payments.CONSTITUENT_ID', $charge['CONSTITUENT_ID'])
        ->condition('payments.APPLIED_BALANCE', 0, '<')
        ->condition('trans.ORGANIZATION_TERM_ID', $charge['ORGANIZATION_TERM_ID'])
        ->orderBy('payments.PAYMENT_DATE', 'ASC')
        ->execute();
      while ($payment_row = $payments_result->fetch()) {
//echo $charge_id.' '.$payment_row['ORGANIZATION_TERM_ID'].' Payment amount to apply: '.$payment_row['APPLIED_BALANCE'].' <br />';
        $balance_to_apply = null;
        if ($payment_row['APPLIED_BALANCE']*-1 <= $balance) {
          $balance_to_apply = $payment_row['APPLIED_BALANCE']*-1;
    //echo $charge_id.' '.$payment_row['ORGANIZATION_TERM_ID'].' Applied Balance 2: '.$balance_to_apply.'<br />';
        } else {
          $balance_to_apply = $balance;
    //echo $charge_id.' '.$payment_row['ORGANIZATION_TERM_ID'].' Applied Balance 3: '.$balance_to_apply.'<br />';
        }


        if ($balance_to_apply > 0) {
          $this->addAppliedPayment($payment_row['CONSTITUENT_PAYMENT_ID'], 
            $charge_id, 
            $balance_to_apply , null, 1);

          $balance = $balance - $balance_to_apply ;
        }
        //echo $charge_id.' '.$payment_row['ORGANIZATION_TERM_ID'].' Remaining Balance: '.$balance.' <br />';
        if ($balance <= 0) {
          break;
        }
      } // end while loop
    } // end else for matching payments

  }
*/
  public function calculateBalanceForPayment($payment_id) {

    // get payment amount
    $payment = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS', 'payment')
      ->fields('payment', array('AMOUNT', 'PAYMENT_TYPE'))
      ->condition('payment.CONSTITUENT_PAYMENT_ID', $payment_id)
      ->execute()->fetch();

    $applied_trans_total = 0;

    // get applied transactions
    $applied_trans_result = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
      ->fields('applied', array('AMOUNT', 'CONSTITUENT_TRANSACTION_ID'))
      ->condition('applied.CONSTITUENT_PAYMENT_ID', $payment_id)
      ->execute();
    while ($applied_trans_row = $applied_trans_result->fetch()) {
      $applied_trans_total += $applied_trans_row['AMOUNT'];
      $this->calculateBalanceForCharge($applied_trans_row['CONSTITUENT_TRANSACTION_ID'], $payment['PAYMENT_TYPE']);
    }

    if ($payment['PAYMENT_TYPE'] == 'R') {
      $applied_trans_total = $applied_trans_total * -1;
    } 

    // Get payment transactions
    $payment_trans_result = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'paytrans')
      ->fields('paytrans', array('CONSTITUENT_TRANSACTION_ID', 'AMOUNT', 'STUDENT_CLASS_ID'))
      ->condition('paytrans.PAYMENT_ID', $payment_id)
      ->execute();
    while ($payment_trans_row = $payment_trans_result->fetch()) {
        $this->updateAppliedBalanceForTransaction($payment_trans_row['CONSTITUENT_TRANSACTION_ID'], $applied_trans_total + $payment_trans_row['AMOUNT']);
        if ($payment_trans_row['STUDENT_CLASS_ID']) {
          $this->updateClassPaidStatus($payment_trans_row['STUDENT_CLASS_ID']);
        }
    }

    // get payment transactions with this payment's transaction as constituent transaction id
    $applied_cons_total = 0;
    $applied_cons_trans_result = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
      ->fields('applied', array('AMOUNT', 'CONSTITUENT_TRANSACTION_ID'))
      ->join('BILL_CONSTITUENT_TRANSACTIONS', 'trans', 'trans.CONSTITUENT_TRANSACTION_ID = applied.CONSTITUENT_TRANSACTION_ID')
      ->join('BILL_CONSTITUENT_PAYMENTS', 'pay', 'pay.CONSTITUENT_PAYMENT_ID = applied.CONSTITUENT_PAYMENT_ID')
      ->condition('pay.PAYMENT_TYPE', 'R')
      ->condition('trans.PAYMENT_ID', $payment_id)
      ->execute();
    while ($applied_cons_trans_row = $applied_cons_trans_result->fetch()) {
      $applied_cons_total += $applied_cons_trans_row['AMOUNT'];
      $this->calculateBalanceForCharge($applied_cons_trans_row['CONSTITUENT_TRANSACTION_ID'], 'R');
    }
    
    return $this->posterFactory->newPoster()->edit('Core.Billing.Payment', $payment_id, array(
      'Core.Billing.Payment.AppliedBalance' => $applied_trans_total + $payment['AMOUNT'] * -1 + $applied_cons_total
    ))->process($this->db_options)->getResult();

  }

  public function calculateBalanceForCharge($charge_id, $payment_type = 'P') {
    $result = null;
    // get applied transactions
    $applied_trans = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
      ->expression('SUM(AMOUNT)', 'total_applied_balance')
      ->condition('applied.CONSTITUENT_TRANSACTION_ID', $charge_id)
      ->execute()->fetch();

    // get payment amount
    $charge = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'charge')
      ->fields('charge', array('AMOUNT', 'REFUND_TRANSACTION_ID', 'STUDENT_CLASS_ID', 'PAYMENT_ID'))
      ->condition('charge.CONSTITUENT_TRANSACTION_ID', $charge_id)
      ->execute()->fetch();

    if ($charge['REFUND_TRANSACTION_ID'] != '') {
      $result = $this->updateAppliedBalanceForTransaction($charge_id, 0);
    } else {
      if ($payment_type == 'R') {
        // Gather any transactions attached to original payment
        $applied_trans_payment = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied')
          ->expression('SUM(AMOUNT)', 'total_applied_balance')
          ->condition('applied.CONSTITUENT_PAYMENT_ID', $charge['PAYMENT_ID'])
          ->execute()->fetch();

        $result = $this->updateAppliedBalanceForTransaction($charge_id, $charge['AMOUNT'] + $applied_trans['total_applied_balance'] + $applied_trans_payment['total_applied_balance']);
      } else {
        $result = $this->updateAppliedBalanceForTransaction($charge_id, $charge['AMOUNT'] - $applied_trans['total_applied_balance']);  
      } 
    }
    if ($charge['STUDENT_CLASS_ID'] != '') {
      $this->updateClassPaidStatus($charge['STUDENT_CLASS_ID']);
    }
    return $result;
  }

  public function updateAppliedBalanceForTransaction($transaction_id, $applied_balance) {
    return $this->posterFactory->newPoster()->edit('Core.Billing.Transaction', $transaction_id, array(
      'Core.Billing.Transaction.AppliedBalance' => 
        $applied_balance
    ))->process($this->db_options)->getResult();
  }

  public function updateClassPaidStatus($class_id) {

    $applied_balance = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
      ->expression('SUM(APPLIED_BALANCE)', 'applied_balance')
      ->condition('trans.STUDENT_CLASS_ID', $class_id)
      ->condition('trans.AMOUNT', 0, '!=')
      ->execute()->fetch()['applied_balance'];

    if ($applied_balance == 0) {
      $status = $this->posterFactory->newPoster()->edit('HEd.Student.Class', $class_id, array('HEd.Student.Class.Paid' => 1))->process($this->db_options)->getResult();
    } else {
      $status = $this->posterFactory->newPoster()->edit('HEd.Student.Class', $class_id, array('HEd.Student.Class.Paid' => 0))->process($this->db_options)->getResult();
    }

    $class_row = $this->database->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('SECTION_ID', 'PAID'))
      ->condition('class.STUDENT_CLASS_ID', $class_id)
      ->execute()->fetch();

    $paid_totals_row = $this->database->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('SECTION_ID'))
      ->expression('COUNT(*)', 'paid_total')
      ->condition('class.SECTION_ID', $class_row['SECTION_ID'])
      ->condition($this->database->db_or()->condition('DROPPED', null)->condition('DROPPED', 0))
      ->condition('class.PAID', 1)
      ->groupBy('SECTION_ID', 'class')
      ->execute()->fetch();

    $section_poster = $this->posterFactory->newPoster()->edit('HEd.Section', $class_row['SECTION_ID'], array(
        'HEd.Section.PaidTotal' => $paid_totals_row['paid_total']
      ))->process($this->db_options)->getResult();
    
    return $status;

  }
  
}