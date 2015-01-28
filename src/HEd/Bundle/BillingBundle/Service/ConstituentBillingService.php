<?php

namespace Kula\HEd\Bundle\BillingBundle\Service;

class ConstituentBillingService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;
  
  protected $session;
  
  public function __construct(\Kula\Component\Database\Connection $db, 
                              \Kula\Component\Database\PosterFactory $poster_factory,
                              $record = null, 
                              $session = null) {
    $this->database = $db;
    $this->record = $record;
    $this->poster_factory = $poster_factory;
    $this->session = $session;
  }
  
  public function addTransaction($constituent_id, $organization_term_id, $transaction_code_id, $transaction_date, $transaction_description, $amount) {
    
    // Get transaction code
    $transaction_code = $this->database->select('BILL_CODE', 'code')
      ->fields('code', array('CODE_TYPE', 'CODE_DESCRIPTION'))
      ->predicate('code.CODE_ID', $transaction_code_id)
      ->execute()->fetch();
    if ($transaction_description) {
      $formatted_transaction_description = $transaction_code['CODE_DESCRIPTION'].' - '.$transaction_description;
    } else {
      $formatted_transaction_description = $transaction_code['CODE_DESCRIPTION'];
    }
    
    if ($transaction_code['CODE_TYPE'] == 'P')
      $amount = $amount * -1;
    
    // Prepare & post payment data    
    $payment_poster = $this->poster_factory->newPoster(array('BILL_CONSTITUENT_TRANSACTIONS' => array('new' => array(
      'CONSTITUENT_ID' => $constituent_id,
      'ORGANIZATION_TERM_ID' => $organization_term_id,
      'CODE_ID' => $transaction_code_id,
      'TRANSACTION_DATE' => $transaction_date,
      'TRANSACTION_DESCRIPTION' => $formatted_transaction_description,
      'AMOUNT' => $amount, 
      'ORIGINAL_AMOUNT' => $amount,
      'APPLIED_BALANCE' => $amount,
      'POSTED' => 'N'
    ))));
    // Capture new payment ID
    $constituent_transaction_id = $payment_poster->getResultForTable('insert', 'BILL_CONSTITUENT_TRANSACTIONS')['new'];
    return $constituent_transaction_id;
  }
  
  public function addCourseFees($student_class_id) {
    
    // Get Class Info
    $class_fees = $this->database->select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_STATUS_ID'))
      ->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_ID'), 'class.STUDENT_STATUS_ID = status.STUDENT_STATUS_ID')
      ->join('STUD_SECTION', 'section', array('ORGANIZATION_TERM_ID'), 'section.SECTION_ID = class.SECTION_ID')
      ->join('BILL_COURSE_FEE', 'crsfee', array('CODE_ID', 'AMOUNT'), 'crsfee.COURSE_ID = section.COURSE_ID AND section.ORGANIZATION_TERM_ID = crsfee.ORGANIZATION_TERM_ID')
      ->join('BILL_CODE', 'code', array('CODE_DESCRIPTION'), 'code.CODE_ID = crsfee.CODE_ID')
      ->predicate('class.STUDENT_CLASS_ID', $student_class_id)
      ->execute();
    while ($class_fee_row = $class_fees->fetch()) {
      // Prepare & post payment data    
      $payment_poster = $this->poster_factory->newPoster(array('BILL_CONSTITUENT_TRANSACTIONS' => array('new' => array(
        'CONSTITUENT_ID' => $class_fee_row['STUDENT_ID'],
        'ORGANIZATION_TERM_ID' => $class_fee_row['ORGANIZATION_TERM_ID'],
        'CODE_ID' => $class_fee_row['CODE_ID'],
        'TRANSACTION_DATE' => date('Y-m-d'),
        'TRANSACTION_DESCRIPTION' => $class_fee_row['CODE_DESCRIPTION'],
        'AMOUNT' => $class_fee_row['AMOUNT'], 
        'ORIGINAL_AMOUNT' => $class_fee_row['AMOUNT'],
        'APPLIED_BALANCE' => $class_fee_row['AMOUNT'],
        'POSTED' => array('checkbox_hidden' => '', 'checkbox' => 'Y'),
        'SHOW_ON_STATEMENT' => array('checkbox_hidden' => '', 'checkbox' => 'Y'),
        'STUDENT_CLASS_ID' => $student_class_id,
      ))));
      // Capture new payment ID
      $constituent_transaction_id = $payment_poster->getResultForTable('insert', 'BILL_CONSTITUENT_TRANSACTIONS')['new'];  
    }
    
  }
  
  public function removeCourseFees($student_class_id) {
    $course_fees_transactions = $this->database->select('BILL_CONSTITUENT_TRANSACTIONS', 'constrans')
      ->fields('constrans', array('CONSTITUENT_TRANSACTION_ID'))
      ->predicate('constrans.REFUND_TRANSACTION_ID', null)
      ->predicate('constrans.STUDENT_CLASS_ID', $student_class_id)
      ->execute();
    while ($course_fees_transaction = $course_fees_transactions->fetch()) {
      $this->removeTransaction($course_fees_transaction['CONSTITUENT_TRANSACTION_ID'], 'Schedule change (auto)', date('Y-m-d'));
    }
  }
  
  public function determineTuitionRate($student_status_id) {
    
    // Get Student Status Info
    $student_status = $this->database->select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('STUDENT_STATUS_ID', 'ORGANIZATION_TERM_ID', 'ENTER_CODE', 'LEVEL'))
      ->predicate('status.STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
    // Get Tuition Rate
    $tuition_rate = $this->database->select('BILL_TUITION_RATE_STUDENTS', 'trstu')
      ->fields('trstu', array())
      ->join('BILL_TUITION_RATE', 'tr', array('TUITION_RATE_ID'), 'tr.TUITION_RATE_ID = trstu.TUITION_RATE_ID')
      ->predicate('trstu.LEVEL', $student_status['LEVEL'])
      ->predicate('trstu.ENTER_CODE', $student_status['ENTER_CODE'])
      ->predicate('tr.ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID'])
      ->execute()->fetch();
    
    if ($tuition_rate['TUITION_RATE_ID']) {
      // post tuition rate
      $tuitionrate_poster = $this->poster_factory->newPoster(null, array('STUD_STUDENT_STATUS' => array($student_status_id => array('TUITION_RATE_ID' => $tuition_rate['TUITION_RATE_ID']))));
    }
    
  }
  
  public function postFinancialAidAward($award_id) {
    
    if (!$this->database->inTransaction())
      $this->database->beginTransaction();
    
    // Get FA Info
    $award_info = $this->database->select('FAID_STUDENT_AWARDS', 'award')
      ->fields('award', array('AWARD_ID', 'NET_AMOUNT', 'DISBURSEMENT_DATE'))
      ->join('FAID_AWARD_CODE', 'awardcode', null, 'awardcode.AWARD_CODE_ID = award.AWARD_CODE_ID')
      ->join('BILL_CODE', 'code', array('CODE_ID', 'CODE_DESCRIPTION'), 'awardcode.TRANSACTION_CODE_ID = code.CODE_ID')
      ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'awardterms', array('ORGANIZATION_TERM_ID'), 'awardterms.AWARD_YEAR_TERM_ID = award.AWARD_YEAR_TERM_ID')
      ->join('FAID_STUDENT_AWARD_YEAR', 'stuawardyr', array('STUDENT_ID'), 'stuawardyr.AWARD_YEAR_ID = awardterms.AWARD_YEAR_ID')
      ->predicate('award.AWARD_ID', $award_id)
      ->execute()->fetch();
    
    $payment_poster = $this->poster_factory->newPoster(array('BILL_CONSTITUENT_TRANSACTIONS' => array('new' => array(
      'CONSTITUENT_ID' => $award_info['STUDENT_ID'],
      'ORGANIZATION_TERM_ID' => $award_info['ORGANIZATION_TERM_ID'],
      'CODE_ID' => $award_info['CODE_ID'],
      'TRANSACTION_DATE' => $award_info['DISBURSEMENT_DATE'] != '' ? $award_info['DISBURSEMENT_DATE'] : date('Y-m-d'),
      'TRANSACTION_DESCRIPTION' => $award_info['CODE_DESCRIPTION'],
      'AMOUNT' => $award_info['NET_AMOUNT'] * -1, 
      'ORIGINAL_AMOUNT' => $award_info['NET_AMOUNT'] * -1,
      'APPLIED_BALANCE' => $award_info['NET_AMOUNT'] * -1,
      'POSTED' => array('checkbox_hidden' => '', 'checkbox' => 'Y'),
      'SHOW_ON_STATEMENT' => array('checkbox_hidden' => '', 'checkbox' => 'Y'),
      'AWARD_ID' => $award_id,
    ))));
    
    $award_poster = $this->poster_factory->newPoster(null, 
      array('FAID_STUDENT_AWARDS' => array($award_id => array(
        'AWARD_STATUS' => 'AWAR'
      ))));
    
    if (!$this->database->inTransaction())
      $this->database->commit();
    
  }
  
  public function adjustFinancialAidAward(Array $award_ids) {
    
    // Query for financial aid award totals
    $award_id_totals = $this->database->select('BILL_CONSTITUENT_TRANSACTIONS', 'constrans')
      ->fields('constrans', array('AWARD_ID'))
      ->expressions(array('SUM(AMOUNT)' => 'award_total'))
      ->predicate('AWARD_ID', array_keys($award_ids))
      ->group_by('AWARD_ID')
      ->execute();
    while ($award_id_total = $award_id_totals->fetch()) {
      if ($award_id_total['award_total'] != ($award_ids[$award_id_total['AWARD_ID']] * -1)) {
        
        // Change award to negative number and determine difference
        $adjustment_amt = -1 * ($award_id_total['award_total'] - (-1 * $award_ids[$award_id_total['AWARD_ID']]));
        
        // Get FA Info
        $award_info = $this->database->select('FAID_STUDENT_AWARDS', 'award')
          ->fields('award', array('AWARD_ID', 'NET_AMOUNT'))
          ->join('FAID_AWARD_CODE', 'awardcode', null, 'awardcode.AWARD_CODE_ID = award.AWARD_CODE_ID')
          ->join('BILL_CODE', 'code', array('CODE_ID', 'CODE_DESCRIPTION'), 'awardcode.TRANSACTION_CODE_ID = code.CODE_ID')
          ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'awardterms', array('ORGANIZATION_TERM_ID'), 'awardterms.AWARD_YEAR_TERM_ID = award.AWARD_YEAR_TERM_ID')
          ->join('FAID_STUDENT_AWARD_YEAR', 'stuawardyr', array('STUDENT_ID'), 'stuawardyr.AWARD_YEAR_ID = awardterms.AWARD_YEAR_ID')
          ->predicate('award.AWARD_ID', $award_id_total['AWARD_ID'])
          ->execute()->fetch();
        
        $payment_poster = $this->poster_factory->newPoster(array('BILL_CONSTITUENT_TRANSACTIONS' => array('new' => array(
          'CONSTITUENT_ID' => $award_info['STUDENT_ID'],
          'ORGANIZATION_TERM_ID' => $award_info['ORGANIZATION_TERM_ID'],
          'CODE_ID' => $award_info['CODE_ID'],
          'TRANSACTION_DATE' => date('Y-m-d'),
          'TRANSACTION_DESCRIPTION' => $award_info['CODE_DESCRIPTION'],
          'AMOUNT' => $adjustment_amt, 
          'ORIGINAL_AMOUNT' => $adjustment_amt,
          'APPLIED_BALANCE' => $adjustment_amt,
          'POSTED' => array('checkbox_hidden' => '', 'checkbox' => 'N'),
          'SHOW_ON_STATEMENT' => array('checkbox_hidden' => '', 'checkbox' => 'Y'),
          'AWARD_ID' => $award_id_total['AWARD_ID'],
        ))));
      }
    }
    
  }
  
  public function postTransaction($constituent_transaction_id) {
    $transaction_poster = $this->poster_factory->newPoster(null, 
      array('BILL_CONSTITUENT_TRANSACTIONS' => array($constituent_transaction_id => array(
        'POSTED' => array('checkbox_hidden' => '', 'checkbox' => 'Y'),
        'SHOW_ON_STATEMENT' => array('checkbox_hidden' => '', 'checkbox' => 'Y')
      ))));
    return $transaction_poster->getResultForTable('update', 'BILL_CONSTITUENT_TRANSACTIONS')[$constituent_transaction_id];
  }
  
  public function removeTransaction($constituent_transaction_id, $voided_reason, $transaction_date) {
    
    if (!$this->database->inTransaction()) {
      $tran_started = 1;
      $this->database->beginTransaction();
    } else {
      $tran_started = 0;
    }

    // Get transaction info
    $transaction_row = $this->database->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('POSTED', 'CODE_ID', 'CONSTITUENT_ID', 'ORGANIZATION_TERM_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'VOIDED_REASON', 'STUDENT_CLASS_ID', 'AWARD_ID'))
      ->predicate('CONSTITUENT_TRANSACTION_ID', $constituent_transaction_id)
      ->execute()->fetch();
    
    if ($transaction_row['POSTED'] == 'Y') {
      $new_amount = $transaction_row['AMOUNT'] * -1;
      
      if ($new_amount < 0)
        $transaction_description = 'REFUND '.$transaction_row['TRANSACTION_DESCRIPTION'];
      else
        $transaction_description = 'PAYBACK '.$transaction_row['TRANSACTION_DESCRIPTION'];
      
      // create return payment
      $return_payment_data = array(
        'CONSTITUENT_ID' => $transaction_row['CONSTITUENT_ID'],
        'ORGANIZATION_TERM_ID' => $transaction_row['ORGANIZATION_TERM_ID'],
        'CODE_ID' => $transaction_row['CODE_ID'],
        'TRANSACTION_DATE' => $transaction_date,
        'TRANSACTION_DESCRIPTION' => $transaction_description,
        'AMOUNT' => $new_amount, 
        'ORIGINAL_AMOUNT' => $new_amount,
        'APPLIED_BALANCE' => 0,
        'REFUND_TRANSACTION_ID' => $constituent_transaction_id,
        'VOIDED_REASON' => $voided_reason,
        'POSTED' => array('checkbox_hidden' => '', 'checkbox' => 'Y'),
        'SHOW_ON_STATEMENT' => array('checkbox_hidden' => '', 'checkbox' => 'N')
      );
      
      if ($transaction_row['STUDENT_CLASS_ID']) $return_payment_data['STUDENT_CLASS_ID'] = $transaction_row['STUDENT_CLASS_ID'];
      if ($transaction_row['AWARD_ID']) $return_payment_data['AWARD_ID'] = $transaction_row['AWARD_ID'];
      
      $return_payment_poster = $this->poster_factory->newPoster(array('BILL_CONSTITUENT_TRANSACTIONS' => array('new' => $return_payment_data)));
      $return_payment_affected = $return_payment_poster->getResultForTable('insert', 'BILL_CONSTITUENT_TRANSACTIONS')['new'];

      // set as returned for existing transaction
      $original_transaction_poster = $this->poster_factory->newPoster(null, 
        array('BILL_CONSTITUENT_TRANSACTIONS' => array($constituent_transaction_id => array(
          'REFUND_TRANSACTION_ID' => $return_payment_affected,
          'APPLIED_BALANCE' => 0,
          'SHOW_ON_STATEMENT' => array('checkbox_hidden' => 'Y')
        ))));
        
        // Has an FA award.  Need to set back to pending
        if ($transaction_row['AWARD_ID']) {
          $fa_poster = $this->poster_factory->newPoster(null, 
            array('FAID_STUDENT_AWARDS' => array($transaction_row['AWARD_ID'] => array(
              'AWARD_STATUS' => 'PEND'
            ))));
        }
        
        if ($tran_started == 1)
          $this->database->commit();
      
      return $return_payment_affected;
      
    } else {
      // Void payment
      if ($transaction_row['VOIDED_REASON'] != '')
        $voided_reason = $transaction_row['VOIDED_REASON']. ' | '.$voided_reason;
      $voided_transaction_poster = $this->poster_factory->newPoster(null, array('BILL_CONSTITUENT_TRANSACTIONS' => array($constituent_transaction_id => array('AMOUNT' => 0.00, 'APPLIED_BALANCE' => 0.00, 'POSTED' => array('checkbox_hidden' => '', 'checkbox' => 'Y'), 'SHOW_ON_STATEMENT' => array('checkbox_hidden' => 'Y', 'checkbox' => 'N'), 'VOIDED' => array('checkbox_hidden' => '', 'checkbox' => 'Y'), 'VOIDED_REASON' => $voided_reason, 'VOIDED_USERSTAMP' => $this->session->get('user_id'), 'VOIDED_TIMESTAMP' => date('Y-m-d H:i:s')))));
      $voided_transaction_result = $voided_transaction_poster->getResultForTable('update', 'BILL_CONSTITUENT_TRANSACTIONS')[$constituent_transaction_id];

      if ($tran_started == 1)
        $this->database->commit();
      
      return $voided_transaction_result;
    }
    
  }
  
}