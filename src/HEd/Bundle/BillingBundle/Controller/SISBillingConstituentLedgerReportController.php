<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\KulaCoreFrameworkBundle\Controller\ReportController;

class SISBillingConstituentLedgerReportController extends ReportController {
  
  private $pdf;
  
  private $show_pending_fa;
  private $show_only_with_balances;

  private $student_balances_for_orgterm;
  private $student_balances;
  
  public function indexAction() {
    $this->authorize();
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'STUDENT' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('STUDENT');
    $financial_aid_menu[] = '';
    $financial_aid_year_result = $this->db()->select('CORE_TERM', 'term')
      ->fields('term', array('FINANCIAL_AID_YEAR'))
      ->distinct()
      ->order_by('FINANCIAL_AID_YEAR', 'DESC')
      ->execute();
    while ($financial_aid_year_row = $financial_aid_year_result->fetch()) {
      $financial_aid_menu[$financial_aid_year_row['FINANCIAL_AID_YEAR']] = $financial_aid_year_row['FINANCIAL_AID_YEAR'];
    }
    
    return $this->render('KulaHEdStudentBillingBundle:BillingConstituentLedgerReport:reports_billingledger.html.twig', array('fa_menu' => $financial_aid_menu));
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $this->pdf = new \Kula\Bundle\HEd\StudentBillingBundle\Controller\BillingConstituentLedgerReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;

    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    $record_type = $this->request->request->get('record_type');
    
    $this->fin_aid_year = $this->request->request->get('fa_year');
    
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
        ->left_join('CORE_LOOKUP_VALUES', 'entercode_values', array('DESCRIPTION' => 'ENTER_CODE'), 'entercode_values.CODE = status.ENTER_CODE AND entercode_values.LOOKUP_ID = 16')
        ->left_join('CORE_LOOKUP_VALUES', 'grade_values', array('DESCRIPTION' => 'GRADE'), 'grade_values.CODE = status.GRADE AND grade_values.LOOKUP_ID = 20')
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
          ->predicate($billing_addresses_query_conditions)
          ->predicate('CONSTITUENT_ID', $row['STUDENT_ID'])
          ->predicate('ADDRESS_TYPE', 'B')
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
          $focus_term_info = $this->db()->select('CORE_TERM', 'term')
            ->fields('term', array('START_DATE'))
            ->predicate('term.TERM_ID', $this->session->get('term_id'))
            ->execute()->fetch();
    
          $or_query_conditions = new \Kula\Component\Database\Query\Predicate('OR');
          $or_query_conditions = $or_query_conditions->predicate('term.TERM_ID', null);
          $or_query_conditions = $or_query_conditions->predicate('term.START_DATE', $focus_term_info['START_DATE'], '<');
    
          $terms_with_balances_result = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
            ->fields('transactions', array('CONSTITUENT_ID'))
            ->expressions(array('SUM(AMOUNT)' => 'total_amount'))
            ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
            ->left_join('CORE_ORGANIZATION', 'org', null, 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
            ->left_join('CORE_TERM', 'term', null, 'term.TERM_ID = orgterms.TERM_ID')
            ->predicate($or_query_conditions)
            ->predicate('transactions.CONSTITUENT_ID', $student_id)
            ->group_by('CONSTITUENT_ID')
            ->order_by('CONSTITUENT_ID')->execute()->fetch();

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
    $result = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
      ->join('BILL_CODE', 'billcodes', array('CODE'), 'billcodes.CODE_ID = transactions.CODE_ID')
      ->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION', 'ORGANIZATION_ID'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->left_join('CORE_TERM', 'term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'), 'term.TERM_ID = orgterms.TERM_ID')
      ->predicate('transactions.CONSTITUENT_ID', $student_id);
    if ($this->fin_aid_year) {
      $result = $result->predicate('term.FINANCIAL_AID_YEAR', $this->fin_aid_year);
    }
    $result = $result
      ->order_by('START_DATE', 'ASC', 'term')
      ->order_by('TRANSACTION_DATE', 'ASC', 'transactions')
      ->execute();
    
    return $result->fetchAll();
  }
}