<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class SISBillingStatementReportController extends ReportController {
  
  private $pdf;
  
  private $show_pending_fa;
  private $show_only_with_balances;

  private $student_balances_for_orgterm;
  private $student_balances;
  
  public function indexAction() {
    $this->authorize();
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'SIS.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('SIS.HEd.Student');
    return $this->render('KulaHEdBillingBundle:SISBillingStatementReport:reports_billingstatement.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $this->pdf = new \Kula\HEd\Bundle\BillingBundle\Report\BillingStatementReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;

    $report_settings = $this->request->request->get('non');
    if (isset($report_settings['DUE_DATE']))
      $this->pdf->due_date = $report_settings['DUE_DATE'];
    // Pending FA Setting
    if (isset($report_settings['SHOW_PENDING_FA']) AND $report_settings['SHOW_PENDING_FA'] == 'Y')
      $this->show_pending_fa = true;
    else
      $this->show_pending_fa = false;
    
    if (isset($report_settings['ONLY_BALANCES']) AND $report_settings['ONLY_BALANCES'] == 'Y')
      $this->show_only_with_balances = $report_settings['ONLY_BALANCES'];
    elseif (isset($report_settings['ONLY_NEGATIVE_BALANCES']) AND $report_settings['ONLY_NEGATIVE_BALANCES'] == 'Y')
      $this->show_only_with_balances = $report_settings['ONLY_NEGATIVE_BALANCES'];
    else
      $this->show_only_with_balances = false;

    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    $record_type = $this->request->request->get('record_type');
    
    // Get current term start date
    $focus_term_info = $this->db()->db_select('CORE_TERM', 'term')
      ->fields('term', array('START_DATE'))
      ->condition('term.TERM_ID', $this->focus->getTermID())
      ->execute()->fetch();
    
    // Get students with balances
    $students_with_balances_result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('CONSTITUENT_ID'))
      ->expression('SUM(AMOUNT)', 'total_amount')
      ->groupBy('CONSTITUENT_ID')
      ->orderBy('CONSTITUENT_ID');
    
    if ($this->focus->getTermID() != '') {
      $org_term_ids = $this->focus->getOrganizationTermIDs();
      if ($this->focus->getTermID() != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
        $students_with_balances_result = $students_with_balances_result->condition('transactions.ORGANIZATION_TERM_ID', $org_term_ids)
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID');
      }
    }
    
    // Add on selected record
    if (isset($record_id) AND $record_id != '' AND $record_type == 'SIS.HEd.Student')
      $students_with_balances_result = $students_with_balances_result->condition('transactions.CONSTITUENT_ID', $record_id);
    $students_with_balances_result = $students_with_balances_result->execute();
    while ($balance_row = $students_with_balances_result->fetch()) {
      if ($balance_row['total_amount'] > 0 AND isset($report_settings['ONLY_BALANCES']) AND $report_settings['ONLY_BALANCES'])
        $this->student_balances[$balance_row['CONSTITUENT_ID']] = $balance_row;
      elseif ($balance_row['total_amount'] < 0 AND isset($report_settings['ONLY_NEGATIVE_BALANCES']) AND $report_settings['ONLY_NEGATIVE_BALANCES'])
        $this->student_balances[$balance_row['CONSTITUENT_ID']] = $balance_row;
    }
    
    //kula_print_r($this->student_balances);
    //die();
    
    // Get Balances
    $this->student_balances_for_orgterm = array();
    
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0) {
    
      $or_query_conditions = $this->db()->db_or();
      $or_query_conditions = $or_query_conditions->condition('term.TERM_ID', null);
      $or_query_conditions = $or_query_conditions->condition('term.START_DATE', $focus_term_info['START_DATE'], '<');
    
    $terms_with_balances_result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('CONSTITUENT_ID'))
      ->expression('SUM(AMOUNT)', 'total_amount')
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->condition($or_query_conditions)
      ->groupBy('CONSTITUENT_ID')
      ->orderBy('CONSTITUENT_ID');
    //echo $terms_with_balances_result->sql();
    //var_dump($terms_with_balances_result->arguments());
    //die();
    // Add on selected record
    if (isset($record_id) AND $record_id != '' AND $record_type == 'SIS.HEd.Student')
      $terms_with_balances_result = $terms_with_balances_result->condition('transactions.CONSTITUENT_ID', $record_id);
    $terms_with_balances_result = $terms_with_balances_result->execute();
    while ($balance_row = $terms_with_balances_result->fetch()) {
      $this->student_balances_for_orgterm[$balance_row['CONSTITUENT_ID']][] = $balance_row;
    }
    } 
    
    // Get Data and Load
    $result = $this->db()->db_select('STUD_STUDENT', 'student')
      ->fields('student', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->leftJoin('CONS_PHONE', 'phone', 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
      ->fields('phone', array('PHONE_NUMBER'))
      ->leftJoin('CONS_ADDRESS', 'billaddr', 'billaddr.ADDRESS_ID = student.BILLING_ADDRESS_ID')
      ->fields('billaddr', array('THOROUGHFARE' => 'bill_ADDRESS', 'LOCALITY' => 'bill_CITY', 'ADMINISTRATIVE_AREA' => 'bill_STATE', 'POSTAL_CODE' => 'bill_ZIPCODE', 'COUNTRY' => 'bill_COUNTRY'))
      ->leftJoin('CONS_ADDRESS', 'mailaddr', 'mailaddr.ADDRESS_ID = stucon.MAILING_ADDRESS_ID')
      ->fields('mailaddr', array('THOROUGHFARE' => 'mail_ADDRESS', 'LOCALITY' => 'mail_CITY', 'ADMINISTRATIVE_AREA' => 'mail_STATE', 'POSTAL_CODE' => 'mail_ZIPCODE', 'COUNTRY' => 'mail_COUNTRY'))
      ->leftJoin('CONS_ADDRESS', 'residenceaddr', 'residenceaddr.ADDRESS_ID = stucon.RESIDENCE_ADDRESS_ID')
      ->fields('residenceaddr', array('THOROUGHFARE' => 'residence_ADDRESS', 'LOCALITY' => 'residence_CITY', 'ADMINISTRATIVE_AREA' => 'residence_STATE', 'POSTAL_CODE' => 'residence_ZIPCODE', 'COUNTRY' => 'residence_COUNTRY'));
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if ($this->focus->getTermID() != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->condition('status.ORGANIZATION_TERM_ID', $org_term_ids)
        ->leftJoin('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_ID = student.STUDENT_ID')
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
        ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'));
    }
    
    if ($this->show_only_with_balances == 'Y') {
      $result = $result->condition('student.STUDENT_ID', array_keys($this->student_balances));
    }
    
    if ($report_settings['FROM_ADD_DATE'] != '') {
      $result = $result->condition('status.CREATED_TIMESTAMP', date('Y-m-d', strtotime($report_settings['FROM_ADD_DATE'])), '>=');
    }
    
    // Add on selected record
    if (isset($record_id) AND $record_id != '' AND $record_type == 'SIS.HEd.Student')
      $result = $result->condition('student.STUDENT_ID', $record_id);

    if (isset($report_settings['student']) AND $report_settings['student'] != '') {
      
      $exploded_stus = explode(",", $report_settings['student']);
      
      if ($exploded_stus) {
        foreach($exploded_stus as $key => $value) {
          $exploded_stus[$key] = trim($value);
        }
      }
      
      $result = $result->condition('stucon.PERMANENT_NUMBER', $exploded_stus);
    }

    $result = $result
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->orderBy('student.STUDENT_ID', 'ASC');
    //echo $result;
    //var_dump($result->arguments());
    //die();
    $result = $result->execute();
    
    while ($row = $result->fetch()) {
      if ($row['bill_ADDRESS']) {
        
        // Get billing addresses
        $billing_addresses_query_conditions = $this->db()->db_or();
        $billing_addresses_query_conditions = $billing_addresses_query_conditions->condition('EFFECTIVE_DATE', null);
        $billing_addresses_query_conditions = $billing_addresses_query_conditions->condition('EFFECTIVE_DATE', date('Y-m-d'), '<=');
        
        $billing_addresses_result = $this->db()->db_select('CONS_ADDRESS', 'address')
          ->fields('address', array('RECIPIENT', 'THOROUGHFARE', 'LOCALITY', 'ADMINISTRATIVE_AREA', 'POSTAL_CODE'))
          //->condition($billing_addresses_query_conditions)
          ->condition('CONSTITUENT_ID', $row['STUDENT_ID'])
          ->condition('ACTIVE', 1)
          ->condition('UNDELIVERABLE', 0)
          ->condition('ADDRESS_TYPE', 'B')
          ->execute();
        while ($billing_addresses_row = $billing_addresses_result->fetch()) {
          $row['address'] = 'bill';
          $row['billing_address'] = $billing_addresses_row;
          $this->createStatement($row['STUDENT_ID'], $row);
        }
      }
      if ($row['mail_ADDRESS'] OR $row['residence_ADDRESS']) {
        unset($row['address']);
        $row['address'] = 'mail';
        $this->createStatement($row['STUDENT_ID'], $row);
      }
    }
    
    // Closing line
    return $this->pdfResponse($this->pdf->Output('','S'));
    
  }
  
  public function createStatement($student_id, $data) {
    
    if (isset($this->student_balances_for_orgterm[$student_id]))
      $previous_balance = $this->student_balances_for_orgterm[$student_id];
    else 
      $previous_balance = 0;
    
    $transactions = $this->getTransactionsForStudent($student_id);
    
    $do_statement = true;
    //if ($this->show_only_with_balances AND (count($transactions) > 0))
    //  $do_statement = true;

    
    if ($do_statement) {
    
    $this->pdf->balance = 0;
    $this->pdf->setData($data);
    $this->pdf->row_count = 1;
    $this->pdf->row_page_count = 1;
    $this->pdf->row_total_count = 1;
    $this->pdf->StartPageGroup();
    $this->pdf->AddPage();
    
    if (isset($this->student_balances_for_orgterm[$student_id]))
      $this->pdf->previous_balances($this->student_balances_for_orgterm[$student_id]);
    
    
    $last_term_id = 0;
    foreach($transactions as $row) {
      $this->pdf->table_row($row);
      $last_term_id = $row['TERM_ID'];
    }
    if ($this->show_pending_fa) $this->getPendingFinancialAid($student_id, $last_term_id);
    $this->pdf->total_balance();
    $this->getHolds($student_id);
    $this->pdf->remit_payment();
    
    }
  }
  
  public function getTransactionsForStudent($student_id) {
    $result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED'))
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'))
      ->condition('transactions.CONSTITUENT_ID', $student_id)
      ->condition('transactions.SHOW_ON_STATEMENT', 1);
    
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $result = $result->condition('transactions.ORGANIZATION_TERM_ID', $org_term_ids);
    $result = $result
      ->orderBy('term.START_DATE', 'ASC')
      ->orderBy('transactions.TRANSACTION_DATE', 'ASC')
      ->execute();
    
    return $result->fetchAll();
  }
  
  public function getPendingFinancialAid($student_id, $term_id) {
    $awards_result = $this->db()->db_select('FAID_STUDENT_AWARDS', 'faidstuawrds')
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
      ->condition('faidstuawrds.AWARD_STATUS', 'PEND')
      ->condition('faidstuawrds.SHOW_ON_STATEMENT', 1)
      ->condition('faidstuawrds.NET_AMOUNT', 0, '>')
      ->execute();
    while ($awards_row = $awards_result->fetch()) {
      $this->pdf->fa_table_row($awards_row);
    }
    
  }
  
  public function getHolds($student_id) {
    $holds_result = $this->db()->db_select('STUD_STUDENT_HOLDS', 'stuholds')
        ->fields('stuholds', array('STUDENT_HOLD_ID', 'HOLD_ID', 'HOLD_DATE', 'COMMENTS', 'VOIDED', 'VOIDED_REASON', 'VOIDED_TIMESTAMP'))
        ->join('STUD_HOLD', 'hold', 'stuholds.HOLD_ID = hold.HOLD_ID')
        ->fields('hold', array('HOLD_NAME'))
        ->leftJoin('CORE_USER', 'user', 'user.USER_ID = stuholds.VOIDED_USERSTAMP')
        ->fields('user', array('USERNAME'))
        ->condition('stuholds.STUDENT_ID', $student_id)
        ->condition('stuholds.VOIDED', 0)
        ->orderBy('stuholds.HOLD_DATE', 'ASC')
        ->execute();
    $first = 0;
    while ($holds_row = $holds_result->fetch()) {
      if ($first == 0) $this->pdf->holds_header();
      $this->pdf->hold_row($holds_row);
      $first++;
    }
  }
}