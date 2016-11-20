<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class APIv1PaymentController extends APIController {

  public function processPaymentAction() {

    // get logged in user
    $currentUser = $this->authorizeUser();

    $data = array();
    $i = 0;
    $total_amount = 0;

    // return class list
    $class_list_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array('STUDENT_CLASS_ID'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = classes.STUDENT_STATUS_ID')
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = classes.SECTION_ID')
      ->fields('sec', array('SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = sec.COURSE_ID')
      ->fields('course', array('COURSE_TITLE"'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('stustatus.STUDENT_ID', $currentUser)
      ->condition('classes.DROPPED', 0)
      ->condition('classes.START_DATE', date('Y-m-d'), '>=')
      ->execute();
    while ($class_list_row = $class_list_result->fetch()) {

      $data[$i] = $class_list_row;

      // Get charges and payments for class not posted
      $trans_result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'trans')
        ->fields('trans', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DESCRIPTION', 'AMOUNT'))
        ->condition('trans.POSTED', 0)
        ->condition('trans.CONSTITUENT_ID', $currentUser)
        ->condition('trans.STUDENT_CLASS_ID', $class_list_row['STUDENT_CLASS_ID'])
        ->execute();
      while ($trans_row = $trans_result->fetch()) {

        $data[$i]['billing'][] = $trans_row;
        $total_amount += $trans_row['AMOUNT'];

      } // end while on loop through transactions

      $i++;
    } // end while on loop through classes

    $merchant_params = array(
      'ssl_transaction_type' => 'ccsale',
      'ssl_merchant_id' => $this->getParameter('virtualmerchant_merchant_id'),
      'ssl_user_id' => $this->getParameter('virtualmerchant_user_id'),
      'ssl_pin' => $this->getParameter('virtualmerchant_pin'),
      'ssl_amount' => $total_amount,
      'ssl_show_form' => 'false',
      'ssl_card_present' => 'N',
      'ssl_email' => 'mjacobse@ocac.edu',
      'ssl_first_name' => 'Makoa',
      'ssl_last_name' => 'Jacobsen',
      'ssl_card_number' => $this->request->request->get('cc_number'),
      'ssl_exp_date' => $this->request->request->get('cc_exp_date'),
      'ssl_avs_zip' => $this->request->request->get('cc_zip_code'),
      'ssl_avs_address' => $this->request->request->get('cc_address'),
      'ssl_cvv2cvc2' => $this->request->request->get('cc_cvv'),
      'ssl_result_format' => 'ASCII'
    );

    // Send to payment processor
    
    $virtual_merchant = new Client();
    $result = $virtual_merchant->post($this->getParameter('virtualmerchant_url'), array(
      'form_params' => $merchant_params))->getBody();

    $data = array();

    $exploded_per_line = explode("\n", (string) $result);
    foreach($exploded_per_line as $value) {
      $exploded_line = explode("=", $value);
      $data[$exploded_line[0]] = $exploded_line[1];
    }


    // Post payment to Kula
    //$this->get('kula.HEd.billing.constituent')->addPayment($currentUser, 'P', 'CREDIT', date('Y-m-d H:i:s', $result, $total_amount);

    // return class list
    return $this->jsonResponse($data);
  }

}