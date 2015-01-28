<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class StandingSetupController extends Controller {
	
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