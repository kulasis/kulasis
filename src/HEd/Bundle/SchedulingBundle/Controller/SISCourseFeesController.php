<?php

namespace Kula\Bundle\HEd\OfferingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class CourseFeesController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('COURSE');
		
		$fees = array();
		
		if ($this->record->getSelectedRecordID()) {
			
			// Get Rooms
			$fees = $this->db()->select('BILL_COURSE_FEE', 'BILL_COURSE_FEE')
				->fields('BILL_COURSE_FEE', array('AMOUNT', 'CODE_ID', 'COURSE_FEE_ID'))
				->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = BILL_COURSE_FEE.ORGANIZATION_TERM_ID')
				->join('BILL_CODE', 'code', array('CODE_DESCRIPTION'), 'code.CODE_ID = BILL_COURSE_FEE.CODE_ID')
				->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
				->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
				->predicate('COURSE_ID', $this->record->getSelectedRecordID())
				->predicate('orgterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
				->order_by('START_DATE', 'DESC', 'term')
				->order_by('CODE_DESCRIPTION', 'ASC')
			->execute()->fetchAll();
		}
		
		// Get organization term and name for adding
		$org_term = $this->db()->select('CORE_ORGANIZATION_TERMS', 'orgterms')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'orgterms.TERM_ID = term.TERM_ID')
			->predicate('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs()[0])
			->execute()->fetch();
		
		return $this->render('KulaHEdOfferingBundle:CourseFees:fees_index.html.twig', array('fees' => $fees, 'organization_term_id' => $this->focus->getOrganizationTermIDs()[0], 'organization_term_id_display' => $org_term['TERM_ABBREVIATION'] . ' / ' . $org_term['ORGANIZATION_NAME']));
		
	}

}