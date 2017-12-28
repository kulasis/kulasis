<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreBilling1098TFireReportController extends ReportController {
  
  public function indexAction() {
    $this->authorize();
    
    return $this->render('KulaCoreBillingBundle:CoreBilling1098TReport:fire.html.twig', array());
  }
  
  public function generateAction() {  
    $this->authorize();

    $report_settings = $this->request->request->get('non');

    // Transaction dates
    $start_date = date('Y-m-d', strtotime($report_settings['START_DATE']));
    $end_date = date('Y-m-d', strtotime($report_settings['END_DATE']));

    $year = date('Y', strtotime($report_settings['START_DATE']));

    $container = $GLOBALS['kernel']->getContainer();
    $reportfein = $container->getParameter('report_institution_fein');

    $t_record = '';
    $a_record = '';

    // T Record
    $t_record .= str_pad('T', 1, " ", STR_PAD_LEFT); // Record Type
    $t_record .= str_pad($year, 4, " ", STR_PAD_LEFT); // Payment Year
    $t_record .= str_pad('', 1, " ", STR_PAD_LEFT); // Prior Year Data Indicator
    $t_record .= str_pad(preg_replace('/[^0-9]/','',$reportfein), 9, " ", STR_PAD_LEFT); // Transmitter's TIN
    $t_record .= str_pad($report_settings['TRANSMITTER_CONTROL_CODE'], 5, " ", STR_PAD_LEFT); // Transmitter Control Code
    $t_record .= str_pad("", 7, " ", STR_PAD_LEFT); // Blank
    $t_record .= str_pad((isset($report_settings['TEST_FILE'])) ? $report_settings['TEST_FILE'] : '' , 1, " ", STR_PAD_LEFT); // Test File Indicator
    $t_record .= str_pad((isset($report_settings['FOREIGN_ENTITY_INDICATOR'])) ? $report_settings['FOREIGN_ENTITY_INDICATOR'] : '' , 1, " ", STR_PAD_LEFT);// Foreign Entity Indicator
    $t_record .= str_pad($report_settings['TRANSMITTERS_NAME'], 80, " ", STR_PAD_LEFT); // Transmitter Name and Transmitter Name (Continuation)
    $t_record .= str_pad($report_settings['COMPANY_NAME'], 80, " ", STR_PAD_LEFT); // Company Name and Company Name (Continuation)
    $t_record .= str_pad($report_settings['COMPANY_ADDRESS'], 40, " ", STR_PAD_LEFT); // Company Mailing Address
    $t_record .= str_pad($report_settings['COMPANY_CITY'], 40, " ", STR_PAD_LEFT); // Company City
    $t_record .= str_pad($report_settings['COMPANY_STATE'], 2, " ", STR_PAD_LEFT); // Company State
    $t_record .= str_pad($report_settings['COMPANY_ZIP_CODE'], 9, " ", STR_PAD_LEFT); // Company ZIP Code
    $t_record .= str_pad("", 15, " ", STR_PAD_LEFT); // Blank
    // Total Number of Payees
    // Contact Name
    // Contact Telephone Number & Extension
    // Contact Email Address
    $t_record .= str_pad("", 91, " ", STR_PAD_LEFT); // Blank
    $t_record .= str_pad("1", 8, "0", STR_PAD_RIGHT);  // Record Sequence Number
    // Blank
    // Vendor Indicator
    // Vendor Name
    // Vendor Mailing Address
    // Vendor City
    // Vendor State
    // Vendor Zip Code
    // Vendor Contact Name
    // Vendor Contact Telephone Number & Extnesion
    // Blank
    // Vendor Foreign Entity Indicator
    // Blank
    // Blank

    $a_record = '';
    // A Record
    $a_record .= str_pad('A', 1, " ", STR_PAD_LEFT); // Record Type
    $a_record .= str_pad($year, 4, " ", STR_PAD_LEFT); // Payment Year
    $a_record .= str_pad("", 1, " ", STR_PAD_LEFT); // Combined Federal/State Filing Program
    $a_record .= str_pad("", 5, " ", STR_PAD_LEFT); // Blank
    $a_record .= str_pad(preg_replace('/[^0-9]/','',$reportfein), 9, " ", STR_PAD_LEFT); // Payer's TIN
    $a_record .= str_pad(substr($report_settings['COMPANY_NAME'], 0, 4), 4, " ", STR_PAD_LEFT); // Payer Name Control
    $a_record .= str_pad("", 1, " ", STR_PAD_LEFT); // Last Filing Indicator
    $a_record .= str_pad('8', 1, " ", STR_PAD_LEFT); // Type of Return - 8 or 1098-T
    $a_record .= str_pad('123457', 16, " ", STR_PAD_LEFT); // Amount Codes
    $a_record .= str_pad("", 8, " ", STR_PAD_LEFT); // Blank
    $a_record .= str_pad("", 1, " ", STR_PAD_LEFT); // Foreign Entity Indicator
    $a_record .= str_pad($report_settings['COMPANY_NAME'], 80, " ", STR_PAD_LEFT); // First Payer Name Line
    // Second Payer Name Line
    $a_record .= str_pad("0", 1, " ", STR_PAD_LEFT);// Transfer Agent Indicator
    $a_record .= str_pad($report_settings['COMPANY_ADDRESS'], 40, " ", STR_PAD_LEFT); // Payer Shipping Address
    $a_record .= str_pad($report_settings['COMPANY_CITY'], 40, " ", STR_PAD_LEFT); // Payer City
    $a_record .= str_pad($report_settings['COMPANY_STATE'], 2, " ", STR_PAD_LEFT); // Payer State
    $a_record .= str_pad($report_settings['COMPANY_ZIP_CODE'], 9, " ", STR_PAD_LEFT); // Payer Zip Code
    // Payer's Telephone Number & Extension
    $a_record .= str_pad("", 260, " ", STR_PAD_LEFT);  // Blank
    $a_record .= str_pad("2", 8, "0", STR_PAD_RIGHT); // Record Sequence Number
    $a_record .= str_pad("", 241, " ", STR_PAD_LEFT);  // Blank
    // Blank or CR/LF

    $record_number = 3;
    $b_record = '';
    // Loop through 1098-T records

    $org_term_ids = $this->focus->getOrganizationID();
    // Get students
    // Get Data and Load
    $result = $this->db()->db_select('CONS_CONSTITUENT', 'stucon')
      ->distinct()
      ->fields('stucon', array('CONSTITUENT_ID', 'PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME'))
      ->join('BILL_CONSTITUENT_TRANSACTIONS', 'trans', 'trans.CONSTITUENT_ID = stucon.CONSTITUENT_ID')
      ->condition('trans.TRANSACTION_DATE', $start_date, '>=')
      ->condition('trans.TRANSACTION_DATE', $end_date, '<=')
      ->leftJoin('CONS_ADDRESS', 'addr', 'addr.ADDRESS_ID = stucon.RESIDENCE_ADDRESS_ID')
      ->fields('addr', array('THOROUGHFARE' => 'ADDRESS', 'LOCALITY' => 'CITY', 'ADMINISTRATIVE_AREA' => 'STATE', 'POSTAL_CODE' => 'ZIPCODE'));
    if ($this->focus->getTermID() != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = stucon.CONSTITUENT_ID')
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->condition('org.ORGANIZATION_ID', $org_term_ids);
    }
    $result = $result->orderBy('stucon.LAST_NAME')->orderBy('stucon.FIRST_NAME');
    $result = $result->execute();
    while ($row = $result->fetch()) {

      // Get student ssn
      $soc_security_number_obj = new SocialSecurityNumber($container);
      $soc_security_number = $soc_security_number_obj->calculate($row['CONSTITUENT_ID']);

      // B Record
      $b_record .= str_pad('B', 1, " ", STR_PAD_LEFT); // Record Type
      $b_record .= str_pad($year, 4, " ", STR_PAD_LEFT); // Payment Year
      $b_record .= str_pad('', 1, " ", STR_PAD_LEFT); // Corrected Return Indicator
      $b_record .= str_pad(substr($row['LAST_NAME'], 0, 4), 4, " ", STR_PAD_LEFT); // Name Control
      $b_record .= str_pad('2', 1, " ", STR_PAD_RIGHT); // Type of TIN
      $b_record .= str_pad(str_replace('-', '', $soc_security_number), 9, " ", STR_PAD_RIGHT); // Payee's Taxpayer Identification Number (TIN)
      $b_record .= str_pad($row['PERMANENT_NUMBER'], 20, " ", STR_PAD_LEFT);  // Payer's Account Number for Payee
      $b_record .= str_pad('', 4, " ", STR_PAD_LEFT); // Payer's Office Code
      $b_record .= str_pad('', 10, " ", STR_PAD_LEFT); // Blank
      $b_record .= str_pad($this->getBoxNumber($row['CONSTITUENT_ID'], $start_date, $end_date, 1), 12, "0", STR_PAD_RIGHT); // Payment Amount 1 - Payments received for qualified tuition and related expenses
      $b_record .= str_pad($this->getBoxNumber($row['CONSTITUENT_ID'], $start_date, $end_date, 2), 12, "0", STR_PAD_RIGHT); // Payment Amount 2 - Amounts Billed for Qualified Tuition and Related Expenses
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount 3 - Adjustments made for prior year
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount 4 - Scholarships or grants
      $b_record .= str_pad($this->getBoxNumber($row['CONSTITUENT_ID'], $start_date, $end_date, 5), 12, "0", STR_PAD_RIGHT); // Payment Amount 5 - Adjustments to scholarships or grants for a prior year
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount 6
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount 7 - Reimbursements or refunds of qualified tuition and related expenses from an insurance contract
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount 8
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount 9
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount A
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount B
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount C
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount D
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount E
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount F
      $b_record .= str_pad("", 12, "0", STR_PAD_RIGHT); // Payment Amount G
      $b_record .= str_pad('', 1, " ", STR_PAD_LEFT); // Foreign Country Indicator
      $b_record .= str_pad($row['LAST_NAME'].' '.$row['FIRST_NAME'], 80, " ", STR_PAD_LEFT); // First Payee Name Line
      // Second Payee Name Line
      $b_record .= str_pad('', 40, " ", STR_PAD_LEFT); // Blank
      $b_record .= str_pad($row['ADDRESS'], 40, " ", STR_PAD_LEFT); // Payee Mailing Address
      $b_record .= str_pad('', 40, " ", STR_PAD_LEFT); // Blank
      $b_record .= str_pad($row['CITY'], 40, " ", STR_PAD_LEFT); // Payee City
      $b_record .= str_pad($row['STATE'], 40, " ", STR_PAD_LEFT); // Payee State
      $b_record .= str_pad($row['ZIPCODE'], 40, " ", STR_PAD_LEFT); // Payee Zip Code
      $b_record .= str_pad('', 1, " ", STR_PAD_LEFT); // Blank
      $b_record .= str_pad($record_number, 8, "0", STR_PAD_RIGHT); // Record Sequence Number
      $b_record .= str_pad('', 36, " ", STR_PAD_LEFT); // Blank
      $b_record .= str_pad('1', 1, " ", STR_PAD_LEFT); // TIN Certification
      $b_record .= str_pad('', 2, " ", STR_PAD_LEFT); // Blank
      // Half-time Student Indicator
      $half_time_status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('TOTAL_CREDITS_ATTEMPTED'))
        ->leftJoin('STUD_SCHOOL_TERM_LEVEL', 'school_term_level', 'school_term_level.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID AND school_term_level.LEVEL = stustatus.LEVEL')
        ->fields('school_term_level', array('MIN_PART_TIME_HOURS'))
        ->condition('ENTER_DATE', $start_date, '>=')
        ->condition('ENTER_DATE', $end_date, '<=')
        ->condition('STUDENT_ID', $row['CONSTITUENT_ID'])
        ->execute()->fetch();
      if ($half_time_status['TOTAL_CREDITS_ATTEMPTED'] >= $half_time_status['MIN_PART_TIME_HOURS']) {
        $b_record .= str_pad('1', 1, " ", STR_PAD_LEFT);
      } else {
        $b_record .= str_pad(' ', 1, " ", STR_PAD_LEFT);
      }
      // Graduate Student Indicator
      $graduate_status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('LEVEL'))
        ->condition('ENTER_DATE', $start_date, '>=')
        ->condition('ENTER_DATE', $end_date, '<=')
        ->condition('STUDENT_ID', $row['CONSTITUENT_ID'])
        ->condition('LEVEL', 'GR')
        ->execute()->fetch();
      if ($graduate_status['LEVEL']) {
        $b_record .= str_pad('1', 1, " ", STR_PAD_LEFT);
      } else {
        $b_record .= str_pad(' ', 1, " ", STR_PAD_LEFT);
      }
      $b_record .= str_pad(' ', 1, " ", STR_PAD_LEFT);  // Academic Period Indicator
      $b_record .= str_pad(' ', 1, " ", STR_PAD_LEFT);   // Method of Reporitng Amounts Indicator
      $b_record .= str_pad('', 112, " ", STR_PAD_LEFT); // Blank
      $b_record .= str_pad('', 60, " ", STR_PAD_LEFT); // Special Data Entries
      $b_record .= str_pad('', 26, " ", STR_PAD_LEFT); // Blank
      // Blank or CR/LF

      $record_number++;
    } // end loop

    $c_record = '';
    // C Record
    // Number of Payees
    // Blank
    // Control Total 1
    // Control Total 2
    // Control Total 3
    // Control Total 4
    // Control Total 5
    // Control Total 6
    // Control Total 7
    // Control Total 8
    // Control Total 9
    // Control Total A
    // Control Total B
    // Control Total C
    // Control Total D
    // Control Total E
    // Control Total F
    // Control Total G
    // Blank
    // Record Sequence Number
    // Blank
    // Blank or CR/LF

    $f_record = '';
    // F Record
    // Number of "A" Records
    // Zero
    // Blank
    // Total Number of Payees
    // Blank
    // Record Sequence Number
    // Blank
    // Blank or CR/LF



    // Closing line
    return $this->textResponse($t_record);
  }

  private function getBoxNumber($student, $start_date, $end_date, $box_number) {

    $amount = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
      ->expression('SUM(AMOUNT)', 'total_amount')
      ->join('BILL_CODE', 'code', 'code.CODE_ID = trans.CODE_ID')
      ->condition('trans.CONSTITUENT_ID', $student)
      ->condition('TRANSACTION_DATE', $start_date, '>=')
      ->condition('TRANSACTION_DATE', $end_date, '<=')
      ->condition('code.FORM1098T_BOX'.$box_number, '1')
      ->execute()->fetch();

    return $amount['total_amount'];

  }
}