<?php

namespace Kula\Bundle\HEd\FinancialAidBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class CodesController extends Controller {
	
	public function award_codesAction() {
		$this->authorize();
		$this->processForm();
		
		$award_codes = $this->db()->select('FAID_AWARD_CODE')
			->order_by('AWARD_CODE')
			->execute()->fetchAll();
		
		return $this->render('KulaHEdFinancialAidBundle:Codes:award_codes.html.twig', array('award_codes' => $award_codes));
	}
	
}