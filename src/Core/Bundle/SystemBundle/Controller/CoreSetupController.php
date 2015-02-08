<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreSetupController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    
    return $this->render('KulaCoreSystemBundle:Setup:index.html.twig');
  }
  
  public function ldapAction() {
    $this->authorize();
    $this->processForm();
    
    // Get terms
    $servers = $this->db()->db_select('CORE_SYSTEM_LDAP')
      ->fields('CORE_SYSTEM_LDAP')
      ->orderBy('SERVER_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaCoreSystemBundle:Setup:ldap.html.twig', array('servers' => $servers));
  }

}