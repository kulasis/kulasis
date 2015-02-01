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
    if ($this->request->query->get('record_type') == 'STUDENT' AND $this->request->query->get('record_id') != '') {
      $this->setRecordType('STUDENT');
      
      $student_payments = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->fields('trans', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
        ->join('BILL_CODE', 'code', array('CODE'), 'code.CODE_ID = trans.CODE_ID')
        ->predicate('code.CODE_TYPE', 'P')
        ->predicate('POSTED', 'Y')
        ->predicate('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->order_by('TRANSACTION_DATE', 'DESC');
      $org_term_ids = $this->focus->getOrganizationTermIDs();
      if ($this->session->get('term_id') != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
        $student_payments = $student_payments->predicate('trans.ORGANIZATION_TERM_ID', $org_term_ids);
      }
      $student_payments = $student_payments->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdStudentBillingBundle:BillingReceiptReport:reports_billingreceipt.html.twig', array('student_payments' => $student_payments));
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $this->pdf = new \Kula\Bundle\HEd\StudentBillingBundle\Controller\BillingReceiptReport("P");
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
    $focus_term_info = $this->db()->select('CORE_TERM', 'term')
      ->fields('term', array('START_DATE'))
      ->predicate('term.TERM_ID', $this->session->get('term_id'))
      ->execute()->fetch();

    
    // Get Data and Load
    $result = $this->db()->select('STUD_STUDENT', 'student')
      ->fields('student', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->left_join('CONS_PHONE', 'phone', array('PHONE_NUMBER'), 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
      ->left_join('CONS_ADDRESS', 'billaddr', array('ADDRESS' => 'bill_ADDRESS', 'CITY' => 'bill_CITY', 'STATE' => 'bill_STATE', 'ZIPCODE' => 'bill_ZIPCODE', 'COUNTRY' => 'bill_COUNTRY'), 'billaddr.ADDRESS_ID = student.BILLING_ADDRESS_ID')
      ->left_join('CONS_ADDRESS', 'mailaddr', array('ADDRESS' => 'mail_ADDRESS', 'CITY' => 'mail_CITY', 'STATE' => 'mail_STATE', 'ZIPCODE' => 'mail_ZIPCODE', 'COUNTRY' => 'mail_COUNTRY'), 'mailaddr.ADDRESS_ID = stucon.MAILING_ADDRESS_ID')
      ->left_join('CONS_ADDRESS', 'residenceaddr', array('ADDRESS' => 'residence_ADDRESS', 'CITY' => 'residence_CITY', 'STATE' => 'residence_STATE', 'ZIPCODE' => 'residence_ZIPCODE', 'COUNTRY' => 'residence_COUNTRY'), 'residenceaddr.ADDRESS_ID = stucon.RESIDENCE_ADDRESS_ID');
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if ($this->session->get('term_id') != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->predicate('status.ORGANIZATION_TERM_ID', $org_term_ids)
        ->left_join('STUD_STUDENT_STATUS', 'status', null, 'status.STUDENT_ID = student.STUDENT_ID')
        ->left_join('CORE_LOOKUP_VALUES', 'grade_values', array('DESCRIPTION' => 'GRADE'), 'grade_values.CODE = status.GRADE AND grade_values.LOOKUP_ID = 20')
        ->left_join('CORE_LOOKUP_VALUES', 'entercode_values', array('DESCRIPTION' => 'ENTER_CODE'), 'entercode_values.CODE = status.ENTER_CODE AND entercode_values.LOOKUP_ID = 16')
        ->left_join('STUD_STUDENT_DEGREES', 'studdegrees', null, 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
        ->left_join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'degree.DEGREE_ID = studdegrees.DEGREE_ID')  
        ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
        ->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
        ->left_join('CORE_TERM', 'term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'), 'term.TERM_ID = orgterms.TERM_ID');
    }
    

    // Add on selected record
    if (isset($record_id) AND $record_id != '' AND $record_type == 'STUDENT')
      $result = $result->predicate('student.STUDENT_ID', $record_id);

    $result = $result
      ->order_by('LAST_NAME', 'ASC', 'stucon')
      ->order_by('FIRST_NAME', 'ASC', 'stucon')
      ->order_by('STUDENT_ID', 'ASC', 'student')
      ->execute();
    
    while ($row = $result->fetch()) {
      
      if ($row['bill_ADDRESS']) {
        // Get billing addresses
        $billing_addresses_query_conditions = new \Kula\Component\Database\Query\Predicate('OR');
        $billing_addresses_query_conditions = $billing_addresses_query_conditions->predicate('EFFECTIVE_DATE', null);
        $billing_addresses_query_conditions = $billing_addresses_query_conditions->predicate('EFFECTIVE_DATE', date('Y-m-d'), '>=');
        
        $billing_addresses_result = $this->db()->select('CONS_ADDRESS', 'address')
          ->fields('address', array('RECIPIENT', 'ADDRESS', 'CITY', 'STATE', 'ZIPCODE'))
          ->predicate('CONSTITUENT_ID', $row['STUDENT_ID'])
          ->predicate('ACTIVE', 'Y')
          ->predicate('UNDELIVERABLE', 'N')
          ->predicate('ADDRESS_TYPE', 'B');
        
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
    $result = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
      ->join('BILL_CODE', 'code', array('CODE', 'CODE_TYPE'), 'transactions.CODE_ID = code.CODE_ID')
      ->predicate('transactions.CONSTITUENT_ID', $student_id)
      ->predicate('code.CODE_TYPE', 'P')
      ->predicate('transactions.SHOW_ON_STATEMENT', 'Y')
      ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->left_join('CORE_TERM', 'term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'), 'term.TERM_ID = orgterms.TERM_ID');
        
    if (count($this->selected_transactions) > 0) {
      $result = $result->predicate('transactions.CONSTITUENT_TRANSACTION_ID', $this->selected_transactions);
    }

    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if ($this->session->get('term_id') != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->predicate('transactions.ORGANIZATION_TERM_ID', $org_term_ids);
    }
    $result = $result->execute();
    while ($row = $result->fetch()) {
      $this->pdf->table_row($row);
    }
    
  }

}