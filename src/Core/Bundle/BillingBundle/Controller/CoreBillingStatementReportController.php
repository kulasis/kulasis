<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreBillingStatementReportController extends ReportController {
  
  private $pdf;
  
  public function indexAction() {
    $this->authorize();
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'Core.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Student');
    if ($this->request->query->get('record_type') == 'Core.Constituent' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.Constituent');
    return $this->render('KulaCoreBillingBundle:CoreBillingStatementReport:reports_billingstatement.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();

    $non = $this->request->request->get('non');
    
    $this->pdf = new \Kula\Core\Bundle\BillingBundle\Report\BillingStatementReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;

    // Get statement service
    $statement_service = $this->get('kula.Core.billing.statement');
    $statement_service->setConfiguration($non);

    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    $record_type = $this->request->request->get('record_type');

    $statement_service->generateStatements(array($record_id));
    $statements = $statement_service->getStatements();

    $messageText = '';

    if (count($statements) > 0) {
    foreach($statements as $statement) {

      if (isset($statement['addresses'])) {
        foreach($statement['addresses'] as $address) {
          $address_to_process = null;
          if ($address['bill_ADDRESS'] != '') {
            $address_to_process = array(
              'address' => $address['bill_ADDRESS'],
              'city' => $address['bill_CITY'],
              'state' => $address['bill_STATE'],
              'zipcode' => $address['bill_ZIPCODE'],
              'country' => $address['bill_COUNTRY'],
              'recipient' => $address['bill_recipient']
            );
          }
          if ($address['mail_ADDRESS'] != '') {
            $address_to_process = array(
              'address' => $address['mail_ADDRESS'],
              'city' => $address['mail_CITY'],
              'state' => $address['mail_STATE'],
              'zipcode' => $address['mail_ZIPCODE'],
              'country' => $address['mail_COUNTRY']
            );
          }
          if ($address['residence_ADDRESS'] != '') {
            $address_to_process = array(
              'address' => $address['residence_ADDRESS'],
              'city' => $address['residence_CITY'],
              'state' => $address['residence_STATE'],
              'zipcode' => $address['residence_ZIPCODE'],
              'country' => $address['residence_COUNTRY']
            );
          }
          $this->createStatement($statement, $address_to_process);
        } // end foreach addresses
      } else { // end if addresses
        $this->createStatement($statement, null);
      } // end if addresses
      
    } // end foreach statement
    } // end if on count of statements
    
    // Closing line
    return $this->pdfResponse($this->pdf->Output('','S'));
    
  }

  private function createStatement($statement, $address = null) {

    $this->pdf->address = $address;
    $this->pdf->setData($statement);
    $this->pdf->row_count = 1;
    $this->pdf->row_page_count = 1;
    $this->pdf->row_total_count = 1;
    $this->pdf->StartPageGroup();
    $this->pdf->AddPage();
    
    if (isset($statement['previous_balance']))
      $this->pdf->previous_balances($statement['previous_balance']);
    
    if (isset($statement['transactions'])) {
    foreach($statement['transactions'] as $row) {
      $this->pdf->table_row($row);
    }
    }

    if (isset($statement['pending_fa'])) {
    foreach($statement['pending_fa'] as $fa_row) {
      $this->pdf->fa_table_row($fa_row);
    }
    }
    
    $this->pdf->total_balance();

    if (isset($statement['holds'])) {
      $first = 0;
      foreach($statement['holds'] as $hold) {
        if ($first == 0) $this->pdf->holds_header();
        $this->pdf->hold_row($hold);
      }
    }
    $this->pdf->remit_payment();

  }

}