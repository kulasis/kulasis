<?php

namespace Kula\Core\Bundle\BillingBundle\Service\Merchant;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class VirtualMerchantService {
  
  protected $database;
  
  protected $poster_factory;

  protected $raw_result;
  
  public function __construct($url,
                              $merchant_id,
                              $user_id,
                              $pin) {
    $this->url = $url;
    $this->merchant_id = $merchant_id;
    $this->user_id = $user_id;
    $this->pin = $pin;

    $this->raw_result = array();
    $this->error = false;
  }

  public function process($amount, $first_name, $last_name, $email, $card_number, $exp_date, $cvv, $avs_address, $avs_city, $avs_state, $avs_zip, $invoice) {

    $merchant_params = array(
      'ssl_transaction_type' => 'ccsale',
      'ssl_merchant_id' => $this->merchant_id,
      'ssl_user_id' => $this->user_id,
      'ssl_pin' => $this->pin,
      'ssl_show_form' => 'false',
      'ssl_result_format' => 'ASCII',
      'ssl_card_present' => 'N',
      'ssl_invoice_number' => $invoice,
      'ssl_customer_code' => $invoice,
      'ssl_email' => $email,
      'ssl_first_name' => $first_name,
      'ssl_last_name' => $last_name,
      'ssl_card_number' => $card_number,
      'ssl_exp_date' => $exp_date,
      'ssl_avs_city' => $avs_city,
      'ssl_avs_state' => $avs_state,
      'ssl_avs_zip' => $avs_zip,
      'ssl_avs_address' => $avs_address,
      'ssl_cvv2cvc2' => $cvv,
      'ssl_amount' => $amount
    );

    // Send to payment processor
    $virtual_merchant = new Client();
    $result = $virtual_merchant->post($this->url, array('form_params' => $merchant_params))->getBody();

    $this->processASCIIResult($result);

    return $this->getResult();
  }

  public function getResult() {
    if (isset($this->raw_result['ssl_result']) AND $this->raw_result['ssl_result'] == '0') {
      return $this->raw_result['ssl_result_message'];
    } else {
      $this->error = true;
      return $this->raw_result['errorMessage'];
    }
  }

  public function getError() {
    return $this->error;
  }

  public function getTransactionID() {
    if (isset($this->raw_result['ssl_txn_id']))
      return $this->raw_result['ssl_txn_id'];
  }

  public function getRawResult() {
    return $this->raw_result;
  }

  public function getResultAmount() {
    if (isset($this->raw_result['ssl_result']) AND $this->raw_result['ssl_result'] == '0') {
      return $this->raw_result['ssl_amount'];
    } else {
      return 0.00;
    }
  }

  private function processASCIIResult($result) {

    $exploded_per_line = explode("\n", (string) $result);
    foreach($exploded_per_line as $value) {
      $exploded_line = explode("=", $value);
      $this->raw_result[$exploded_line[0]] = $exploded_line[1];
    }

  }
  
}