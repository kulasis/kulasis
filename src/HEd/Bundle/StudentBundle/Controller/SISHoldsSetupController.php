<?php

namespace Kula\Bundle\HEd\StudentBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class HoldsSetupController extends Controller {
	
	public function hold_codesAction() {
		$this->authorize();
		$this->processForm();
		
		$hold_codes = $this->db()->select('STUD_HOLD')
			->order_by('HOLD_CODE')
			->execute()->fetchAll();
		
		return $this->render('KulaHEdStudentBundle:HoldsSetup:hold_codes.html.twig', array('hold_codes' => $hold_codes));
	}
	
}