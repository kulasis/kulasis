<?php

namespace Kula\K12\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreBillingConstituentLedgerReportController extends ReportController {
  
  private $pdf;
  
  private $show_pending_fa;
  private $show_only_with_balances;

  private $student_balances_for_orgterm;
  private $student_balances;
  
  public function indexAction() {
    $this->authorize();
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'Core.K12.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.K12.Student');
    $financial_aid_menu[] = '';
    $financial_aid_year_result = $this->db()->db_select('CORE_TERM', 'term')
      ->fields('term', array('FINANCIAL_AID_YEAR'))
      ->distinct()
      ->orderBy('FINANCIAL_AID_YEAR', 'DESC')
      ->execute();
    while ($financial_aid_year_row = $financial_aid_year_result->fetch()) {
      $financial_aid_menu[$financial_aid_year_row['FINANCIAL_AID_YEAR']] = $financial_aid_year_row['FINANCIAL_AID_YEAR'];
    }
    
    return $this->render('KulaK12BillingBundle:CoreBillingConstituentLedgerReport:reports_billingledger.html.twig', array('fa_menu' => $financial_aid_menu));
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $this->pdf = new \Kula\K12\Bundle\BillingBundle\Report\BillingConstituentLedgerReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;

    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    $record_type = $this->request->request->get('record_type');
    
    $this->fin_aid_year = $this->request->request->get('fa_year');
    
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
    if ($this->session->get('term_id') != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->condition('status.ORGANIZATION_TERM_ID', $org_term_ids)
        ->leftJoin('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_ID = student.STUDENT_ID')
        ->leftJoin('CORE_LOOKUP_VALUES', 'entercode_values', "entercode_values.CODE = status.ENTER_CODE AND entercode_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'K12.Student.Enrollment.EnterCode')")
        ->fields('entercode_values', array('DESCRIPTION' => 'ENTER_CODE'))
        ->leftJoin('CORE_LOOKUP_VALUES', 'grade_values', "grade_values.CODE = status.GRADE AND grvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'K12.Student.Enrollment.Grade')")
        ->fields('grade_values', array('DESCRIPTION' => 'GRADE'))
        ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
        ->leftJoin('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION'))
        ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'));
    }
    
    // Add on selected record
    if (isset($record_id) AND $record_id != '' AND $record_type == 'Core.K12.Student')
      $result = $result->condition('student.STUDENT_ID', $record_id);

    $result = $result
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->orderBy('student.STUDENT_ID', 'ASC')
      ->execute();
    
    while ($row = $result->fetch()) {
      if ($row['bill_ADDRESS']) {
        
        // Get billing addresses
        $billing_addresses_query_conditions = $this->db()->db_or();
        $billing_addresses_query_conditions = $billing_addresses_query_conditions->condition('EFFECTIVE_DATE', null);
        $billing_addresses_query_conditions = $billing_addresses_query_conditions->condition('EFFECTIVE_DATE', date('Y-m-d'), '>=');
        
        $billing_addresses_result = $this->db()->db_select('CONS_ADDRESS', 'address')
          ->fields('address', array('RECIPIENT', 'THOROUGHFARE', 'LOCALITY', 'ADMINISTRATIVE_AREA', 'POSTAL_CODE'))
          ->condition($billing_addresses_query_conditions)
          ->condition('CONSTITUENT_ID', $row['STUDENT_ID'])
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
    
    $transactions = $this->getTransactionsForStudent($student_id);
    
    $do_statement = true;
    if (count($transactions) > 0)
      $do_statement = true;
    else 
      $do_statement = false;
    
    if ($do_statement) {
    
      $this->pdf->balance = 0;
      $this->pdf->previous_balance = 0;
      $this->pdf->term_balance = array();
      $this->pdf->total_term_balance = 0;
      $this->pdf->setData($data);
      $this->pdf->row_count = 1;
      $this->pdf->row_page_count = 1;
      $this->pdf->row_total_count = 1;
      $this->pdf->StartPageGroup();
      $this->pdf->AddPage();
    
      $last_term_id = 0;
      $last_org_id = 0;
      
      if ($this->fin_aid_year) {
        
        $org_term_ids = $this->focus->getOrganizationTermIDs();
        if (isset($org_term_ids) AND count($org_term_ids) > 0) {
    
          // Get current term start date
          $focus_term_info = $this->db()->db_select('CORE_TERM', 'term')
            ->fields('term', array('START_DATE'))
            ->condition('term.TERM_ID', $this->session->get('term_id'))
            ->execute()->fetch();
    
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
            ->condition('transactions.CONSTITUENT_ID', $student_id)
            ->groupBy('CONSTITUENT_ID')
            ->orderBy('CONSTITUENT_ID')->execute()->fetch();

          // Get previous balance and output
          $this->pdf->previous_balance($terms_with_balances_result['total_amount']);
        } 
        
      }
      
      foreach($transactions as $row) {
        if (!isset($this->pdf->term_balance[$row['ORGANIZATION_ABBREVIATION']][$row['TERM_ABBREVIATION']])) {
          $this->pdf->term_balance[$row['ORGANIZATION_ABBREVIATION']][$row['TERM_ABBREVIATION']] = 0;
        }
        
        if ($last_term_id !== 0 AND ($last_term_id != $row['TERM_ABBREVIATION'] OR $last_org_id != $row['ORGANIZATION_ABBREVIATION'])) {
          $this->pdf->total_balance($last_org_id, $last_term_id);
          $this->pdf->term_balance[$last_org_id][$last_term_id] = 0;
          $this->pdf->balance = 0;
        }
        $this->pdf->table_row($row);
        $last_term_id = $row['TERM_ABBREVIATION'];
        $last_org_id = $row['ORGANIZATION_ABBREVIATION'];
      }
      $this->pdf->total_balance($last_org_id, $last_term_id);
      
    }
  }
  
  public function getTransactionsForStudent($student_id) {
    $result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
      ->join('BILL_CODE', 'billcodes', 'billcodes.CODE_ID = transactions.CODE_ID')
      ->fields('billcodes', array('CODE'))
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION', 'ORGANIZATION_ID'))
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'))
      ->condition('transactions.CONSTITUENT_ID', $student_id);
    if ($this->fin_aid_year) {
      $result = $result->condition('term.FINANCIAL_AID_YEAR', $this->fin_aid_year);
    }
    $result = $result
      ->orderBy('term.START_DATE', 'ASC')
      ->orderBy('transactions.TRANSACTION_DATE', 'ASC')
      ->execute();
    
    return $result->fetchAll();
  }
}