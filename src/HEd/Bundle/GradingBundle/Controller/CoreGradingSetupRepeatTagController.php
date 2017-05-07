<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreGradingSetupRepeatTagController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    
    $repeat_tags = array();
    
    // Get Mark Scales
    $repeat_tags = $this->db()->db_select('STUD_REPEAT_TAG')
      ->fields('STUD_REPEAT_TAG')
      ->orderBy('REPEAT_TAG_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:CoreGradingSetupRepeatTag:index.html.twig', array('repeat_tags' => $repeat_tags));
  }
  
}