<?php

namespace Kula\Bundle\HEd\StudentBillingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class CodesController extends Controller {
	
	public function codesAction() {
		$this->authorize();
		$this->processForm();
		
		$codes = $this->db()->select('BILL_CODE')
			->order_by('CODE')
			->execute()->fetchAll();
		
		return $this->render('KulaHEdStudentBillingBundle:Codes:codes.html.twig', array('codes' => $codes));
	}
	
}