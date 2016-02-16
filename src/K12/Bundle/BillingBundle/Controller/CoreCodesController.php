<?php

namespace Kula\K12\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreCodesController extends Controller {
  
  public function codesAction() {
    $this->authorize();
    $this->processForm();
    
    $codes = $this->db()->db_select('BILL_CODE')
      ->fields('BILL_CODE')
      ->orderBy('CODE')
      ->execute()->fetchAll();
    
    return $this->render('KulaK12BillingBundle:CoreCodes:codes.html.twig', array('codes' => $codes));
  }
  
}