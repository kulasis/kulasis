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
    $tcc = $report_settings['TRANSMITTER_CONTROL_CODE'];
    $test_file = (isset($report_settings['TEST_FILE'])) ? $report_settings['TEST_FILE'] : '';

    $year = date('Y', strtotime($report_settings['START_DATE']));

    $container = $GLOBALS['kernel']->getContainer();
    $reportfein = $container->getParameter('report_institution_fein');

    $file = '';

    // T Record
    $file .= str_pad('T', 1, " ", STR_PAD_LEFT); // Record Type
    $file .= str_pad($year, 4, " ", STR_PAD_LEFT); // Payment Year
    $file .= str_pad('', 1, " ", STR_PAD_LEFT); // Prior Year Data Indicator
    $file .= str_pad(preg_replace('/[^0-9]/','',$reportfein), 9, " ", STR_PAD_LEFT); // Transmitter's TIN
    $file .= str_pad($tcc, 5, " ", STR_PAD_LEFT); // Transmitter Control Code
    $file .= str_pad("", 7, " ", STR_PAD_LEFT); // Blank
    $file .= str_pad($test_file , 1, " ", STR_PAD_LEFT); // Test File Indicator
    // Foreign Entity Indicator
    // Transmitter Name
    // Transmitter Name (Continuation)
    // Company Name
    // Company Name (Continuation)
    // Company Mailing Address
    // Company City
    // Company State
    // Company ZIP Code
    // Blank
    // Total Number of Payees
    // Contact Name
    // Contact Telephone Number & Extension
    // Contact Email Address
    // Blank
    // Record Sequence Number
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

    // A Record



    // Closing line
    return $this->textResponse($file);
  }
}