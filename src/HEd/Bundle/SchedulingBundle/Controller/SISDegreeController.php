<?php

namespace Kula\Bundle\HEd\OfferingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class DegreeController extends Controller {
	
	public function degreesAction() {
		$this->authorize();
		$this->processForm();
		
		$degress = array();
		
			// Get Degrees
			$degrees = $this->db()->select('STUD_DEGREE')
				->fields(null, array('DEGREE_ID', 'DEGREE_NAME', 'EFFECTIVE_TERM_ID', 'MINIMUM_GPA', 'MINIMUM_CREDITS'))
				->order_by('DEGREE_NAME', 'ASC')
				->execute()->fetchAll();
		
		return $this->render('KulaHEdOfferingBundle:Degree:degrees.html.twig', array('degrees' => $degrees));
	}
	
	public function majorsAction() {
		$this->authorize();
		$this->processForm();
		
		$majors = array();
		
			// Get Majors
			$majors = $this->db()->select('STUD_DEGREE_MAJOR')
				->fields(null, array('MAJOR_ID', 'MAJOR_NAME', 'EFFECTIVE_TERM_ID', 'MINIMUM_GPA', 'MINIMUM_CREDITS'))
				->order_by('MAJOR_NAME', 'ASC')
				->execute()->fetchAll();
		
		return $this->render('KulaHEdOfferingBundle:Degree:majors.html.twig', array('majors' => $majors));
	}
	
	public function minorsAction() {
		$this->authorize();
		$this->processForm();
		
		$minors = array();
		
			// Get Minors
			$minors = $this->db()->select('STUD_DEGREE_MINOR')
				->fields(null, array('MINOR_ID', 'MINOR_NAME', 'EFFECTIVE_TERM_ID', 'MINIMUM_GPA', 'MINIMUM_CREDITS'))
				->order_by('MINOR_NAME', 'ASC')
				->execute()->fetchAll();
		
		return $this->render('KulaHEdOfferingBundle:Degree:minors.html.twig', array('minors' => $minors));
	}
	
	public function concentrationsAction() {
		$this->authorize();
		$this->processForm();
		
		$concentrations = array();
		
			// Get Concentrations
			$concentrations = $this->db()->select('STUD_DEGREE_CONCENTRATION')
				->fields(null, array('CONCENTRATION_ID', 'CONCENTRATION_NAME', 'EFFECTIVE_TERM_ID', 'MINIMUM_GPA', 'MINIMUM_CREDITS'))
				->order_by('CONCENTRATION_NAME', 'ASC')
				->execute()->fetchAll();
		
		return $this->render('KulaHEdOfferingBundle:Degree:concentrations.html.twig', array('concentrations' => $concentrations));
	}
	
}