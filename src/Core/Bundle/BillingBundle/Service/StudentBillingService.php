<?php

namespace Kula\Core\Bundle\BillingBundle\Service;

class StudentBillingService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;
  
  protected $session;
  
  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory,
                              $record = null, 
                              $session = null,
                              $constituent_billing_service = null,
                              $schedule_service = null) {
    $this->database = $db;
    $this->record = $record;
    $this->poster_factory = $poster_factory;
    $this->session = $session;
    $this->constituent_billing_service = $constituent_billing_service;
    $this->schedule_service = $schedule_service;
  }
  
  public function processBilling($student_status_id, $email_subject = 'Student Detail/Tuition Recalculated', $send_email = true) {
    
    if ($student_status_id) {
      
      // Determine tuition rate
      $this->constituent_billing_service->determineTuitionRate($student_status_id);
    
    // Get original attempted credits
    $attempted_total_credits = $this->database->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('TOTAL_CREDITS_ATTEMPTED', 'FTE', 'LEVEL'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->join('CORE_LOOKUP_VALUES', 'grade', "grade.CODE = status.GRADE AND grade.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'Core.Student.Enrollment.Grade')")
      ->fields('grade', array('DESCRIPTION' => 'grade'))
      ->join('CORE_LOOKUP_VALUES', 'entercode', "entercode.CODE = status.ENTER_CODE AND entercode.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'Core.Student.Enrollment.EnterCode')")
      ->fields('entercode', array('DESCRIPTION' => 'entercode'))
      ->join('CONS_CONSTITUENT', 'constituent', 'constituent.CONSTITUENT_ID = status.STUDENT_ID')
      ->fields('constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'))
      ->condition('STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
    // Calculate Total Credits
    $this->schedule_service->calculateTotalAttemptedCredits($student_status_id);
    
    // Check mandatory 
    $this->checkMandatoryTransactions($student_status_id);
    
    // Calculate FTE
    $this->schedule_service->calculateFTE($student_status_id);
    
    // Calculate Tuition
    $this->calculateTuition($student_status_id);
    
    $this->calculateAuditTuition($student_status_id);
    
    // Calculate Fees
    $this->calculateCourseFees($student_status_id, $attempted_total_credits['TOTAL_CREDITS_ATTEMPTED']);
    
    $new_student_info = $this->database->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('TOTAL_CREDITS_ATTEMPTED', 'FTE'))
      ->condition('STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
      if ($attempted_total_credits['ORGANIZATION_NAME'] == 'OCAC Degree Programs') {
      
        if ($send_email) {
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
    
        } // end if on send email
    
      } // end if on attempted credits
    
    } // end if on student_status_id
  }
  
  public function checkMandatoryTransactions($student_status_id) {
    
    // Get status
    $student_status = $this->database->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('STATUS', 'STUDENT_ID', 'ORGANIZATION_TERM_ID', 'TUITION_RATE_ID', 'ENTER_CODE', 'LEVEL'))
      ->condition('status.STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
    // Active Student
    if ($student_status['STATUS'] == '') {
      // Get transactions for all students, add if do not exist
      $transactions_all_result = $this->database->db_select('BILL_TUITION_RATE_TRANSACTIONS', 'ratetrans')
        ->fields('ratetrans', array('TRANSACTION_CODE_ID', 'AMOUNT'))
        ->leftJoin('BILL_CONSTITUENT_TRANSACTIONS', 'constrans', "constrans.CODE_ID = ratetrans.TRANSACTION_CODE_ID AND 
          constrans.CONSTITUENT_ID = '".$student_status['STUDENT_ID']."' AND constrans.ORGANIZATION_TERM_ID = '".$student_status['ORGANIZATION_TERM_ID']."'")
        ->condition('ratetrans.RULE', 'ALLSTU')
        ->condition('constrans.CONSTITUENT_TRANSACTION_ID', null)
        ->condition('ratetrans.TUITION_RATE_ID', $student_status['TUITION_RATE_ID'])
        ->execute();
        while ($transactions_all_row = $transactions_all_result->fetch()) {
          if ($transactions_all_row['AMOUNT'] > 0) {
            $new_transaction_id = $this->constituent_billing_service->addTransaction($student_status['STUDENT_ID'], $student_status['ORGANIZATION_TERM_ID'], $transactions_all_row['TRANSACTION_CODE_ID'], date('Y-m-d'), '', $transactions_all_row['AMOUNT']);
            $this->constituent_billing_service->postTransaction($new_transaction_id);
          }
        }
        
      // Get transactions for new students, add if do not exist
      $transactions_all_result = $this->database->db_select('BILL_TUITION_RATE_TRANSACTIONS', 'ratetrans')
        ->fields('ratetrans', array('TRANSACTION_CODE_ID', 'AMOUNT'))
        ->join('BILL_TUITION_RATE_STUDENTS', 'ratestu', 'ratestu.TUITION_RATE_ID = ratetrans.TUITION_RATE_ID AND ratestu.NEW_STUDENT = 1')
        ->leftJoin('BILL_CONSTITUENT_TRANSACTIONS', 'constrans', "constrans.CODE_ID = ratetrans.TRANSACTION_CODE_ID AND 
          constrans.CONSTITUENT_ID = '".$student_status['STUDENT_ID']."' AND constrans.ORGANIZATION_TERM_ID = '".$student_status['ORGANIZATION_TERM_ID']."'")
        ->condition('ratetrans.RULE', 'NEWSTU')
        ->condition('constrans.CONSTITUENT_TRANSACTION_ID', null)
        ->condition('ratestu.ENTER_CODE', $student_status['ENTER_CODE'])
        ->condition('ratestu.LEVEL', $student_status['LEVEL'])
        ->condition('ratetrans.TUITION_RATE_ID', $student_status['TUITION_RATE_ID'])
        ->execute();
        while ($transactions_all_row = $transactions_all_result->fetch()) {
          if ($transactions_all_row['AMOUNT'] > 0) {
            $new_transaction_id = $this->constituent_billing_service->addTransaction($student_status['STUDENT_ID'], $student_status['ORGANIZATION_TERM_ID'], $transactions_all_row['TRANSACTION_CODE_ID'], date('Y-m-d'), '', $transactions_all_row['AMOUNT']);
            $this->constituent_billing_service->postTransaction($new_transaction_id);
          }
        }
      
      
    } elseif ($student_status['STATUS'] == 'I') {
      
      // Get transactions for all students, add if do not exist
      $transactions_all_result = $this->database->db_select('BILL_TUITION_RATE_TRANSACTIONS', 'ratetrans')
        ->fields('ratetrans', array('TRANSACTION_CODE_ID', 'TUITION_RATE_TRANSACTION_ID'))
        ->join('BILL_CONSTITUENT_TRANSACTIONS', 'constrans', "constrans.CODE_ID = ratetrans.TRANSACTION_CODE_ID AND 
          constrans.CONSTITUENT_ID = '".$student_status['STUDENT_ID']."' AND constrans.ORGANIZATION_TERM_ID = '".$student_status['ORGANIZATION_TERM_ID']."'")
        ->condition('ratetrans.RULE', array('NEWSTU', 'ALLSTU'))
        ->expression('SUM(constrans.AMOUNT)', 'trans_total')
		    ->condition('ratetrans.TUITION_RATE_ID', $student_status['TUITION_RATE_ID'])
		    ->groupBy('TUITION_RATE_TRANSACTION_ID')
        ->execute();
        while ($transactions_all_row = $transactions_all_result->fetch()) {
          if ($transactions_all_row['trans_total'] > 0) {
			  
      			// Determine amount to refund
      			$amount = $this->database->db_select('BILL_TUITION_RATE_TRANS_REFUND', 'transrefunds')
      				->fields('transrefunds', array('REFUND_PERCENTAGE'))
      				->condition('transrefunds.TUITION_RATE_TRANSACTION_ID', $transactions_all_row['TUITION_RATE_TRANSACTION_ID'])
      				->condition('transrefunds.END_DATE', date('Y-m-d'), '>=')
      				->orderBy('transrefunds.END_DATE', 'ASC')
      				->execute()->fetch();
      				
      			// Refund amount
            $refund_amount = $transactions_all_row['trans_total'] * $amount['REFUND_PERCENTAGE'] * -1 * 0.01;

            if ($refund_amount != 0.00) {
      			 $new_transaction_id = $this->constituent_billing_service->addTransaction($student_status['STUDENT_ID'], $student_status['ORGANIZATION_TERM_ID'], $transactions_all_row['TRANSACTION_CODE_ID'], date('Y-m-d'), 'Refund', $refund_amount);
             $this->constituent_billing_service->postTransaction($new_transaction_id);
                
            }
          }
        }
    }
    
  }
  
  public function calculateAuditTuition($student_status_id) {
    
    // Get any audit classes
    $total_audit_credits = 0;
    $audit_classes_result = $this->database->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->expression('SUM(section.CREDITS)', 'total')
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = classes.SECTION_ID')
      ->join('STUD_MARK_SCALE', 'markscale', 'markscale.MARK_SCALE_ID = classes.MARK_SCALE_ID')
      ->condition('DROPPED', 0)
      ->condition('classes.STUDENT_STATUS_ID', $student_status_id)
      ->condition('markscale.AUDIT', '1')
      ->execute();
    while ($audit_classes_row = $audit_classes_result->fetch()) {
      $total_audit_credits += $audit_classes_row['total'];
    }
    
    if ($total_audit_credits > 0) {
      $student_status_result = $this->database->db_select('STUD_STUDENT_STATUS', 'status')
        ->fields('status', array('TUITION_RATE_ID', 'TOTAL_CREDITS_ATTEMPTED', 'STUDENT_ID', 'ORGANIZATION_TERM_ID'))
        ->join('BILL_TUITION_RATE', 'tuitionrate', 'tuitionrate.TUITION_RATE_ID = status.TUITION_RATE_ID AND tuitionrate.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
        ->join('BILL_TUITION_RATE_TRANSACTIONS', 'transactions', 'transactions.TUITION_RATE_ID = tuitionrate.TUITION_RATE_ID')
        ->condition('transactions.RULE', 'AUDIT')
        ->fields('transactions', array('TUITION_RATE_TRANSACTION_ID', 'CREDIT_HOUR_RATE', 'TRANSACTION_CODE_ID'))
        ->condition('status.STUDENT_STATUS_ID', $student_status_id)
        ->execute();
      while ($student_status = $student_status_result->fetch()) {

      $audit_charge_total = $student_status['CREDIT_HOUR_RATE'] * $total_audit_credits;
      
        // Compare calculated tuition total to what has been billed
        $billed_audit = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
          ->expression('SUM(AMOUNT)', 'billed_amount')
          ->condition('CONSTITUENT_ID', $student_status['STUDENT_ID'])
          ->condition('ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID'])
          ->condition('CODE_ID', $student_status['TRANSACTION_CODE_ID'])
          ->execute()->fetch()['billed_amount'];
  
        // Determine difference to post
        $amount_to_post = $audit_charge_total - $billed_audit;

        if ($amount_to_post < 0) {
        
          // Get latest drop date
          $drop_date = $this->schedule_service->calculateLatestDropDate($student_status_id);
          $transaction_description = 'REFUND';
        
        // Apply refund policy
        $refund_percentage = $this->database->db_select('BILL_TUITION_RATE_TRANS_REFUND', 'tuition_rate_refund')
          ->fields('tuition_rate_refund', array('REFUND_PERCENTAGE'))
          ->condition('TUITION_RATE_TRANSACTION_ID', $student_status['TUITION_RATE_TRANSACTION_ID'])
          ->condition('END_DATE', $drop_date, '>=')
          ->orderBy('END_DATE', 'ASC')
          ->execute()->fetch()['REFUND_PERCENTAGE'];
        
        $amount_to_post = $amount_to_post * $refund_percentage * .01;
      
        }
      
        if ($amount_to_post != 0) {
          $new_transaction_id = $this->constituent_billing_service->addTransaction($student_status['STUDENT_ID'], $student_status['ORGANIZATION_TERM_ID'], $student_status['TRANSACTION_CODE_ID'], date('Y-m-d'), isset($transaction_description) ? $transaction_description : '', $amount_to_post);
          $this->constituent_billing_service->postTransaction($new_transaction_id);
        }
      } // end while loop
    } // end if greater than 0 for credit total
          
  }
  
  public function calculateTuition($student_status_id) {
    
    // Get tuition rate
    $student_status_result = $this->database->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('TUITION_RATE_ID', 'TOTAL_CREDITS_ATTEMPTED', 'STUDENT_ID', 'ORGANIZATION_TERM_ID'))
      ->join('BILL_TUITION_RATE', 'tuitionrate', 'tuitionrate.TUITION_RATE_ID = status.TUITION_RATE_ID AND tuitionrate.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->fields('tuitionrate', array('BILLING_MODE'))
      ->join('BILL_TUITION_RATE_TRANSACTIONS', 'tuition_rate_trans', 'tuition_rate_trans.TUITION_RATE_ID = tuitionrate.TUITION_RATE_ID')
      ->fields('tuition_rate_trans', array('TUITION_RATE_TRANSACTION_ID', 'TRANSACTION_CODE_ID', 'FULL_TIME_CREDITS','MAX_FULL_TIME_CREDITS', 'FULL_TIME_FLAT_RATE', 'CREDIT_HOUR_RATE'))
      ->condition('tuition_rate_trans.RULE', 'TUITION')
      ->condition('status.STUDENT_STATUS_ID', $student_status_id)
      ->execute();
    while ($student_status = $student_status_result->fetch()) {

      // If Standard, 
      if ($student_status['BILLING_MODE'] == 'STAND') {

        // determine if at flat rate first, total credits attempted can't be null
        if ($student_status['TOTAL_CREDITS_ATTEMPTED'] AND $student_status['TOTAL_CREDITS_ATTEMPTED'] >= $student_status['FULL_TIME_CREDITS']) {
        
          $new_tuition_total = $student_status['FULL_TIME_FLAT_RATE'];
        
          // if over, overage to hourly
          if ($student_status['TOTAL_CREDITS_ATTEMPTED'] > $student_status['MAX_FULL_TIME_CREDITS'] AND $student_status['MAX_FULL_TIME_CREDITS'] != '') {

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
      $tuition_code = $student_status['TRANSACTION_CODE_ID'];
    
      if ($tuition_code) {
        // Compare calculated tuition total to what has been billed
        $billed_tuition = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
          ->expression('SUM(AMOUNT)', 'billed_amount')
          ->condition('CONSTITUENT_ID', $student_status['STUDENT_ID'])
          ->condition('ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID'])
          ->condition('CODE_ID', $tuition_code)
        //  ->condition('TRANSACTION_DESCRIPTION', '%REFUND%', 'NOT LIKE')
          ->execute()->fetch()['billed_amount'];

        $debug = array();
        $debug['billed tuition'] = $billed_tuition;
        $debug['new tuition'] = $new_tuition_total;

        // Determine difference to post
        $amount_to_post = $new_tuition_total - $billed_tuition;

        $current_refund_tuition = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
          ->expression('SUM(AMOUNT)', 'billed_amount')
          ->condition('CONSTITUENT_ID', $student_status['STUDENT_ID'])
          ->condition('ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID'])
          ->condition('CODE_ID', $tuition_code)
          ->condition('TRANSACTION_DESCRIPTION', '%REFUND%', 'LIKE')
          ->execute()->fetch()['billed_amount'];

        if ($amount_to_post < 0) {
        
          // Get latest drop date
          $drop_date = $this->schedule_service->calculateLatestDropDate($student_status_id);
          $transaction_description = 'REFUND';
        
        // Apply refund policy
        $refund_percentage = $this->database->db_select('BILL_TUITION_RATE_TRANS_REFUND', 'tuition_rate_refund')
          ->fields('tuition_rate_refund', array('REFUND_PERCENTAGE'))
          ->condition('TUITION_RATE_TRANSACTION_ID', $student_status['TUITION_RATE_TRANSACTION_ID'])
          ->condition('END_DATE', $drop_date, '>=')
          ->orderBy('END_DATE', 'ASC')
          ->execute()->fetch()['REFUND_PERCENTAGE'];

        $debug['pre amount to post'] = $amount_to_post;
        $debug['refund percentage'] = $refund_percentage;
        
        $amount_to_post = $amount_to_post * $refund_percentage * .01;

        $debug['amount to post'] = $amount_to_post;

        //throw new \Exception(print_r($debug, true));
      
        }
        // Post transaction
        if ($amount_to_post != 0 AND $current_refund_tuition != $amount_to_post) {
          $new_transaction_id = $this->constituent_billing_service->addTransaction($student_status['STUDENT_ID'], $student_status['ORGANIZATION_TERM_ID'], $tuition_code, date('Y-m-d'), isset($transaction_description) ? $transaction_description : '', $amount_to_post);
          $this->constituent_billing_service->postTransaction($new_transaction_id);
        }
      } // end $tuition_code
    } // end $student_status_result 
  }
  
  public function calculateCourseFees($student_status_id, $previous_credit_total = null, $posted = 1) {
    
    // get all classes
    $classes_result = $this->database->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array('STUDENT_CLASS_ID', 'DROPPED', 'DROP_DATE'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_ID', 'TOTAL_CREDITS_ATTEMPTED'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = classes.SECTION_ID')
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->leftJoin('BILL_COURSE_FEE', 'coursefees', 'course.COURSE_ID = coursefees.COURSE_ID AND coursefees.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID AND (coursefees.LEVEL = status.LEVEL OR coursefees.LEVEL IS NULL)')
      ->fields('coursefees', array('CODE_ID', 'AMOUNT'))
      ->leftJoin('BILL_SECTION_FEE', 'sectionfees', 'section.SECTION_ID = sectionfees.SECTION_ID AND (sectionfees.LEVEL = status.LEVEL OR sectionfees.LEVEL IS NULL)')
      ->fields('sectionfees', array('CODE_ID' => 'section_CODE_ID', 'AMOUNT' => 'section_AMOUNT'))
      ->condition('classes.STUDENT_STATUS_ID', $student_status_id)
      ->execute();
    while ($classes_row = $classes_result->fetch()) {

      $condition_or = $this->database->db_or();
	  $code_condition = $this->database->db_and();
      if ($classes_row['section_AMOUNT']) {
		$condition_or = $condition_or->condition('bill.AMOUNT', $classes_row['section_AMOUNT'])->condition('bill.AMOUNT', $classes_row['section_AMOUNT']*-1);
		$code_condition = $code_condition->condition('CODE_ID', $classes_row['section_CODE_ID']);
      } else {
		$condition_or = $condition_or->condition('bill.AMOUNT', $classes_row['AMOUNT'])->condition('bill.AMOUNT', $classes_row['AMOUNT']*-1);
		$code_condition = $code_condition->condition('CODE_ID', $classes_row['CODE_ID']);
      }
	  
      // get existing fees for classes
      $existing_fees = $this->database->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'bill')
        ->expression('SUM(AMOUNT)', 'total_amount')
        ->condition($code_condition)
        ->condition('STUDENT_CLASS_ID', $classes_row['STUDENT_CLASS_ID'])
        ->condition('CONSTITUENT_ID', $classes_row['STUDENT_ID'])
        ->condition($condition_or)
		->execute()->fetch();

      // if class dropped and existing fee total is equal to the fee amount, need to determine if to refund
      if ($classes_row['DROPPED'] == 1 AND ($existing_fees['total_amount'] == $classes_row['AMOUNT'] OR $existing_fees['total_amount'] == $classes_row['section_AMOUNT'])) {
        /*
        // get refund schedule for student status
        $refund = $this->database->db_select('BILL_TUITION_RATE_REFUND', 'tuitionraterefund')
          ->fields('tuitionraterefund', array('REFUND_PERCENTAGE'))
          ->join('STUD_STUDENT_STATUS', 'status', 'status.TUITION_RATE_ID = tuitionraterefund.TUITION_RATE_ID')
          ->fields('status', array('TOTAL_CREDITS_ATTEMPTED'))
          ->condition('status.STUDENT_STATUS_ID', $student_status_id)
          ->condition('REFUND_TYPE', 'COURSEFEE')
          ->condition('END_DATE', $classes_row['DROP_DATE'], '>=')
          ->orderBy('END_DATE')
          ->execute()->fetch();
        
        // if 100% refund, refund fee
        if ($refund['REFUND_PERCENTAGE'] == 100) {
          $this->constituent_billing_service->removeCourseFees($classes_row);
        } elseif ($refund['REFUND_PERCENTAGE'] == 50) {
          // if 50% refund, determine if credit total changed

          // if credit total same or increased, refund fee
          if ($classes_row['TOTAL_CREDITS_ATTEMPTED'] >= $previous_credit_total) {
            $this->constituent_billing_service->removeCourseFees($classes_row);
          }
          
        } else {
          // if 0% refund, no refund
          
        }
        */
      } elseif ($classes_row['DROPPED'] == 1) {
        // Look at classes dropped for any refund transactions
        $this->constituent_billing_service->refundCourseFees($classes_row);
      } elseif ($classes_row['DROPPED'] == 0) {
        // class not dropped
        // need to check if total amount of fees is 0, need to bill
        if ($existing_fees['total_amount'] == 0 AND ($classes_row['AMOUNT'] != 0 OR $classes_row['section_AMOUNT'] != 0)) {
          $this->constituent_billing_service->addCourseFees($classes_row['STUDENT_CLASS_ID'], $posted);
          
        } // if not 0, already billed
        
      }

    } // end while for classes

  }
  
}