<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class SISBillingReceiptReportController extends ReportController {
  
  private $pdf;
  
  private $selected_transactions;

  private $student_balances_for_orgterm;
  private $student_balances;
  
  public function indexAction() {
    $this->authorize();
    $student_payments = array();
    if ($this->request->query->get('record_type') == 'SIS.HEd.Student' AND $this->request->query->get('record_id') != '') {
      $this->setRecordType('SIS.HEd.Student');
      
      $student_payments = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->fields('trans', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
        ->join('BILL_CODE', 'code', 'code.CODE_ID = trans.CODE_ID')
        ->fields('code', array('CODE'))
        ->condition('code.CODE_TYPE', 'P')
        ->condition('POSTED', 1)
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('TRANSACTION_DATE', 'DESC');
      $org_term_ids = $this->focus->getOrganizationTermIDs();
      if ($this->session->get('term_id') != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
        $student_payments = $student_payments->condition('trans.ORGANIZATION_TERM_ID', $org_term_ids);
      }
      $student_payments = $student_payments->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdBillingBundle:SISBillingReceiptReport:reports_billingreceipt.html.twig', array('student_payments' => $student_payments));
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $this->pdf = new \Kula\HEd\Bundle\BillingBundle\Report\BillingReceiptReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;

    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    $record_type = $this->request->request->get('record_type');
    
    // Get selected transactions
    $non = $this->request->request->get('non');
    if (isset($non['receipts']))
      $this->selected_transactions = $non['receipts'];
    
    // Get current term start date
    $focus_term_info = $this->db()->db_select('CORE_TERM', 'term')
      ->fields('term', array('START_DATE'))
      ->condition('term.TERM_ID', $this->session->get('term_id'))
      ->execute()->fetch();

    
    // Get Data and Load
    $result = $this->db()->db_select('STUD_STUDENT', 'student')
      ->fields('student', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->leftJoin('CONS_PHONE', 'phone', 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
      ->fields('phone', array('PHONE_NUMBER'))
      ->leftJoin('CONS_ADDRESS', 'billaddr', 'billaddr.ADDRESS_ID = student.BILLING_ADDRESS_ID')
      ->fields('billaddr', array('THOROUGHFARE' => 'bill_ADDRESS', 'ADMINISTRATIVE_AREA' => 'bill_CITY', 'LOCALITY' => 'bill_STATE', 'POSTAL_CODE' => 'bill_ZIPCODE', 'COUNTRY' => 'bill_COUNTRY'))
      ->leftJoin('CONS_ADDRESS', 'mailaddr', 'mailaddr.ADDRESS_ID = stucon.MAILING_ADDRESS_ID')
      ->fields('mailaddr', array('THOROUGHFARE' => 'mail_ADDRESS', 'ADMINISTRATIVE_AREA' => 'mail_CITY', 'LOCALITY' => 'mail_STATE', 'POSTAL_CODE' => 'mail_ZIPCODE', 'COUNTRY' => 'mail_COUNTRY'))
      ->leftJoin('CONS_ADDRESS', 'residenceaddr', 'residenceaddr.ADDRESS_ID = stucon.RESIDENCE_ADDRESS_ID')
      ->fields('residenceaddr', array('THOROUGHFARE' => 'residence_ADDRESS', 'ADMINISTRATIVE_AREA' => 'residence_CITY', 'LOCALITY' => 'residence_STATE', 'POSTAL_CODE' => 'residence_ZIPCODE', 'COUNTRY' => 'residence_COUNTRY'));
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if ($this->session->get('term_id') != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->condition('status.ORGANIZATION_TERM_ID', $org_term_ids)
        ->leftJoin('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_ID = student.STUDENT_ID')
        ->leftJoin('CORE_LOOKUP_VALUES', 'grade_values', "grade_values.CODE = status.GRADE AND grvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Grade')")
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
    

    // Add on selected record
    if (isset($record_id) AND $record_id != '' AND $record_type == 'SIS.HEd.Student')
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
          ->condition('CONSTITUENT_ID', $row['STUDENT_ID'])
          ->condition('ACTIVE', 1)
          ->condition('UNDELIVERABLE', 0)
          ->condition('ADDRESS_TYPE', 'B');
        
        $billing_addresses_result = $billing_addresses_result->execute();
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
    
    $this->pdf->setData($data);
    $this->pdf->row_count = 1;
    $this->pdf->row_page_count = 1;
    $this->pdf->row_total_count = 1;
    $this->pdf->StartPageGroup();
    $this->pdf->AddPage();
    
    // Get Transactions
    $result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
      ->join('BILL_CODE', 'code', 'transactions.CODE_ID = code.CODE_ID')
      ->fields('code', array('CODE', 'CODE_TYPE'))
      ->condition('transactions.CONSTITUENT_ID', $student_id)
      ->condition('code.CODE_TYPE', 'P')
      ->condition('transactions.SHOW_ON_STATEMENT', 1)
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'));
        
    if (count($this->selected_transactions) > 0) {
      $result = $result->condition('transactions.CONSTITUENT_TRANSACTION_ID', $this->selected_transactions);
    }

    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if ($this->session->get('term_id') != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->condition('transactions.ORGANIZATION_TERM_ID', $org_term_ids);
    }
    $result = $result->execute();
    while ($row = $result->fetch()) {
      $this->pdf->table_row($row);
    }
    
  }

}