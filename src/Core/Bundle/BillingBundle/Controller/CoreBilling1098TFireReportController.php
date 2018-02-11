<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;
use Kula\Core\Bundle\ConstituentBundle\Field\SocialSecurityNumber;

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

    $data = "\"SSN\",\"Name\",\"Address\",\"City\",\"State\",\"Zip\",\"Box2\",\"Box4\",\"Box5\",\"Box6\",\"Box7\",\"Box8\",\"Box9\"\n";
/*
SSN
Name
Address
City 
State
Zip
Box2 - Amounts billed for Qualified Tuition and Related Expenses - Elena please prepare a list of qualified related expenses.  Research this at IRS.gov.
Box 4 - This will be adjustments made in 2017 to the tuition that was reported for 2016.  We will need to run a report for Summer 2016, Fall 2016, and Spring 2017 for all transactions that were entered after January 1, 2017 (not dated after January 1 but POSTED after January 1 - let's discuss).   
Box5 - Scholarships and Grants
Box 6 -  This will be adjustments made in 2017 to the scholarships / grants that were reported for 2016.  We will need to run a report for Summer 2016, Fall 2016, and Spring 2017 for all transactions that were entered after January 1, 2017 (not dated after January 1 but POSTED after January 1 - let's discuss).   
Box7 - this will be yes for all records
Box8 - this will be yes for all records 
Box9 - Y = graduate student and post-bacc, N = undergraduate
*/

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

      $data .= '"'.str_replace('-', '', $soc_security_number).'",'; 
      $data .= '"'.$row['LAST_NAME'].', '.$row['FIRST_NAME'].'",';
      $data .= '"'.$row['ADDRESS'].'",';
      $data .= '"'.$row['CITY'].'",';
      $data .= '"'.$row['STATE'].'",';
      $data .= '"'.$row['ZIPCODE'].'",';
      $data .= '"'.round($this->getBoxNumber($row['CONSTITUENT_ID'], $start_date, $end_date, 2)*-1).'",';  // Box 2
      // Box 4
      $data .= '"'.round($this->getBoxNumber($row['CONSTITUENT_ID'], $start_date, $end_date, 5)*-1).'",';  // Box 5
      // Box 6
      $data .= '"Y"'; // Box 7
      $data .= '"Y"'; // Box 8

      // Get Box 9
      $graduate_status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('LEVEL'))
        ->condition('ENTER_DATE', $start_date, '>=')
        ->condition('ENTER_DATE', $end_date, '<=')
        ->condition('STUDENT_ID', $row['CONSTITUENT_ID'])
        ->condition('LEVEL', 'GR')
        ->execute()->fetch();
      if ($graduate_status['LEVEL']) {
        $data .= '"Y"'; // Box 9
      }

      $data .= "\n";

    } // end while

    // Closing line
    return $this->textResponse($data);
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