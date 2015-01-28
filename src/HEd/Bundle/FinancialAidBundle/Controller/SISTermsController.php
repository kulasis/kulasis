<?php

namespace Kula\Bundle\HEd\FinancialAidBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class TermsController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->setRecordType('STUDENT');
		
		$award_terms = array();
		
		$fin_aid_year = $this->db()->select('CORE_TERM', 'term')
			->fields('term', array('FINANCIAL_AID_YEAR'))
			->predicate('TERM_ID', $this->focus->getTermID())
			->execute()->fetch();
		
		$post_info_add = $this->request->request->get('add');
		if ($post_info_add) {
		
			unset($post_info_add['FAID_STUDENT_AWARD_YEAR_TERMS']['new_num']);

			if (count($post_info_add['FAID_STUDENT_AWARD_YEAR_TERMS']) == 0) {
				unset($post_info_add);
			}
		
		}
		if (isset($post_info_add)) {
			
			// Check for year
			$award_year_info = $this->db()->select('FAID_STUDENT_AWARD_YEAR', 'awardyr')
				->fields('awardyr', array('AWARD_YEAR_ID'))
				->predicate('AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
				->predicate('STUDENT_ID', $this->record->getSelectedRecordID())
				->execute()->fetch();
			
			if ($award_year_info['AWARD_YEAR_ID'] == '') {
				// Create year record
				$poster_factory = new \Kula\Component\Database\Poster(
					array('FAID_STUDENT_AWARD_YEAR' => 
						array('new' =>
							array('STUDENT_ID' => $this->record->getSelectedRecordID(),
										'AWARD_YEAR' => $fin_aid_year['FINANCIAL_AID_YEAR']))));
				$award_year_id = $poster_factory->getResultForTable('insert', 'FAID_STUDENT_AWARD_YEAR')['new'];
				
			} else {
				$award_year_id = $award_year_info['AWARD_YEAR_ID'];
			}
			
			$post_info_add = $this->request->request->get('add');
			
			foreach($post_info_add as $table => $row_info) {
				foreach($row_info as $row_id => $row) {
					$post_info_add[$table][$row_id]['AWARD_YEAR_ID'] = $award_year_id;
				}
			}
			$poster_factory = new \Kula\Component\Database\Poster($post_info_add);
		} else {
			$this->processForm();
		}
		
		if ($this->record->getSelectedRecordID()) {

			$award_terms = $this->db()->select('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm')
				->fields('faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE', 'ORGANIZATION_TERM_ID', 'SEQUENCE'))
				->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', array('AWARD_YEAR'), 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
				->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
				->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
				->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
				->predicate('faidstuawardyr.STUDENT_ID', $this->record->getSelectedRecordID())
				->predicate('faidstuawardyr.AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
				->order_by('START_DATE', 'ASC', 'term');
			$award_terms = $award_terms->execute()->fetchAll();
			
		}
		
		return $this->render('KulaHEdFinancialAidBundle:Terms:terms.html.twig', array('fin_aid_year' => $fin_aid_year['FINANCIAL_AID_YEAR'], 'award_terms' => $award_terms));
	}
}