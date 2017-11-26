<?php

namespace Kula\Core\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreBillingStatementEmailReportController extends ReportController {

  public function indexAction() {
    $this->authorize();
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'Core.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Student');
    if ($this->request->query->get('record_type') == 'Core.Constituent' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.Constituent');
    return $this->render('KulaCoreBillingBundle:CoreBillingStatementReport:reports_billingstatementemail.html.twig');
  }
  
  public function generateAction() {  
    $this->authorize();

    // Get statement service
    $statement_service = $this->get('kula.Core.billing.statement');
    $statement_service->setConfiguration($this->request->request->get('non'));

    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    $record_type = $this->request->request->get('record_type');

    $statement_service->generateStatements(array($record_id));
    $statements = $statement_service->getStatements();

    $messageText = 'End.';

echo "<pre>";
    print_r($statements);

    if (count($statements) > 0) {
    foreach($statements as $statement) {

      // send email
      $message = \Swift_Message::newInstance()
      ->setSubject('OCAC Billing Statement for '.$statement['student']['FIRST_NAME'].' '.$statement['student']['LAST_NAME'].' ('.$statement['student']['PERMANENT_NUMBER'].')')
      ->setFrom(['kulasis@ocac.edu' => 'Oregon College of Art and Craft'])
      ->setReplyTo('bursar@ocac.edu')
      ->setTo('mjacobsen@ocac.edu')
      //->setBcc(array('mjacobsen@ocac.edu', 'cmalone@ocac.edu', 'jthompson@ocac.edu', 'alex@acreative.io')) // 
      ->setBody(
          $this->renderView(
              'KulaCoreBillingBundle:CoreEmail:statement.html.twig',
              array('data' => $statement)
          ),
          'text/html');
      $messageText .= $message->toString();
      //$this->get('mailer')->send($message);

    } // end foreach on statements
    } // end if on count of statements
  
    return $this->textResponse($messageText);
  }
}