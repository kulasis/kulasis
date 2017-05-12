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
  }

  public function logAPICall($request, $response, $error = false) {

    if ($request->headers->get('Authorization')) {

      // All request parameters
      $request_log = array();
      $request_log['SERVER'] = $request->server->all();
      $request_log['POST'] = $this->cleanRequestData($request->request->all());
      $request_log['GET'] = $this->cleanRequestData($request->query->all());

      if ($error) {
        $error_response = array();
        $error_response['statusCode'] = $response->getStatusCode();
        $error_response['content'] = $response->getContent();
        $response = null;
      } else {
        $response_log = array();
        $response_log['statusCode'] = $response->getStatusCode();
        $response_log['content'] = $response->getContent();
        $error_response = null;
      }

      if (isset($_SESSION['user_session_id']))
        $session_id = $_SESSION['user_session_id'];
      elseif (isset($_SESSION['application_session_id']))
        $session_id = $_SESSION['application_session_id'];
      else
        $session_id = null;

      // Log request and response
      $this->db->db_insert('LOG_API', array('target' => 'additional'))->fields(array(
        'LOG_SESSION_ID' => $session_id, 
        'TIMESTAMP' => date('Y-m-d H:i:s'), 
        'REQUEST' => print_r($request_log, true), 
        'RESPONSE' => print_r($response_log, true),
        'ERROR' => print_r($error_response, true)
      ))
      ->execute();

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
  
}