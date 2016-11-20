<?php

namespace Kula\Core\Bundle\BillingBundle\Service;

class PaymentService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;
  
  protected $session;
  
  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory,
                              $record = null, 
                              $session = null) {
    $this->database = $db;
    $this->record = $record;
    $this->posterFactory = $poster_factory;
    $this->session = $session;
  }

  public function addPayment($constituent_id, $payment_type, $payment_method, $payment_date, $payment_number, $amount) {
    
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

    


  }


  
}