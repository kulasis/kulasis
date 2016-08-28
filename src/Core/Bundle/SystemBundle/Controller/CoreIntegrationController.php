<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreIntegrationController extends Controller {

  public function databasesAction() {
    $this->authorize();
    $this->processForm();
    
    $databases = $this->db()->db_select('CORE_INTG_DATABASE', 'dbs')
      ->fields('dbs')
      ->orderBy('APPLICATION')
      ->execute()->fetchAll();
    
    return $this->render('KulaCoreSystemBundle:Integration:databases.html.twig', array('databases' => $databases));
  }

  public function apiAppsAction() {
  	$this->authorize();
    $this->processForm();
    
    $databases = $this->db()->db_select('CORE_INTG_API_APPS', 'dbs')
      ->fields('dbs')
      ->orderBy('APPLICATION')
      ->execute()->fetchAll();
    
    return $this->render('KulaCoreSystemBundle:Integration:api_apps.html.twig', array('databases' => $databases));
  }
}