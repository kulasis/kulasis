<?php

namespace Kula\Core\Bundle\SystemBundle\Service;

class JobQueueService {
  
  protected $db;
  protected $session;
  protected $focus;
  protected $navigation;
  
  public function __construct($db, $session, $focus, $navigation) {
    
    $this->db = $db;
    $this->session = $session;
    $this->focus = $focus;
    $this->navigation = $navigation;
    
  }
  
  public function queueJob($navigation_id, $parameters) {
    
    // xmlize the $parameters
    $xml = new SimpleXMLElement('<root/>');
    array_walk_recursive($parameters, array ($xml, 'addChild'));
    
    $navigation_info = $this->db->db_select('CORE_NAVIGATION')
      ->fields(array('NAVIGATION_ID', 'NAVIGATION_NAME'))
      ->condition('NAVIGATION_ID', $navigation_id)
      ->execute()->fetch();
    
    $job['ROLE_ID'] = $this->session->get('role_id');
    $job['ORGANIZATION_ID'] = $this->session->get('organization_id');
    $job['TERM_ID'] = $this->session->get('term_id');
    $job['NAVIGATION_ID'] = $navigation_id;
    $job['JOB_NAME'] = $navigation_info['NAVIGATION_NAME'];
    $job['JOB_DEFINITION'] = $xml->asXML();
    $job['ADDED_TIME'] = date('Y-m-d h:i:s');
    $job['JOB_STATUS'] = 'W';
    
    $job_id = $this->db->db_insert('CORE_JOB')->fields($job)->execute();
    
    return $job_id;
  }
  
}