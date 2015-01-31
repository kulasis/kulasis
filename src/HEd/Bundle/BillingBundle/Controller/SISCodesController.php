<?php

namespace Kula\HEd\Bundle\BillingBundle\Controller;

use Kula\Core\Bundle\KulaCoreFrameworkBundle\Controller\Controller;

class SISCodesController extends Controller {
  
  public function codesAction() {
    $this->authorize();
    $this->processForm();
    
    $codes = $this->db()->select('BILL_CODE')
      ->order_by('CODE')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdStudentBillingBundle:Codes:codes.html.twig', array('codes' => $codes));
  }
  
}