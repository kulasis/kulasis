<?php

namespace Kula\Core\Bundle\BillingBundle\Service;

class StatementService {
  
  // Additional services
  protected $db;
  protected $focus;

  // configuration values
  protected $show_pending_fa;
  protected $show_only_positive_balances;
  protected $show_only_positive_balances_fa;
  protected $show_only_negative_balances;
  protected $show_only_focus_organization_balances;

  protected $due_date;
  protected $from_add_date;
  
  // processing holders
  protected $student_balances_for_orgterm;
  protected $student_balances;
  protected $students;
  protected $focus_term_info;
  protected $statement_balance;

  protected $statements;
  
  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              $focus = null) {
    $this->db = $db;
    $this->focus = $focus;
  }

  public function setConfiguration($config) {
    $this->show_pending_fa = (isset($config['SHOW_PENDING_FA']) AND $config['SHOW_PENDING_FA'] == 'Y') ? true : false;
    $this->show_only_positive_balances = (isset($config['ONLY_BALANCES']) AND $config['ONLY_BALANCES'] == 'Y') ? true : false;
    $this->show_only_positive_balances_fa = (isset($config['ONLY_BALANCES_FA']) AND $config['ONLY_BALANCES_FA'] == 'Y') ? true : false;
    $this->show_only_negative_balances = (isset($config['ONLY_NEGATIVE_BALANCES']) AND $config['ONLY_NEGATIVE_BALANCES'] == 'Y') ? true : false;
    $this->due_date = (isset($config['DUE_DATE'])) ? $config['DUE_DATE'] : false;
    $this->show_only_focus_organization_balances = (isset($config['ONLY_FOCUS_ORGANIZATION_BALANCES']) AND $config['ONLY_FOCUS_ORGANIZATION_BALANCES'] == 'Y') ? true : false;
  }

  public function getStatements() {
    return $this->statements;
  }

  public function generateStatements($students) {

    // Get current term start date
    $this->focus_term_info = $this->db->db_select('CORE_TERM', 'term')
      ->fields('term', array('START_DATE', 'END_DATE'))
      ->condition('term.TERM_ID', $this->focus->getTermID())
      ->execute()->fetch();

    // Get students to consider
    if (is_array($students) AND count($students) > 0 AND $students[0] != '') {
      $this->students = $students;
    } else {
      $this->determineStudents();
    }

    // Calculate students with balances
    $this->determineStudentBalances();

    // Adjust student list for balances, if necessary
    $this->adjustStudents();

    // Loop through students and generate statements
    if (count($this->students)) {
    foreach($this->students as $student) {
      // reset statement balance
      $this->statement_balance = 0;
      // Generate statements
      $this->createStatement($student);
    } // end if on foreach
    } // end if on count 

  }

  protected function determineStudents() {

    if ($this->focus->getTermID() != '') {
      $students_to_consider_result = $this->db->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_ID'));
      $org_term_ids = $this->focus->getOrganizationTermIDs();
      if ($this->focus->getTermID() != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
        $students_to_consider_result = $students_to_consider_result->condition('stustatus.ORGANIZATION_TERM_ID', $org_term_ids)
          ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
          ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
          ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID');
      }
      $students_to_consider_result = $students_to_consider_result->execute();
      while ($students_to_consider_row = $students_to_consider_result->fetch()) {
        $this->students[] = $students_to_consider_row['STUDENT_ID'];
      }
    }  

  }

  protected function determineStudentBalances() {

    // Get students with balances
    $students_with_balances_result = $this->db->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('CONSTITUENT_ID'))
      ->expression('SUM(AMOUNT)', 'total_amount')
      ->condition('transactions.CONSTITUENT_ID', $this->students, 'IN')
      ->groupBy('CONSTITUENT_ID')
      ->orderBy('CONSTITUENT_ID');

    if ($this->focus->getTermID() != '') {
      $students_with_balances_result = $students_with_balances_result
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->condition('term.END_DATE', $this->focus_term_info['END_DATE'], '<=')
        ->condition('org.ORGANIZATION_ID', $this->focus->getOrganizationID());
    }

    $students_with_balances_result = $students_with_balances_result->execute();
    while ($balance_row = $students_with_balances_result->fetch()) {
      if ($balance_row['total_amount'] > 0 AND $this->show_only_positive_balances)
        $this->student_balances[$balance_row['CONSTITUENT_ID']] = $balance_row;
      elseif ($balance_row['total_amount'] < 0 AND $this->show_only_negative_balances)
        $this->student_balances[$balance_row['CONSTITUENT_ID']] = $balance_row;
    }

  }

  protected function adjustStudents() {

    // Remove FA Balances
    if ($this->show_pending_fa AND count($this->student_balances) > 0) {
      
      foreach($this->student_balances as $student_id => $student) {
  
        $pending_amount = 0;
        $awards_result = $this->db->db_select('FAID_STUDENT_AWARDS', 'faidstuawrds')
          ->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
          ->join('FAID_AWARD_CODE', 'awardcode', 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
          ->fields('awardcode', array('AWARD_DESCRIPTION'))
          ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm', 'faidstuawrds.AWARD_YEAR_TERM_ID = faidstuawrdyrtrm.AWARD_YEAR_TERM_ID')
          ->fields('faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE'))
          ->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
          ->fields('faidstuawardyr', array('AWARD_YEAR'))
          ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
          ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
          ->fields('org', array('ORGANIZATION_ABBREVIATION'))
          ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
          ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION'))
          ->condition('faidstuawardyr.STUDENT_ID', $student_id);
        if ($this->focus->getTermID()) {
          $awards_result = $awards_result->condition('term.TERM_ID', $this->focus->getTermID());
        }
        $awards_result = $awards_result->condition('faidstuawrds.AWARD_STATUS', array('PEND', 'APPR', 'AWAR'))
          ->condition('faidstuawrds.NET_AMOUNT', 0, '>')
          ->execute();
        while ($awards_row = $awards_result->fetch()) {

          if ($awards_row['AWARD_STATUS'] == 'AWAR') {

            // Check if fully awarded on bill
            $trans_awards = $this->db->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
              ->expression('SUM(AMOUNT)', 'total_amount')
              ->condition('transactions.CONSTITUENT_ID', $student_id)
              ->condition('transactions.AWARD_ID', $awards_row['AWARD_ID'])
              ->execute()->fetch();

            if (-1 * $trans_awards['total_amount'] < $awards_row['NET_AMOUNT']) {
              $pending_amount += $awards_row['NET_AMOUNT'] - (-1*$trans_awards['total_amount']);
            }
            
          } else {
            $pending_amount += $awards_row['NET_AMOUNT'];
          }
          
        } // end while on financial aid records

        // Determine if remove from list
        if ($student['total_amount'] - $pending_amount <= 0) {
          unset($this->student_balances[$student_id]);
        }

      } // end foreach

    } // end if on FA removal
      
    // Get Balances
    $this->student_balances_for_orgterm = array();
    
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0) {
    
      $or_query_conditions = $this->db->db_or();
      $or_query_conditions = $or_query_conditions->condition('term.TERM_ID', null);
      $or_query_conditions = $or_query_conditions->condition('term.START_DATE', $this->focus_term_info['START_DATE'], '<');
    
      $terms_with_balances_result = $this->db->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
        ->fields('transactions', array('CONSTITUENT_ID'))
        ->expression('SUM(AMOUNT)', 'total_amount')
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->condition('transactions.CONSTITUENT_ID', $this->students, 'IN')
        ->condition($or_query_conditions);
      if ($this->show_only_focus_organization_balances ) {
        $terms_with_balances_result = $terms_with_balances_result->condition('org.ORGANIZATION_ID', $this->focus->getSchoolIDs());
      }
      $terms_with_balances_result = $terms_with_balances_result->groupBy('CONSTITUENT_ID')
        ->orderBy('CONSTITUENT_ID');

      $terms_with_balances_result = $terms_with_balances_result->execute();
      while ($balance_row = $terms_with_balances_result->fetch()) {
        if ($balance_row['total_amount'] != 0) {
          $this->student_balances_for_orgterm[$balance_row['CONSTITUENT_ID']] = $balance_row['total_amount'];
        }
      } // end while
    } // end if on order_terms

  }

  protected function createStatement($student_id) {

    if (isset($this->student_balances_for_orgterm[$student_id])) {
      $this->statements[$student_id]['transactions'][] = array(
        'TRANSACTION_DATE' => '',
        'ORGANIZATION_ABBREVIATION' => '',
        'TERM_ABBREVIATION' => '',
        'TRANSACTION_DESCRIPTION' => 'Previous Balance',
        'AMOUNT' => $this->student_balances_for_orgterm[$student_id]
      );
      $this->statements[$student_id]['previous_balance'] = $this->student_balances_for_orgterm[$student_id];
      $this->statement_balance += $this->student_balances_for_orgterm[$student_id];
      
    }
    $this->addStudent($student_id);
    $this->addStudentAddresses($student_id);
    $this->addStudentEmailAddresses($student_id);
    $this->addStudentStatus($student_id);
    $this->addTransactionsForStudent($student_id);
    if ($this->show_pending_fa AND 
        isset($this->statements[$student_id]['transactions']) AND 
        is_array($this->statements[$student_id]['transactions'])
    ) {
      // determine last term id
      $last_transaction = end($this->statements[$student_id]['transactions']);
      if (isset($last_transaction['TERM_ID'])) {
        $this->getPendingFinancialAid($student_id, $last_transaction['TERM_ID']);
      }
    } // end if on showing pending FA
    $this->statements[$student_id]['balance'] = number_format(bcdiv($this->statement_balance, 100), 2);
    $this->addHolds($student_id);
      
  }

  public function addStudent($student_id) {
    // Get Data and Load
    $result = $this->db->db_select('CONS_CONSTITUENT', 'stucon')
      ->fields('stucon', array('CONSTITUENT_ID', 'PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->join('STUD_STUDENT', 'student', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('student', array('STUDENT_ID'))
      ->leftJoin('CONS_PHONE', 'phone', 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
      ->fields('phone', array('PHONE_NUMBER'))
      ->condition('student.STUDENT_ID', $student_id)
      ->execute()->fetch();
    $this->statements[$student_id]['student'] = $result;
  }

  public function addStudentAddresses($student_id) {
    // Get Data and Load
    $result = $this->db->db_select('CONS_CONSTITUENT', 'stucon')
      ->fields('stucon', array('CONSTITUENT_ID', 'PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->join('STUD_STUDENT', 'student', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('student', array('STUDENT_ID'))
      ->leftJoin('CONS_ADDRESS', 'billaddr', 'billaddr.ADDRESS_ID = student.BILLING_ADDRESS_ID AND billaddr.UNDELIVERABLE = 0')
      ->fields('billaddr', array('THOROUGHFARE' => 'bill_ADDRESS', 'LOCALITY' => 'bill_CITY', 'ADMINISTRATIVE_AREA' => 'bill_STATE', 'POSTAL_CODE' => 'bill_ZIPCODE', 'COUNTRY' => 'bill_COUNTRY', 'RECIPIENT' => 'bill_recipient'))
      ->leftJoin('CONS_ADDRESS', 'mailaddr', 'mailaddr.ADDRESS_ID = stucon.MAILING_ADDRESS_ID AND mailaddr.UNDELIVERABLE = 0')
      ->fields('mailaddr', array('THOROUGHFARE' => 'mail_ADDRESS', 'LOCALITY' => 'mail_CITY', 'ADMINISTRATIVE_AREA' => 'mail_STATE', 'POSTAL_CODE' => 'mail_ZIPCODE', 'COUNTRY' => 'mail_COUNTRY'))
      ->leftJoin('CONS_ADDRESS', 'residenceaddr', 'residenceaddr.ADDRESS_ID = stucon.RESIDENCE_ADDRESS_ID AND residenceaddr.UNDELIVERABLE = 0')
      ->fields('residenceaddr', array('THOROUGHFARE' => 'residence_ADDRESS', 'LOCALITY' => 'residence_CITY', 'ADMINISTRATIVE_AREA' => 'residence_STATE', 'POSTAL_CODE' => 'residence_ZIPCODE', 'COUNTRY' => 'residence_COUNTRY'))
      ->condition('student.STUDENT_ID', $student_id)
      ->execute();
    while ($row = $result->fetch()) {
      $this->statements[$student_id]['addresses'][] = $row;
    }
  }

  public function addStudentEmailAddresses($student_id) {
    // Get student email addresses
    $result = $this->db->db_select('CONS_CONSTITUENT', 'stucon')
    ->fields('stucon', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME'))
    ->join('STUD_STUDENT', 'student', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
    ->fields('student', array('STUDENT_ID'))
    ->join('CONS_EMAIL_ADDRESS', 'email', 'email.CONSTITUENT_ID = stucon.CONSTITUENT_ID')
    ->fields('email', array('EMAIL_ADDRESS'))
    ->condition('student.STUDENT_ID', $student_id)
    ->condition('email.UNDELIVERABLE', 0)
    ->execute();
  while ($row = $result->fetch()) {
    $this->statements[$student_id]['email_addresses'][] = $row;
  }
  }

  public function addStudentStatus($student_id) {

    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if ($this->focus->getTermID() != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
      $this->statements[$student_id]['status'] = $this->db->db_select('STUD_STUDENT_STATUS', 'status')
        ->fields('status', array('PAYMENT_PLAN'))
        ->leftJoin('CORE_LOOKUP_VALUES', 'grade_values', "grade_values.CODE = status.GRADE AND grade_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Grade')")
        ->fields('grade_values', array('DESCRIPTION' => 'GRADE'))
        ->leftJoin('CORE_LOOKUP_VALUES', 'entercode_values', "entercode_values.CODE = status.ENTER_CODE AND entercode_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.EnterCode')")
        ->fields('entercode_values', array('DESCRIPTION' => 'ENTER_CODE'))
        ->leftJoin('STUD_STUDENT_DEGREES', 'studdegrees', 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
        ->leftJoin('STUD_DEGREE', 'degree', 'degree.DEGREE_ID = studdegrees.DEGREE_ID')  
        ->fields('degree', array('DEGREE_NAME'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'))
        ->condition('orgterms.ORGANIZATION_TERM_ID', $org_term_ids, 'IN')
        ->condition('status.STUDENT_ID', $student_id)
        ->orderBy('term.START_DATE', 'DESC')
        ->execute()->fetch();
    }

  }
  
  public function addTransactionsForStudent($student_id) {
    $result = $this->db->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED'))
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'))
      ->condition('transactions.CONSTITUENT_ID', $student_id);
    
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $result = $result->condition('transactions.ORGANIZATION_TERM_ID', $org_term_ids);
    $result = $result
      ->orderBy('term.START_DATE', 'ASC')
      ->orderBy('transactions.TRANSACTION_DATE', 'ASC')
      ->execute();
    while ($row = $result->fetch()) {
      $this->statement_balance += intval(bcmul($row['AMOUNT'], 100));
      $row['AMOUNT'] = number_format($row['AMOUNT'], 2);
      $row['balance'] = number_format(bcdiv($this->statement_balance, 100), 2);
      $this->statements[$student_id]['transactions'][] = $row;
    }
  }
  
  public function getPendingFinancialAid($student_id, $term_id) {
    $awards_result = $this->db->db_select('FAID_STUDENT_AWARDS', 'faidstuawrds')
      ->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
      ->join('FAID_AWARD_CODE', 'awardcode', 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
      ->fields('awardcode', array('AWARD_DESCRIPTION'))
      ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm', 'faidstuawrds.AWARD_YEAR_TERM_ID = faidstuawrdyrtrm.AWARD_YEAR_TERM_ID')
      ->fields('faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE'))
      ->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
      ->fields('faidstuawardyr', array('AWARD_YEAR'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION'))
      ->condition('faidstuawardyr.STUDENT_ID', $student_id)
      ->condition('term.TERM_ID', $term_id)
      ->condition('faidstuawrds.AWARD_STATUS', array('PEND', 'APPR', 'AWAR'))
      ->condition('faidstuawrds.NET_AMOUNT', 0, '>')
      ->condition('faidstuawrds.SHOW_ON_STATEMENT', 1)
      ->execute();
    while ($awards_row = $awards_result->fetch()) {

      if ($awards_row['AWARD_STATUS'] == 'AWAR') {

        // Check if fully awarded on bill
        $trans_awards = $this->db->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
          ->expression('SUM(AMOUNT)', 'total_amount')
          ->condition('transactions.CONSTITUENT_ID', $student_id)
          ->condition('transactions.AWARD_ID', $awards_row['AWARD_ID'])
          ->execute()->fetch();

        if (-1 * $trans_awards['total_amount'] < $awards_row['NET_AMOUNT']) {
          $awards_row['NET_AMOUNT'] = $awards_row['NET_AMOUNT'] - (-1*$trans_awards['total_amount']);
          $awards_row['amount'] = $awards_row['NET_AMOUNT'] * -1;
          $this->statement_balance += intval(bcmul($awards_row['amount'], 100));
          $awards_row['amount'] = number_format($awards_row['amount'], 2);
          $awards_row['balance'] = number_format(bcdiv($this->statement_balance, 100), 2);
          $this->statements[$student_id]['pending_fa'][] = $awards_row;
        }
        
      } else {
        $awards_row['amount'] = $awards_row['NET_AMOUNT'] * -1;
        $this->statement_balance += intval(bcmul($awards_row['amount'], 100));
        $awards_row['amount'] = number_format($awards_row['amount'], 2);
        $awards_row['balance'] = number_format(bcdiv($this->statement_balance, 100), 2);
        $this->statements[$student_id]['pending_fa'][] = $awards_row;
      }
      
    }
    
  }
  
  public function addHolds($student_id) {
    $holds_result = $this->db->db_select('STUD_STUDENT_HOLDS', 'stuholds')
        ->fields('stuholds', array('STUDENT_HOLD_ID', 'HOLD_ID', 'HOLD_DATE', 'COMMENTS', 'VOIDED', 'VOIDED_REASON', 'VOIDED_TIMESTAMP'))
        ->join('STUD_HOLD', 'hold', 'stuholds.HOLD_ID = hold.HOLD_ID')
        ->fields('hold', array('HOLD_NAME'))
        ->leftJoin('CORE_USER', 'user', 'user.USER_ID = stuholds.VOIDED_USERSTAMP')
        ->fields('user', array('USERNAME'))
        ->condition('stuholds.STUDENT_ID', $student_id)
        ->condition('stuholds.VOIDED', 0)
        ->orderBy('stuholds.HOLD_DATE', 'ASC')
        ->execute();
    while ($holds_row = $holds_result->fetch()) {
      $this->statements[$student_id]['holds'][] = $holds_row;
    }
  }

}