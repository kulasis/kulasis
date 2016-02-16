<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreCodesController extends Controller {
  
  public function award_codesAction() {
    $this->authorize();
    $this->processForm();
    
    $award_codes = $this->db()->db_select('FAID_AWARD_CODE')
      ->fields('FAID_AWARD_CODE')
      ->orderBy('AWARD_CODE')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdFinancialAidBundle:CoreCodes:award_codes.html.twig', array('award_codes' => $award_codes));
  }
  
}