<?php

namespace Kula\HEd\Bundle\BillingBundle\Service;

class StudentBillingService {
  
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
  
  public function processBilling($student_status_id, $email_subject = 'Student Detail/Tuition Recalculated') {
    
    if ($student_status_id) {
    
    $schedule_service = new \Kula\Bundle\HEd\SchedulingBundle\ScheduleService($this->database, new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
    
    // Get original attempted credits
    $attempted_total_credits = \Kula\Component\Database\DB::connect('write')->select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('TOTAL_CREDITS_ATTEMPTED', 'FTE', 'LEVEL'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
      ->join('CORE_LOOKUP_VALUES', 'grade', array('DESCRIPTION' => 'grade'), 'grade.CODE = status.GRADE AND grade.LOOKUP_ID = 20')
      ->join('CORE_LOOKUP_VALUES', 'entercode', array('DESCRIPTION' => 'entercode'), 'entercode.CODE = status.ENTER_CODE AND entercode.LOOKUP_ID = 16')
      ->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'), 'constituent.CONSTITUENT_ID = status.STUDENT_ID')
      ->predicate('STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
    // Calculate Total Credits
    $schedule_service->calculateTotalAttemptedCredits($student_status_id);
    
    // Calculate FTE
    $schedule_service->calculateFTE($student_status_id);
    
    // Calculate Tuition
    $this->calculateTuition($student_status_id);
    
    $this->calculateAuditTuition($student_status_id);
    
    // Calculate Fees
    $this->calculateCourseFees($student_status_id, $attempted_total_credits['TOTAL_CREDITS_ATTEMPTED']);
    
    $new_student_info = \Kula\Component\Database\DB::connect('write')->select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('TOTAL_CREDITS_ATTEMPTED', 'FTE'))
      ->predicate('STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
    $email_text = 'Student: '.$attempted_total_credits['LAST_NAME'].', '.$attempted_total_credits['FIRST_NAME'].' ('.$attempted_total_credits['PERMANENT_NUMBER'].') | '.$attempted_total_credits['ORGANIZATION_NAME'].' | '.$attempted_total_credits['TERM_ABBREVIATION'].' | '.$attempted_total_credits['LEVEL'].' | '.$attempted_total_credits['grade'].' '.$attempted_total_credits['entercode']."\r\n";
    $email_text .= 'FTE: '.$attempted_total_credits['FTE'].' -> '.$new_student_info['FTE']."\r\n";
    $email_text .= 'Total Credits: '.$attempted_total_credits['TOTAL_CREDITS_ATTEMPTED'].' -> '.$new_student_info['TOTAL_CREDITS_ATTEMPTED']."\r\n\r\n\r\n";
    
    $headers = 'From: Kula SIS <kulasis@ocac.edu>' . "\r\n" .
        'Bcc: Makoa Jacobsen <mjacobsen@ocac.edu>' . "\r\n" .
        'Reply-To: registrar@ocac.edu' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    $subject = $email_subject.' for '.$attempted_total_credits['LAST_NAME'].', '.$attempted_total_credits['FIRST_NAME'].' ('.$attempted_total_credits['PERMANENT_NUMBER'].')';
    $to = 'Registrar <registrar@ocac.edu>, Linda Anderson <landerson@ocac.edu>, Bursar <bursar@ocac.edu>';
    mail($to, $subject, $email_text, $headers);
    
    }
  }
  
  public function checkMandatoryTransactions($student_status_id) {
    
    // Get status
    $student_status = $this->database->select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('STATUS', 'STUDENT_ID', 'ORGANIZATION_TERM_ID', 'TUITION_RATE_ID'))
      ->predicate('status.STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
    $constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->database, new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
    
    // Active Student
    if ($student_status['STATUS'] == '') {
      // Get transactions for all students, add if do not exist
      $transactions_all_result = $this->database->select('BILL_TUITION_RATE_TRANSACTIONS', 'ratetrans')
        ->fields('ratetrans', array('TRANSACTION_CODE_ID', 'AMOUNT'))
        ->left_join('BILL_CONSTITUENT_TRANSACTIONS', 'constrans', null, "constrans.CODE_ID = ratetrans.TRANSACTION_CODE_ID AND 
          constrans.CONSTITUENT_ID = '".$student_status['STUDENT_ID']."' AND constrans.ORGANIZATION_TERM_ID = '".$student_status['ORGANIZATION_TERM_ID']."'")
        ->predicate('ratetrans.RULE', 'ALLSTU')
        ->predicate('constrans.CONSTITUENT_TRANSACTION_ID', null)
        ->predicate('ratetrans.TUITION_RATE_ID', $student_status['TUITION_RATE_ID'])
        ->execute();
        while ($transactions_all_row = $transactions_all_result->fetch()) {
          $new_transaction_id = $constituent_billing_service->addTransaction($student_status['STUDENT_ID'], $student_status['ORGANIZATION_TERM_ID'], $transactions_all_row['TRANSACTION_CODE_ID'], date('Y-m-d'), '', $transactions_all_row['AMOUNT']);
          $constituent_billing_service->postTransaction($new_transaction_id);
        }
      // Get transactions for new students, add if do not exist
    } elseif ($student_status['STATUS'] == 'I') {
      
      
      
    }
    
  }
  
  public function calculateAuditTuition($student_status_id) {
    $predicate_or = new \Kula\Component\Database\Query\Predicate('OR');
    $predicate_or = $predicate_or->predicate('DROPPED', null)->predicate('DROPPED', 'N');
    
    // Get any audit classes
    $total_audit_credits = 0;
    $audit_classes_result = $this->database->select('STUD_STUDENT_CLASSES', 'classes')
      ->expressions(array('SUM(section.CREDITS)' => 'total'))
      ->join('STUD_SECTION', 'section', null, 'section.SECTION_ID = classes.SECTION_ID')
      ->join('STUD_MARK_SCALE', 'markscale', null, 'markscale.MARK_SCALE_ID = classes.MARK_SCALE_ID')
      ->predicate($predicate_or)
      ->predicate('classes.STUDENT_STATUS_ID', $student_status_id)
      ->predicate('markscale.AUDIT', 'Y')
      ->execute();
    while ($audit_classes_row = $audit_classes_result->fetch()) {
      $total_audit_credits += $audit_classes_row['total'];
    }
    
    if ($total_audit_credits > 0) {
      
      $student_status = $this->database->select('STUD_STUDENT_STATUS', 'status')
        ->fields('status', array('TUITION_RATE_ID', 'TOTAL_CREDITS_ATTEMPTED', 'STUDENT_ID', 'ORGANIZATION_TERM_ID'))
        ->join('BILL_TUITION_RATE', 'tuitionrate', array('CREDIT_HOUR_AUDIT_RATE'), 'tuitionrate.TUITION_RATE_ID = status.TUITION_RATE_ID AND tuitionrate.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
        ->predicate('status.STUDENT_STATUS_ID', $student_status_id)
        ->execute()->fetch();
      
      $audit_charge_total = $student_status['CREDIT_HOUR_AUDIT_RATE'] * $total_audit_credits;
      
      // Get audit code
      $audit_code = $this->database->select('BILL_TUITION_RATE_TRANSACTIONS', 'tuition_rate_trans')
        ->fields('tuition_rate_trans', array('TRANSACTION_CODE_ID'))
        ->predicate('TUITION_RATE_ID', $student_status['TUITION_RATE_ID'])
        ->predicate('RULE', 'AUDIT')
        ->execute()->fetch()['TRANSACTION_CODE_ID'];
      
      if ($audit_code) {
      
        // Compare calculated tuition total to what has been billed
        $billed_audit = $this->database->select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
          ->expressions(array('SUM(AMOUNT)' => 'billed_amount'))
          ->predicate('CONSTITUENT_ID', $student_status['STUDENT_ID'])
          ->predicate('ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID'])
          ->predicate('CODE_ID', $audit_code)
          ->execute()->fetch()['billed_amount'];
      
        // Determine difference to post
        $amount_to_post = $audit_charge_total - $billed_audit;
    
        if ($amount_to_post < 0) {
        
          // Get latest drop date
          $schedule_service = new \Kula\Bundle\HEd\SchedulingBundle\ScheduleService($this->database, new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
          $drop_date = $schedule_service->calculateLatestDropDate($student_status_id);
          $transaction_description = 'REFUND';
        
        // Apply refund policy
        $refund_percentage = $this->database->select('BILL_TUITION_RATE_REFUND', 'tuition_rate_refund')
          ->fields('tuition_rate_refund', array('REFUND_PERCENTAGE'))
          ->predicate('TUITION_RATE_ID', $student_status['TUITION_RATE_ID'])
          ->predicate('REFUND_TYPE', 'TUITION')
          ->predicate('END_DATE', $drop_date, '>=')
          ->order_by('END_DATE', 'ASC')
          ->execute()->fetch()['REFUND_PERCENTAGE'];
        
        $amount_to_post = $amount_to_post * $refund_percentage * .01;
      
        }
        // Post transaction
        $constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->database, new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
      
        if ($amount_to_post != 0) {
          $new_transaction_id = $constituent_billing_service->addTransaction($student_status['STUDENT_ID'], $student_status['ORGANIZATION_TERM_ID'], $audit_code, date('Y-m-d'), isset($transaction_description) ? $transaction_description : '', $amount_to_post);
          $constituent_billing_service->postTransaction($new_transaction_id);
        }
      }
    }
          
  }
  
  public function calculateTuition($student_status_id) {
    
    // Get tuition rate
    $student_status = $this->database->select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('TUITION_RATE_ID', 'TOTAL_CREDITS_ATTEMPTED', 'STUDENT_ID', 'ORGANIZATION_TERM_ID'))
      ->join('BILL_TUITION_RATE', 'tuitionrate', array('BILLING_MODE', 'FULL_TIME_CREDITS', 'FULL_TIME_FLAT_RATE', 'MAX_FULL_TIME_CREDITS', 'CREDIT_HOUR_RATE', 'CREDIT_HOUR_AUDIT_RATE'), 'tuitionrate.TUITION_RATE_ID = status.TUITION_RATE_ID AND tuitionrate.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->predicate('status.STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    // If Standard, 
    if ($student_status['BILLING_MODE'] == 'STAND') {
      // determine if at flat rate first
      if ($student_status['TOTAL_CREDITS_ATTEMPTED'] >= $student_status['FULL_TIME_CREDITS']) {
        
        $new_tuition_total = $student_status['FULL_TIME_FLAT_RATE'];
        
        // if over, overage to hourly
        if ($student_status['TOTAL_CREDITS_ATTEMPTED'] > $student_status['MAX_FULL_TIME_CREDITS']) {
        
          // Determine overage
          $overage_hours = $student_status['TOTAL_CREDITS_ATTEMPTED'] - $student_status['MAX_FULL_TIME_CREDITS'];
          $new_tuition_total += $overage_hours * $student_status['CREDIT_HOUR_RATE'];
        
        }
        
      } else {
        $new_tuition_total = $student_status['TOTAL_CREDITS_ATTEMPTED'] * $student_status['CREDIT_HOUR_RATE'];
      }
      
    // If not, then hourly, multiply credit total by credit hour price
    } elseif ($student_status['BILLING_MODE'] == 'HOUR') {
      
      $new_tuition_total = $student_status['TOTAL_CREDITS_ATTEMPTED'] * $student_status['CREDIT_HOUR_RATE'];
      
    }
    // ------
    
    // Get tuition code
    $tuition_code = $this->database->select('BILL_TUITION_RATE_TRANSACTIONS', 'tuition_rate_trans')
      ->fields('tuition_rate_trans', array('TRANSACTION_CODE_ID'))
      ->predicate('TUITION_RATE_ID', $student_status['TUITION_RATE_ID'])
      ->predicate('RULE', 'TUITION')
      ->execute()->fetch()['TRANSACTION_CODE_ID'];
    
    if ($tuition_code) {
      // Compare calculated tuition total to what has been billed
      $billed_tuition = $this->database->select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->expressions(array('SUM(AMOUNT)' => 'billed_amount'))
        ->predicate('CONSTITUENT_ID', $student_status['STUDENT_ID'])
        ->predicate('ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID'])
        ->predicate('CODE_ID', $tuition_code)
        ->execute()->fetch()['billed_amount'];
    
      // Determine difference to post
      $amount_to_post = $new_tuition_total - $billed_tuition;
    
      if ($amount_to_post < 0) {
        
        // Get latest drop date
        $schedule_service = new \Kula\Bundle\HEd\SchedulingBundle\ScheduleService($this->database, new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
        $drop_date = $schedule_service->calculateLatestDropDate($student_status_id);
        $transaction_description = 'REFUND';
        
      // Apply refund policy
      $refund_percentage = $this->database->select('BILL_TUITION_RATE_REFUND', 'tuition_rate_refund')
        ->fields('tuition_rate_refund', array('REFUND_PERCENTAGE'))
        ->predicate('TUITION_RATE_ID', $student_status['TUITION_RATE_ID'])
        ->predicate('REFUND_TYPE', 'TUITION')
        ->predicate('END_DATE', $drop_date, '>=')
        ->order_by('END_DATE', 'ASC')
        ->execute()->fetch()['REFUND_PERCENTAGE'];
        
      $amount_to_post = $amount_to_post * $refund_percentage * .01;
      
      }
      // Post transaction
      $constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->database, new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
      
      if ($amount_to_post != 0) {
        $new_transaction_id = $constituent_billing_service->addTransaction($student_status['STUDENT_ID'], $student_status['ORGANIZATION_TERM_ID'], $tuition_code, date('Y-m-d'), isset($transaction_description) ? $transaction_description : '', $amount_to_post);
        $constituent_billing_service->postTransaction($new_transaction_id);
      }
    } // end $tuition_code
  }
  
  public function calculateCourseFees($student_status_id, $previous_credit_total = null) {
    
    // get all classes
    $classes_result = $this->database->select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array('STUDENT_CLASS_ID', 'DROPPED', 'DROP_DATE'))
      ->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_ID', 'TOTAL_CREDITS_ATTEMPTED'), 'status.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
      ->join('STUD_SECTION', 'section', null, 'section.SECTION_ID = classes.SECTION_ID')
      ->join('STUD_COURSE', 'course', null, 'course.COURSE_ID = section.COURSE_ID')
      ->join('BILL_COURSE_FEE', 'coursefees', array('CODE_ID', 'AMOUNT'), 'course.COURSE_ID = coursefees.COURSE_ID AND coursefees.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->predicate('classes.STUDENT_STATUS_ID', $student_status_id)
      ->execute();
    while ($classes_row = $classes_result->fetch()) {
      
      $predicate_or = new \Kula\Component\Database\Query\Predicate('OR');
      $predicate_or = $predicate_or->predicate('bill.AMOUNT', $classes_row['AMOUNT'])->predicate('bill.AMOUNT', $classes_row['AMOUNT']*-1);
      
      // get existing fees for classes
      $existing_fees = $this->database->select('BILL_CONSTITUENT_TRANSACTIONS', 'bill')
        ->expressions(array('SUM(AMOUNT)' => 'total_amount'))
        ->predicate('CODE_ID', $classes_row['CODE_ID'])
        ->predicate('STUDENT_CLASS_ID', $classes_row['STUDENT_CLASS_ID'])
        ->predicate('CONSTITUENT_ID', $classes_row['STUDENT_ID'])
        ->predicate($predicate_or)
        ->execute()->fetch();

      // if class dropped and existing fee total is equal to the fee amount, need to determine if to refund
      if ($classes_row['DROPPED'] == 'Y' AND $existing_fees['total_amount'] == $classes_row['AMOUNT']) {
        
        // get refund schedule for student status
        $refund = $this->database->select('BILL_TUITION_RATE_REFUND', 'tuitionraterefund')
          ->fields('tuitionraterefund', array('REFUND_PERCENTAGE'))
          ->join('STUD_STUDENT_STATUS', 'status', array('TOTAL_CREDITS_ATTEMPTED'), 'status.TUITION_RATE_ID = tuitionraterefund.TUITION_RATE_ID')
          ->predicate('status.STUDENT_STATUS_ID', $student_status_id)
          ->predicate('REFUND_TYPE', 'COURSEFEE')
          ->predicate('END_DATE', $classes_row['DROP_DATE'], '>=')
          ->order_by('END_DATE')
          ->execute()->fetch();
        
        // if 100% refund, refund fee
        if ($refund['REFUND_PERCENTAGE'] == 100) {
          $billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->database, $this->poster_factory, $this->record, $this->session);
          $billing_service->removeCourseFees($classes_row);
        } elseif ($refund['REFUND_PERCENTAGE'] == 50) {
          // if 50% refund, determine if credit total changed

          // if credit total same or increased, refund fee
          if ($classes_row['TOTAL_CREDITS_ATTEMPTED'] >= $previous_credit_total) {
            $billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->database, $this->poster_factory, $this->record, $this->session);
            $billing_service->removeCourseFees($classes_row);
          }
          
        } else {
          // if 0% refund, no refund
          
        }
        
      } elseif ($classes_row['DROPPED'] != 'Y' OR $classes_row['DROPPED'] == null) {
        // class not dropped
        
        // need to check if total amount of fees is 0, need to bill
        if ($existing_fees['total_amount'] == 0) {
          
          $billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->database, $this->poster_factory, $this->record, $this->session);
          $billing_service->addCourseFees($classes_row['STUDENT_CLASS_ID']);
          
        } // if not 0, already billed
        
      }

    } // end while for classes

  }
  
}