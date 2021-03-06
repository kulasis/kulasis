<?php

namespace Kula\Core\Bundle\BillingBundle\Service;

class ConstituentBillingService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;
  
  protected $session;
  
  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory,
                              $record = null, 
                              $session = null,
                              $payment_service = null) {
    $this->database = $db;
    $this->record = $record;
    $this->posterFactory = $poster_factory;
    $this->session = $session;
    $this->db_options = array();
    $this->payment_service = $payment_service;
  }

  public function setDBOptions($options = array()) {
    $this->db_options = $options;

    $this->payment_service->setDBOptions($options);
  }
  
  public function addTransaction($constituent_id, $organization_term_id, $transaction_code_id, $transaction_date, $transaction_description, $amount) {
    
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
    
    if ($transaction_code['CODE_TYPE'] == 'P')
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
      'Core.Billing.Transaction.Posted' => 0
    ))->process($this->db_options)->getResult();
  }
  
  public function addCourseFees($student_class_id, $posted = 1, $options = array()) {
    
    if ($posted == 0 OR $posted === false) {
      $posted = 0;
    }

    // Get Class Info
    $class_fees = $this->database->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_STATUS_ID'))
      ->join('STUD_STUDENT_STATUS', 'status', 'class.STUDENT_STATUS_ID = status.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_ID'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = class.SECTION_ID')
      ->fields('section', array('ORGANIZATION_TERM_ID'))
      ->join('BILL_COURSE_FEE', 'crsfee', 'crsfee.COURSE_ID = section.COURSE_ID AND section.ORGANIZATION_TERM_ID = crsfee.ORGANIZATION_TERM_ID AND (
    crsfee.LEVEL IS NULL OR crsfee.LEVEL = status.LEVEL)')
      ->fields('crsfee', array('CODE_ID', 'AMOUNT'))
      ->join('BILL_CODE', 'code', 'code.CODE_ID = crsfee.CODE_ID')
      ->fields('code', array('CODE_DESCRIPTION'))
      ->condition('class.STUDENT_CLASS_ID', $student_class_id)
      ->execute();
    while ($class_fee_row = $class_fees->fetch()) {
      // Prepare & post payment data
      $this->posterFactory->newPoster()->add('Core.Billing.Transaction', 'new', array(
        'Core.Billing.Transaction.ConstituentID' => $class_fee_row['STUDENT_ID'],
        'Core.Billing.Transaction.OrganizationTermID' => $class_fee_row['ORGANIZATION_TERM_ID'],
        'Core.Billing.Transaction.CodeID' => $class_fee_row['CODE_ID'],
        'Core.Billing.Transaction.TransactionDate' => date('Y-m-d'),
        'Core.Billing.Transaction.Description' => $class_fee_row['CODE_DESCRIPTION'],
        'Core.Billing.Transaction.Amount' => $class_fee_row['AMOUNT'], 
        'Core.Billing.Transaction.OriginalAmount' => $class_fee_row['AMOUNT'],
        'Core.Billing.Transaction.AppliedBalance' => $class_fee_row['AMOUNT'],
        'Core.Billing.Transaction.Posted' => $posted,
        'Core.Billing.Transaction.ShowOnStatement' => 1,
        'Core.Billing.Transaction.StudentClassID' => $student_class_id
      ))->process($options);
    }
    // Add section fees
    // Get Class Info
    $class_fees = $this->database->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_STATUS_ID'))
      ->join('STUD_STUDENT_STATUS', 'status', 'class.STUDENT_STATUS_ID = status.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_ID'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = class.SECTION_ID')
      ->fields('section', array('ORGANIZATION_TERM_ID'))
      ->join('BILL_SECTION_FEE', 'crsfee', 'crsfee.SECTION_ID = section.SECTION_ID AND (crsfee.LEVEL IS NULL OR crsfee.LEVEL = status.LEVEL)')
      ->fields('crsfee', array('CODE_ID', 'AMOUNT'))
      ->join('BILL_CODE', 'code', 'code.CODE_ID = crsfee.CODE_ID')
      ->fields('code', array('CODE_DESCRIPTION'))
      ->condition('class.STUDENT_CLASS_ID', $student_class_id)
      ->execute();
    while ($class_fee_row = $class_fees->fetch()) {
      
      // Prepare & post payment data
      $this->posterFactory->newPoster()->add('Core.Billing.Transaction', 'new', array(
        'Core.Billing.Transaction.ConstituentID' => $class_fee_row['STUDENT_ID'],
        'Core.Billing.Transaction.OrganizationTermID' => $class_fee_row['ORGANIZATION_TERM_ID'],
        'Core.Billing.Transaction.CodeID' => $class_fee_row['CODE_ID'],
        'Core.Billing.Transaction.TransactionDate' => date('Y-m-d'),
        'Core.Billing.Transaction.Description' => $class_fee_row['CODE_DESCRIPTION'],
        'Core.Billing.Transaction.Amount' => $class_fee_row['AMOUNT'], 
        'Core.Billing.Transaction.OriginalAmount' => $class_fee_row['AMOUNT'],
        'Core.Billing.Transaction.AppliedBalance' => $class_fee_row['AMOUNT'],
        'Core.Billing.Transaction.Posted' => $posted,
        'Core.Billing.Transaction.ShowOnStatement' => 1,
        'Core.Billing.Transaction.StudentClassID' => $student_class_id
      ))->process($options);
    }
    
  }
  
  public function removeCourseFees($student_class_id) {
    $course_fees_transactions = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'constrans')
      ->fields('constrans', array('CONSTITUENT_TRANSACTION_ID', 'PAYMENT_ID'))
      ->condition('constrans.REFUND_TRANSACTION_ID', null)
      ->condition('constrans.STUDENT_CLASS_ID', $student_class_id)
      ->execute();
    while ($course_fees_transaction = $course_fees_transactions->fetch()) {

      if ($course_fees_transaction['PAYMENT_ID'] != '') {
        $this->payment_service->voidPayment($course_fees_transaction['PAYMENT_ID']);
      }

      $this->removeTransaction($course_fees_transaction['CONSTITUENT_TRANSACTION_ID'], 'Schedule change (auto)', date('Y-m-d'));
    }
  }
  
  public function refundCourseFees($student_class_id) {
    
    // lookup student and class info
    $class_info = $this->database->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('DROP_DATE', 'SECTION_ID'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->join('STUD_SECTION', 'sect', 'sect.SECTION_ID = class.SECTION_ID')
      ->fields('sect', array('COURSE_ID'))
      ->fields('stustatus', array('STUDENT_ID', 'ORGANIZATION_TERM_ID', 'LEVEL'))
      ->condition('class.STUDENT_CLASS_ID', $student_class_id)
      ->execute()->fetch();
    
    // get course refund end date
    $course_fee_refund_end_date = $this->database->db_select('BILL_COURSE_FEE_REFUND', 'crsrefund')
      ->expression('MIN(END_DATE)', 'enddate')
      ->condition('crsrefund.ORGANIZATION_TERM_ID', $class_info['ORGANIZATION_TERM_ID'])
      ->condition('crsrefund.COURSE_ID', $class_info['COURSE_ID'])
      ->condition('crsrefund.END_DATE', $class_info['DROP_DATE'], '>=')
      ->condition($this->database->db_or()->condition('crsrefund.LEVEL', $class_info['LEVEL'])->isNull('crsrefund.LEVEL'))
      ->execute()->fetch()['enddate'];
    
    // loop through transactions to refund
    $course_fee_refund = $this->database->db_select('BILL_COURSE_FEE_REFUND', 'crsrefund')
      ->fields('crsrefund', array('CODE_ID', 'AMOUNT'))
      ->join('STUD_STUDENT_CLASSES', 'class')
      ->join('STUD_SECTION', 'sect', 'class.SECTION_ID = class.SECTION_ID')
      ->condition('crsrefund.ORGANIZATION_TERM_ID', 'sect.ORGANIZATION_TERM_ID')
      ->condition('sect.COURSE_ID', 'crsrefund.COURSE_ID')
      ->condition('crsrefund.END_DATE', $course_fee_refund_end_date)
      ->condition($this->database->db_or()->condition('crsrefund.LEVEL', $class_info['LEVEL'])->isNull('crsrefund.LEVEL'))
      ->execute();
    while ($course_fee_refund_row = $course_fee_refund->fetch()) {
      // if type same and amount same, then use removeTransaction method, else post as new transaction  
      // get existing fee with same code and amount
      $existing_class_fee = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->fields('trans', array('CONSTITUENT_TRANSACTION_ID'))
        ->condition('STUDENT_CLASS_ID', $student_class_id)
        ->condition('CODE_ID', $course_fee_refund_row['CODE_ID'])
        ->condition('AMOUNT', $course_fee_refund_row['AMOUNT'])
        ->execute()->fetch();
      if ($existing_class_fee['CONSTITUENT_TRANSACTION_ID']) {
        $this->removeTransaction($existing_class_fee['CONSTITUENT_TRANSACTION_ID'], 'Schedule change (auto)', date('Y-m-d'));
      } else {
        $this->addTransaction($class_info['STUDENT_ID'], $class_info['ORGANIZATION_TERM_ID'], $course_fee_refund_row['CODE_ID'], date('Y-m-d'), null, $course_fee_refund_row['AMOUNT']);
      }
    }
    
    // repeat for section refunds
    // get section refund end date
    $section_fee_refund_end_date = $this->database->db_select('BILL_SECTION_FEE_REFUND', 'sectrefund')
      ->expression('MIN(END_DATE)', 'enddate')
      ->condition('sectrefund.END_DATE', $class_info['DROP_DATE'], '>=')
      ->condition('sectrefund.SECTION_ID', $class_info['SECTION_ID'])
      ->condition($this->database->db_or()->condition('sectrefund.LEVEL', $class_info['LEVEL'])->isNull('sectrefund.LEVEL'))
      ->execute()->fetch()['enddate'];
    
    // loop through transactions to refund
    $section_fee_refund = $this->database->db_select('BILL_SECTION_FEE_REFUND', 'sectrefund')
      ->fields('sectrefund', array('CODE_ID', 'AMOUNT'))
      ->condition('sectrefund.SECTION_ID', $class_info['SECTION_ID'])
      ->condition('sectrefund.END_DATE', $course_fee_refund_end_date)
      ->condition($this->database->db_or()->condition('sectrefund.LEVEL', $class_info['LEVEL'])->isNull('sectrefund.LEVEL'))
      ->execute();
    while ($section_fee_refund_row = $section_fee_refund->fetch()) {
      // if type same and amount same, then use removeTransaction method, else post as new transaction  
      // get existing fee with same code and amount
      $existing_class_fee = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->fields('trans', array('CONSTITUENT_TRANSACTION_ID'))
        ->condition('STUDENT_CLASS_ID', $student_class_id)
        ->condition('CODE_ID', $section_fee_refund_row['CODE_ID'])
        ->condition('AMOUNT', $section_fee_refund_row['AMOUNT'])
        ->execute()->fetch();
      if ($existing_class_fee['CONSTITUENT_TRANSACTION_ID']) {
        $this->removeTransaction($existing_class_fee['CONSTITUENT_TRANSACTION_ID'], 'Schedule change (auto)', date('Y-m-d'));
      } else {
        $this->addTransaction($class_info['STUDENT_ID'], $class_info['ORGANIZATION_TERM_ID'], $section_fee_refund_row['CODE_ID'], date('Y-m-d'), null, $section_fee_refund_row['AMOUNT']);
      }
    }
    
    // TODO: What about refunding discounts...
  }
  
  public function determineTuitionRate($student_status_id, $options = array()) {

    // Get Student Status Info
    $student_status = $this->database->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('STUDENT_STATUS_ID', 'ORGANIZATION_TERM_ID', 'ENTER_CODE', 'LEVEL'))
      ->leftJoin('STUD_STUDENT_DEGREES', 'studegrees', 'studegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->fields('studegrees', array('DEGREE_ID'))
      ->condition('status.STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
    $degree_tuition_rates = array();
    
    // Get valid degree rates to consider
    if ($student_status['DEGREE_ID']) {
      $degree_tuition_rate = $this->database->db_select('BILL_TUITION_RATE_STUDENTS', 'trstu')
        ->join('BILL_TUITION_RATE', 'tr', 'tr.TUITION_RATE_ID = trstu.TUITION_RATE_ID')
        ->fields('tr', array('TUITION_RATE_ID'))
        ->condition('trstu.DEGREE_ID', $student_status['DEGREE_ID'])
        ->condition('tr.ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID'])
        ->execute()->fetchAll();
      if (count($degree_tuition_rate) > 0) {
        foreach($degree_tuition_rate as $id => $row) {
          $degree_tuition_rates[] = $row['TUITION_RATE_ID'];
        }
      }
    }
    
    // Get Tuition Rate
    $tuition_rate = $this->database->db_select('BILL_TUITION_RATE_STUDENTS', 'trstu')
      ->fields('trstu')
      ->join('BILL_TUITION_RATE', 'tr', 'tr.TUITION_RATE_ID = trstu.TUITION_RATE_ID')
      ->fields('tr', array('TUITION_RATE_ID'))
      ->condition('trstu.LEVEL', $student_status['LEVEL'])
      ->condition('trstu.ENTER_CODE', $student_status['ENTER_CODE'])
      ->condition('tr.ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID']);
    if (count($degree_tuition_rates) > 0) {
      $tuition_rate = $tuition_rate->condition('tr.TUITION_RATE_ID', $degree_tuition_rates);
    }
    $tuition_rate = $tuition_rate->execute()->fetch();
    
    if ($tuition_rate['TUITION_RATE_ID']) {
      // post tuition rate
      return $this->posterFactory->newPoster()->edit('HEd.Student.Status', $student_status_id, array(
        'HEd.Student.Status.TuitionRateID' => $tuition_rate['TUITION_RATE_ID']
      ))->process($options)->getResult();
    }
    
  }
  
  public function postFinancialAidAward($award_id) {
    
    $transaction = $this->database->db_transaction();
    
    // Get FA Info
    $award_info = $this->database->db_select('FAID_STUDENT_AWARDS', 'award')
      ->fields('award', array('AWARD_ID', 'NET_AMOUNT', 'DISBURSEMENT_DATE'))
      ->join('FAID_AWARD_CODE', 'awardcode', 'awardcode.AWARD_CODE_ID = award.AWARD_CODE_ID')
      ->join('BILL_CODE', 'code', 'awardcode.TRANSACTION_CODE_ID = code.CODE_ID')
      ->fields('code', array('CODE_ID', 'CODE_DESCRIPTION'))
      ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'awardterms', 'awardterms.AWARD_YEAR_TERM_ID = award.AWARD_YEAR_TERM_ID')
      ->fields('awardterms', array('ORGANIZATION_TERM_ID'))
      ->join('FAID_STUDENT_AWARD_YEAR', 'stuawardyr', 'stuawardyr.AWARD_YEAR_ID = awardterms.AWARD_YEAR_ID')
      ->fields('stuawardyr', array('STUDENT_ID'))
      ->condition('award.AWARD_ID', $award_id)
      ->execute()->fetch();
    
    $payment_poster = $this->posterFactory->newPoster()->add('Core.Billing.Payment', 'new', array(
      'Core.Billing.Payment.ConstituentID' => $award_info['STUDENT_ID'],
      'Core.Billing.Payment.PayeeConstituentID' => $award_info['STUDENT_ID'],
      'Core.Billing.Payment.PaymentType' => 'F',
      'Core.Billing.Payment.PaymentDate' => $award_info['DISBURSEMENT_DATE'] != '' ? $award_info['DISBURSEMENT_DATE'] : date('Y-m-d'),
      'Core.Billing.Payment.PaymentTimestamp' => $award_info['DISBURSEMENT_DATE'] != '' ? $award_info['DISBURSEMENT_DATE'] : date('Y-m-d'),
      'Core.Billing.Payment.Amount' => $award_info['NET_AMOUNT'], 
      'Core.Billing.Payment.OriginalAmount' => $award_info['NET_AMOUNT'],
      'Core.Billing.Payment.AppliedBalance' => $award_info['NET_AMOUNT'] * -1,
      'Core.Billing.Payment.Posted' => 0
    ))->process($this->db_options)->getResult();

    $payment_trans_poster = $this->posterFactory->newPoster()->add('Core.Billing.Transaction', 'new', array(
      'Core.Billing.Transaction.ConstituentID' => $award_info['STUDENT_ID'],
      'Core.Billing.Transaction.OrganizationTermID' => $award_info['ORGANIZATION_TERM_ID'],
      'Core.Billing.Transaction.CodeID' => $award_info['CODE_ID'],
      'Core.Billing.Transaction.TransactionDate' => $award_info['DISBURSEMENT_DATE'] != '' ? $award_info['DISBURSEMENT_DATE'] : date('Y-m-d'),
      'Core.Billing.Transaction.Description' => $award_info['CODE_DESCRIPTION'],
      'Core.Billing.Transaction.Amount' => $award_info['NET_AMOUNT'] * -1, 
      'Core.Billing.Transaction.OriginalAmount' => $award_info['NET_AMOUNT'] * -1,
      'Core.Billing.Transaction.AppliedBalance' => $award_info['NET_AMOUNT'] * -1,
      'Core.Billing.Transaction.Posted' => 1,
      'Core.Billing.Transaction.ShowOnStatement' => 1,
      'Core.Billing.Transaction.AwardID' => $award_id,
      'Core.Billing.Transaction.PaymentID' => $payment_poster
    ))->process($this->db_options)->getResult();
    
    $award_poster = $this->posterFactory->newPoster()->edit('HEd.FAID.Student.Award', $award_id, array(
      'HEd.FAID.Student.Award.AwardStatus' => 'AWAR'
    ))->process($this->db_options)->getResult();
    
    $transaction->commit();
    
  }
  
  public function adjustFinancialAidAward(Array $award_ids) {
    
    // Query for financial aid award totals
    $award_id_totals = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'constrans')
      ->fields('constrans', array('AWARD_ID'))
      ->expression('SUM(AMOUNT)', 'award_total')
      ->condition('AWARD_ID', array_keys($award_ids))
      ->groupBy('AWARD_ID')
      ->execute();
    while ($award_id_total = $award_id_totals->fetch()) {
      if ($award_id_total['award_total'] != ($award_ids[$award_id_total['AWARD_ID']] * -1)) {
        
        // Change award to negative number and determine difference
        $adjustment_amt = -1 * ($award_id_total['award_total'] - (-1 * $award_ids[$award_id_total['AWARD_ID']]));
        
        // Get FA Info
        $award_info = $this->database->db_select('FAID_STUDENT_AWARDS', 'award')
          ->fields('award', array('AWARD_ID', 'NET_AMOUNT'))
          ->join('FAID_AWARD_CODE', 'awardcode', 'awardcode.AWARD_CODE_ID = award.AWARD_CODE_ID')
          ->join('BILL_CODE', 'code', 'awardcode.TRANSACTION_CODE_ID = code.CODE_ID')
          ->fields('code', array('CODE_ID', 'CODE_DESCRIPTION'))
          ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'awardterms', 'awardterms.AWARD_YEAR_TERM_ID = award.AWARD_YEAR_TERM_ID')
          ->fields('awardterms', array('ORGANIZATION_TERM_ID'))
          ->join('FAID_STUDENT_AWARD_YEAR', 'stuawardyr', 'stuawardyr.AWARD_YEAR_ID = awardterms.AWARD_YEAR_ID')
          ->fields('stuawardyr', array('STUDENT_ID'))
          ->condition('award.AWARD_ID', $award_id_total['AWARD_ID'])
          ->execute()->fetch();

        $payment_poster = $this->posterFactory->newPoster()->add('Core.Billing.Payment', 'new', array(
          'Core.Billing.Payment.ConstituentID' => $award_info['STUDENT_ID'],
          'Core.Billing.Payment.PayeeConstituentID' => $award_info['STUDENT_ID'],
          'Core.Billing.Payment.PaymentType' => 'F',
          'Core.Billing.Payment.PaymentDate' => date('Y-m-d'),
          'Core.Billing.Payment.PaymentTimestamp' => date('Y-m-d'),
          'Core.Billing.Payment.Amount' => $adjustment_amt, 
          'Core.Billing.Payment.OriginalAmount' => $adjustment_amt,
          'Core.Billing.Payment.AppliedBalance' => $adjustment_amt * -1,
          'Core.Billing.Payment.Posted' => 0
        ))->process($this->db_options)->getResult();
        
        $payment_trans_poster = $this->posterFactory->newPoster()->add('Core.Billing.Transaction', 'new', array(
          'Core.Billing.Transaction.ConstituentID' => $award_info['STUDENT_ID'],
          'Core.Billing.Transaction.OrganizationTermID' => $award_info['ORGANIZATION_TERM_ID'],
          'Core.Billing.Transaction.CodeID' => $award_info['CODE_ID'],
          'Core.Billing.Transaction.TransactionDate' => date('Y-m-d'),
          'Core.Billing.Transaction.Description' => $award_info['CODE_DESCRIPTION'],
          'Core.Billing.Transaction.Amount' => $adjustment_amt, 
          'Core.Billing.Transaction.OriginalAmount' => $adjustment_amt,
          'Core.Billing.Transaction.AppliedBalance' => $adjustment_amt,
          'Core.Billing.Transaction.Posted' => 0,
          'Core.Billing.Transaction.ShowOnStatement' => 1,
          'Core.Billing.Transaction.AwardID' => $award_id_total['AWARD_ID'],
          'Core.Billing.Transaction.PaymentID' => $payment_poster
        ))->process($this->db_options)->getResult();
      }
    }
    
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
      
      $return_payment_affected = $this->posterFactory->newPoster()->add('Core.Billing.Transaction', 'new', $return_payment_data)->process($this->db_options)->getResult();

      // Zero out any applied balances and recalc
      $applied_trans_result = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied_payments')
        ->fields('applied_payments', array('CONSTITUENT_APPLIED_PAYMENT_ID', 'CONSTITUENT_PAYMENT_ID'))
        ->condition('CONSTITUENT_TRANSACTION_ID', $constituent_transaction_id)
        ->execute();
      while ($applied_trans_row = $applied_trans_result->fetch()) {
        $this->posterFactory->newPoster()->edit('Core.Billing.Payment.Applied', $applied_trans_row['CONSTITUENT_APPLIED_PAYMENT_ID'], array('Core.Billing.Payment.Applied.Amount' => 0.00))->process($this->db_options)->getResult();

        // Recalc applied payments
        $this->payment_service->calculateBalanceForPayment($applied_trans_row['CONSTITUENT_PAYMENT_ID']);
      }

      // Recalc applied charges
      $this->payment_service->calculateBalanceForCharge($constituent_transaction_id);

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
        'Core.Billing.Transaction.VoidedUserstamp' => $this->session->get('user_id'), 
        'Core.Billing.Transaction.VoidedTimestamp' => date('Y-m-d H:i:s')
      ))->process($this->db_options)->getResult();

      $transaction->commit();
      
      return $voided_transaction_result;
    }
    
  }

  public function voidTransaction($constituent_transaction_id, $voided_reason, $transaction_date) {
    
    $transaction = $this->database->db_transaction();

    // Get transaction info
    $transaction_row = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('VOIDED_REASON'))
      ->condition('CONSTITUENT_TRANSACTION_ID', $constituent_transaction_id)
      ->execute()->fetch();

    // Void payment
    if ($transaction_row['VOIDED_REASON'] != '')
      $voided_reason = $transaction_row['VOIDED_REASON']. ' | '.$voided_reason;
    
    $voided_transaction_result = $this->posterFactory->newPoster()->edit('Core.Billing.Transaction', $constituent_transaction_id, array(
      'Core.Billing.Transaction.Amount' => 0.00, 
      'Core.Billing.Transaction.AppliedBalance' => 0.00, 
      'Core.Billing.Transaction.Voided' => 1, 
      'Core.Billing.Transaction.VoidedReason' => $voided_reason, 
      'Core.Billing.Transaction.VoidedUserstamp' => $this->session->get('user_id'), 
      'Core.Billing.Transaction.VoidedTimestamp' => date('Y-m-d H:i:s')
    ))->process($this->db_options)->getResult();

    $transaction->commit();
    
    return $voided_transaction_result;
    
  }

  public function refundTransaction($constituent_transaction_id, $voided_reason, $transaction_date) {
    
    $transaction = $this->database->db_transaction();

    // Get transaction info
    $transaction_row = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('POSTED', 'CODE_ID', 'CONSTITUENT_ID', 'ORGANIZATION_TERM_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'VOIDED_REASON', 'STUDENT_CLASS_ID', 'AWARD_ID'))
      ->condition('CONSTITUENT_TRANSACTION_ID', $constituent_transaction_id)
      ->execute()->fetch();
    
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
        'Core.Billing.Transaction.VoidedReason' => $voided_reason
      );
      
      if ($transaction_row['STUDENT_CLASS_ID']) $return_payment_data['Core.Billing.Transaction.StudentClassID'] = $transaction_row['STUDENT_CLASS_ID'];
      if ($transaction_row['AWARD_ID']) $return_payment_data['Core.Billing.Transaction.AwardID'] = $transaction_row['AWARD_ID'];
      
      $return_payment_affected = $this->posterFactory->newPoster()->add('Core.Billing.Transaction', 'new', $return_payment_data)->process($this->db_options)->getResult();

      // Zero out any applied balances and recalc
      $applied_trans_result = $this->database->db_select('BILL_CONSTITUENT_PAYMENTS_APPLIED', 'applied_payments')
        ->fields('applied_payments', array('CONSTITUENT_APPLIED_PAYMENT_ID', 'CONSTITUENT_PAYMENT_ID'))
        ->condition('CONSTITUENT_TRANSACTION_ID', $constituent_transaction_id)
        ->execute();
      while ($applied_trans_row = $applied_trans_result->fetch()) {
        $this->posterFactory->newPoster()->edit('Core.Billing.Payment.Applied', $applied_trans_row['CONSTITUENT_APPLIED_PAYMENT_ID'], array('Core.Billing.Payment.Applied.Amount' => 0.00))->process($this->db_options)->getResult();

        // Recalc applied payments
        $this->payment_service->calculateBalanceForPayment($applied_trans_row['CONSTITUENT_PAYMENT_ID']);
      }

      // Recalc applied charges
      $this->payment_service->calculateBalanceForCharge($constituent_transaction_id);

      // set as returned for existing transaction
      $original_transaction_poster = $this->posterFactory->newPoster()->edit('Core.Billing.Transaction', $constituent_transaction_id, array(
        'Core.Billing.Transaction.RefundTransactionID' => $return_payment_affected,
        'Core.Billing.Transaction.AppliedBalance' => 0
      ))->process($this->db_options)->getResult();
        
        // Has an FA award.  Need to set back to pending
        if ($transaction_row['AWARD_ID']) {
          $fa_poster = $this->posterFactory->newPoster()->edit('HEd.FAID.Student.Award', $transaction_row['AWARD_ID'], array(
            'HEd.FAID.Student.Award.AwardStatus' => 'PEND'
          ))->process($this->db_options)->getResult();
        }

      $transaction->commit();
      
      return $return_payment_affected;
    
  }

  public function addPayment($constituent_id, $payment_type, $payment_method, $payment_date, $payment_number, $amount) {
    // create payment data
    $payment_id = $this->posterFactory->newPoster()->add('Core.Billing.Payment', 0, array(
      'Core.Billing.Payment.ConstituentID' => $constituent_id,
      'Core.Billing.Payment.PaymentType' => $payment_type,
      'Core.Billing.Payment.PaymentMethod' => $payment_method,
      'Core.Billing.Payment.PaymentDate' => $payment_date,
      'Core.Billing.Payment.PaymentNumber' => $payment_number,
      'Core.Billing.Payment.Amount' => $amount, 
      'Core.Billing.Payment.OriginalAmount' => $amount,
      'Core.Billing.Payment.AppliedBalance' => $amount,
    ))->process($this->db_options)->getResult();

    return $payment_id;
  }
  
}