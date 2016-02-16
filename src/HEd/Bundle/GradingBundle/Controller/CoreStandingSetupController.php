<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreStandingSetupController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    
    $standings = array();
    
    // Get Mark Scales
    $standings = $this->db()->db_select('STUD_STANDING')
      ->fields('STUD_STANDING', array('STANDING_ID', 'STANDING_CODE', 'STANDING_DESCRIPTION', 'CONV_STANDING_NUMBER'))
      ->orderBy('STANDING_CODE', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:CoreStandingSetup:standings.html.twig', array('standings' => $standings));
  }
  
}