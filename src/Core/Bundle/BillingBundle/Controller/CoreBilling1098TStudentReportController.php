<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;
use Kula\Core\Bundle\ConstituentBundle\Field\SocialSecurityNumber;

use \setasign\Fpdi;

class CoreBilling1098TStudentReportController extends ReportController {
  
  private $pdf;
  
  public function indexAction() {
    $this->authorize();

    if ($this->request->query->get('record_type') == 'Core.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Student');
    if ($this->request->query->get('record_type') == 'Core.Constituent' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.Constituent');

    return $this->render('KulaCoreBillingBundle:CoreBilling1098TReport:student.html.twig', array('start_date' => date('01/01/Y'), 'end_date' => date('12/31/Y')));
  }
  
  public function generateAction() {

    $this->authorize();
    $kernel = $this->get('kernel');

    $report_settings = $this->request->request->get('non');

    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    $record_type = $this->request->request->get('record_type');

    // Transaction dates
    $start_date = date('Y-m-d', strtotime($report_settings['START_DATE']));
    $end_date = date('Y-m-d', strtotime($report_settings['END_DATE']));

    $year = date('Y', strtotime($report_settings['START_DATE']));

    // initiate FPDI
    $pdf = new Fpdi\Fpdi();
    $pdf->SetFont('Arial', '', 10);

    $org_term_ids = $this->focus->getOrganizationTermIDs();
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
    if (isset($record_id) AND $record_id != '' AND ($record_type == 'Core.HEd.Student' OR $record_type == 'Core.Constituent')) {
      $result = $result->condition('stucon.CONSTITUENT_ID', $record_id);
    }
    $result = $result->execute();
    while ($row = $result->fetch()) {

      $container = $GLOBALS['kernel']->getContainer();

      // add a page
      $pdf->AddPage();
      // set the source file
      $pdf->setSourceFile($kernel->locateResource("@KulaCoreBillingBundle/ReportSource/Form-1098-T-".$year.".pdf"));
      // import page 1
      $tplIdx = $pdf->importPage(1);
      // use the imported page and place it at point 10,10 with a width of 100 mm
      $pdf->useImportedPage($tplIdx);

      // Get filer info
      $institution_name = $container->getParameter('report_institution_name');
      $reportAddressLine1 = $container->getParameter('report_institution_address_line1');
      $reportAddressLine2 = $container->getParameter('report_institution_address_line2');
      $reportPhoneLine1 = $container->getParameter('report_institution_phone_line1');
      $reportfein = $container->getParameter('report_institution_fein');

      $pdf->SetLeftMargin(19);
      $pdf->setY(20);
      $pdf->Cell(20,5, $institution_name, '', 0,'L');
      $pdf->Ln(4);
      $pdf->Cell(20,5, $reportAddressLine1, '', 0,'L');
      $pdf->Ln(4);
      $pdf->Cell(20,5, $reportAddressLine2, '', 0,'L');
      $pdf->Ln(4);
      $pdf->Cell(20,5, $reportPhoneLine1, '', 0,'L');
      $pdf->Ln(9);
      $pdf->Cell(43,5, $reportfein, '', 0,'L');

      // Get student ssn
      $soc_security_number_obj = new SocialSecurityNumber($container);
      $soc_security_number = $soc_security_number_obj->calculate($row['CONSTITUENT_ID']);
      $pdf->Cell(20,5, $soc_security_number, '', 0,'L');
      $pdf->Ln(9);

      // Get student name
      $pdf->Cell(20,5, $row['LAST_NAME'].', '.$row['FIRST_NAME'], '', 0,'L');
      $pdf->Ln(12);

      // Get address
      $pdf->Cell(20,5, $row['ADDRESS'], '', 0,'L');
      $pdf->Ln(9);
      $pdf->Cell(20,5, $row['CITY'].', '.$row['STATE'].' '.$row['ZIPCODE'], '', 0,'L');

      // Second Column
      $pdf->SetLeftMargin(107);
      $pdf->setY(21);
      // Get Box 1
      $pdf->Cell(20,5, $this->getBoxNumber($row['CONSTITUENT_ID'], $start_date, $end_date, 1), '', 0,'L'); 
      $pdf->Ln(12.6);
      // Get Box 2
      $pdf->Cell(20,5, $this->getBoxNumber($row['CONSTITUENT_ID'], $start_date, $end_date, 2), '', 0,'L'); 
      $pdf->Ln(45.8);

      // Get Box 9
      $graduate_status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('LEVEL'))
        ->condition('ENTER_DATE', $start_date, '>=')
        ->condition('ENTER_DATE', $end_date, '<=')
        ->condition('STUDENT_ID', $row['CONSTITUENT_ID'])
        ->condition('LEVEL', 'GR')
        ->execute()->fetch();
      if ($graduate_status['LEVEL']) {
        $pdf->Cell(32,5, 'X', '', 0,'R'); 
      }

      // Third column
      $pdf->SetLeftMargin(142);
      $pdf->setY(54.5);
      // Get Box 5
      $pdf->Cell(20,5, $this->getBoxNumber($row['CONSTITUENT_ID'], $start_date, $end_date, 5), '', 0,'L'); 



    } // end while looping through students

    // Closing line
    return $this->pdfResponse($pdf->Output());
    
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