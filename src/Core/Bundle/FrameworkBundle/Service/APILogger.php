<?php

namespace Kula\Core\Bundle\FrameworkBundle\Service;

class APILogger {
  
  private $db;
  private $poster_factory;
  private $session;
  
  private $organization;
  
  private $organization_term_ids;
  
  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              $session) {
      $this->db = $db;
      $this->session = $session;
      $this->error = null;
  }

  public function logAPICall($request, $response) {

    if ($request->headers->get('Authorization')) {

      // All request parameters
      $request_log = array();
      $request_log['POST'] = $this->cleanRequestData($request->request->all());
      $request_log['GET'] = $this->cleanRequestData($request->query->all());
      $request_log['SERVER'] = $request->server->all();
      $request_log = base64_encode(serialize($request_log));


      $response_log = array();
      $response_log['statusCode'] = $response->getStatusCode();
      $response_log['content'] = $response->getContent();
      $response_log = base64_encode(serialize($response_log));

      $log_fields = array(
        'LOG_SESSION_ID' => $this->session->get('session_id'), 
        'TIMESTAMP' => date('Y-m-d H:i:s'), 
        'REQUEST_URI' => $request->server->get('REQUEST_URI'),
        'REQUEST_METHOD' => $request->server->get('REQUEST_METHOD'),
        'RESPONSE_CODE' => $response->getStatusCode(),
        'REQUEST' => $request_log, 
        'RESPONSE' => $response_log
      );
      if ($this->error) {
        $log_fields['ERROR'] = $this->error;
      }

      // Log request and response
      $this->db->db_insert('LOG_API', array('target' => 'additional'))->fields($log_fields)->execute();

    }

  }

  public function cleanRequestData($data) {

    if (isset($data['password']))
      $data['password'] = '<REMOVED PASSWORD>';
    if (isset($data['cc_number']))
      $data['cc_number'] = '<REMOVED CC NUMBER>';
    if (isset($data['cc_cvv']))
      $data['cc_cvv'] = '<REMOVED CC CVV>';

    return $data;

  }

  public function setError($error) {
    $this->error = $error;
  }
  
}