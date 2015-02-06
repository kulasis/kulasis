<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISStandingSetupController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    
    $standings = array();
    
      // Get Mark Scales
      $standings = $this->db()->select('STUD_STANDING')
        ->fields(null, array('STANDING_ID', 'STANDING_CODE', 'STANDING_DESCRIPTION', 'CONV_STANDING'))
        ->order_by('STANDING_CODE', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdCourseHistoryBundle:StandingSetup:standings.html.twig', array('standings' => $standings));
  }
  
}