<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISCodesController extends Controller {
  
  public function codesAction() {
    $this->authorize();
    $this->processForm();
    
    $codes = $this->db()->db_select('BILL_CODE')
      ->fields('BILL_CODE')
      ->orderBy('CODE')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdBillingBundle:SISCodes:codes.html.twig', array('codes' => $codes));
  }
  
}