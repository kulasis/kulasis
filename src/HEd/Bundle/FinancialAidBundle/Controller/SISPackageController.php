<?php

namespace Kula\Bundle\HEd\FinancialAidBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class PackageController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->setRecordType('STUDENT');
		
		$fin_aid_year = $this->db()->select('CORE_TERM', 'term')
			->fields('term', array('FINANCIAL_AID_YEAR'))
			->predicate('TERM_ID', $this->focus->getTermID())
			->execute()->fetch();	
		
		// Must remove template records first before determining if add should occur or else edit will never happen
		$post_info_add = $this->request->request->get('add');

		if ($post_info_add) {
		
			unset($post_info_add['FAID_STUDENT_AWARD_YEAR_AWARDS']['new_num']);

			if (count($post_info_add['FAID_STUDENT_AWARD_YEAR_AWARDS']) == 0 AND count($post_info_add['FAID_STUDENT_AWARDS']) == 0) {
				unset($post_info_add);
			}
		
		}
		
		
		
		if (isset($post_info_add)) {
			unset($post_info_add['FAID_STUDENT_AWARD_YEAR_AWARDS']['new_num']);

			$this->db('write')->beginTransaction();
			
			foreach($post_info_add as $table => $row_info) {
				foreach($row_info as $row_id => $row) {
					
					// if inserting into FAID_STUDENT_AWARD_YEAR_AWARDS
					if ($table == 'FAID_STUDENT_AWARD_YEAR_AWARDS') {
					
						// check if award code already exists
						$award_code_exists = $this->db()->select('FAID_STUDENT_AWARD_YEAR', 'FAID_STUDENT_AWARD_YEAR')
							->fields('FAID_STUDENT_AWARD_YEAR', array('AWARD_YEAR_ID'))
							->left_join('FAID_STUDENT_AWARD_YEAR_AWARDS', 'FAID_STUDENT_AWARD_YEAR_AWARDS', array('AWARD_YEAR_AWARD_ID'), 
								'FAID_STUDENT_AWARD_YEAR_AWARDS.AWARD_YEAR_ID = FAID_STUDENT_AWARD_YEAR.AWARD_YEAR_ID AND
								AWARD_CODE_ID = '.$row['AWARD_CODE_ID'])
							->predicate('STUDENT_ID', $this->record->getSelectedRecordID())
							->predicate('AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
							->execute()->fetch();
						if (!$award_code_exists['AWARD_YEAR_AWARD_ID']) {
							
							$poster_factory = new \Kula\Component\Database\Poster(array('FAID_STUDENT_AWARD_YEAR_AWARDS' => array('new' => array(
								'AWARD_YEAR_ID' => $award_code_exists['AWARD_YEAR_ID'],
								'AWARD_CODE_ID' => $row['AWARD_CODE_ID'],
								'GROSS_AMOUNT' => $row['GROSS_AMOUNT'],
								))));
								
								// Create student award records
								$student_award_terms_result = $this->db()->select('FAID_STUDENT_AWARD_YEAR_TERMS')
									->fields(array('AWARD_YEAR_TERM_ID', 'PERCENTAGE'))
									->predicate('AWARD_YEAR_ID', $award_code_exists['AWARD_YEAR_ID'])
									->execute();
								while ($student_award_term = $student_award_terms_result->fetch()) {
									
									if ($student_award_term['PERCENTAGE'] > 0)
										$percentage = $student_award_term['PERCENTAGE'] * .01;
									else
										$percentage = 0;
									
									$award_code_result = $this->db()->select('FAID_AWARD_CODE')
										->fields(array('SHOW_ON_STATEMENT', 'ORIGINATION_FEE_PERCENTAGE'))
										->predicate('AWARD_CODE_ID', $row['AWARD_CODE_ID'])
										->execute()->fetch();
									
									if ($award_code_result['ORIGINATION_FEE_PERCENTAGE'] > 0) {
										$net_amount = ceil($row['GROSS_AMOUNT'] * $percentage * ((100.0 - $award_code_result['ORIGINATION_FEE_PERCENTAGE']) * .01)); // (100 - fee) * .01
									} else {
										$net_amount = $row['GROSS_AMOUNT'] * $percentage;
									}
									
									$poster_factory = new \Kula\Component\Database\Poster(array('FAID_STUDENT_AWARDS' => array('new' => array(
										'AWARD_YEAR_TERM_ID' => $student_award_term['AWARD_YEAR_TERM_ID'],
										'AWARD_CODE_ID' => $row['AWARD_CODE_ID'],
										'GROSS_AMOUNT' => $row['GROSS_AMOUNT'] * $percentage,
										'NET_AMOUNT' => $net_amount,
										'AWARD_STATUS' => 'PEND',
										'SHOW_ON_STATEMENT' => array('checkbox_hidden' => '', 'checkbox' => $award_code_result['SHOW_ON_STATEMENT'])
										))));
								}
								
						}
						
					} // end if for FAID_STUDENT_AWARD_YEAR_AWARDS records
					
					if ($table == 'FAID_STUDENT_AWARDS') {

						foreach ($row as $award_row_id => $award) {

						// Split $row_id; [0] = Award Code, [1] = Organization Term ID
						$row_id_split = explode('/', $award_row_id);
						
						$award_code_result = $this->db()->select('FAID_AWARD_CODE')
							->fields(array('SHOW_ON_STATEMENT', 'ORIGINATION_FEE_PERCENTAGE'))
							->predicate('AWARD_CODE_ID', $row_id_split[0])
							->execute()->fetch();
						
						if ($award_code_result['ORIGINATION_FEE_PERCENTAGE'] > 0) {
							$net_amount = ceil($award['GROSS_AMOUNT'] * ((100.0 - $award_code_result['ORIGINATION_FEE_PERCENTAGE']) * .01)); // (100 - fee) * .01
						} else {
							$net_amount = $award['GROSS_AMOUNT'];
						}
						
						$data = array('FAID_STUDENT_AWARDS' => array('new' => array(
							'AWARD_YEAR_TERM_ID' => $row_id_split[1],
							'AWARD_CODE_ID' => $row_id_split[0],
							'GROSS_AMOUNT' => $award['GROSS_AMOUNT'],
							'NET_AMOUNT' => $net_amount,
							'AWARD_STATUS' => 'PEND',
							'SHOW_ON_STATEMENT' => array('checkbox_hidden' => 'N', 'checkbox' => $award_code_result['SHOW_ON_STATEMENT'])
							)));
						
						$poster_factory = new \Kula\Component\Database\Poster($data);
							
						} // end for
						
					} // end if for FAID_STUDENT_AWARDS
					
				}
			}
			
			$this->db('write')->commit();
			
		} elseif ($this->request->request->get('delete')) {	
			
			$post_delete = $this->request->request->get('delete');
			
			$this->db('write')->beginTransaction();
			
			foreach($post_delete as $table => $row_info) {
				foreach($row_info as $row_id => $row) {
					if ($row['delete_row'] == 'Y') {
						// get AWARD_YEAR_ID
						$award_term_ids = array();
						$award_code_id = '';
						$award_year_id_result = $this->db()->select('FAID_STUDENT_AWARD_YEAR_AWARDS', 'faidstuawardyrawards')
							->fields('faidstuawardyrawards', array('AWARD_YEAR_ID', 'AWARD_CODE_ID'))
							->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawardyrterms', array('AWARD_YEAR_TERM_ID'), 'faidstuawardyrawards.AWARD_YEAR_ID = faidstuawardyrterms.AWARD_YEAR_ID')
							->predicate('AWARD_YEAR_AWARD_ID', $row_id);
						$award_year_id_result = 	$award_year_id_result->execute();
						while ($award_year_id_row = $award_year_id_result->fetch()) {
							$award_term_ids[] = $award_year_id_row['AWARD_YEAR_TERM_ID'];
							$award_code_id = $award_year_id_row['AWARD_CODE_ID'];
						}
			
						// delete from FAID_STUDENT_AWARDS
						$this->db('write')->delete('FAID_STUDENT_AWARDS')
							->predicate('AWARD_YEAR_TERM_ID', $award_term_ids)
							->predicate('AWARD_CODE_ID', $award_code_id)
							->execute();
						
					}
				}
			}
			$poster_factory = new \Kula\Component\Database\Poster(null, null, $post_delete);
			
			$this->db('write')->commit();
			
		} else {
			$this->processForm();
		}
		
		$award_year = array();
		$award_terms = array();
		$awards = array();
		$awards_terms_totals = array();
		
		if ($this->record->getSelectedRecordID()) {
		
			$fin_aid_year = $this->db()->select('CORE_TERM', 'term')
				->fields('term', array('FINANCIAL_AID_YEAR'))
				->predicate('TERM_ID', $this->focus->getTermID())
				->execute()->fetch();
			
			$award_year = $this->db()->select('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr')
				->fields('faidstuawardyr', array('AWARD_YEAR_ID', 'AWARD_YEAR', 'PRIMARY_EFC', 'SECONDARY_EFC', 'TOTAL_INCOME', 'TOTAL_COST_OF_ATTENDANCE'))
				->predicate('faidstuawardyr.STUDENT_ID', $this->record->getSelectedRecordID())
				->predicate('faidstuawardyr.AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
				->execute()->fetch();
		
			$award_terms = $this->db()->select('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm')
				->fields('faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE', 'ORGANIZATION_TERM_ID', 'SEQUENCE'))
				->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', array('AWARD_YEAR'), 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
				->join('CORE_ORGANIZATION_TERMS', 'orgterm', null, 'orgterm.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
				->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')	
				->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterm.TERM_ID')
				->predicate('faidstuawardyr.STUDENT_ID', $this->record->getSelectedRecordID())
				->predicate('faidstuawardyr.AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
				->order_by('START_DATE', 'ASC', 'term');
			$award_terms = $award_terms->execute()->fetchAll();
			
			$awards_result = $this->db()->select('FAID_STUDENT_AWARDS', 'stuawards')
				->fields('stuawards', array('AWARD_ID', 'AWARD_CODE_ID', 'GROSS_AMOUNT'))
				->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'ORGANIZATION_TERM_ID'), 'faidstuawrdyrtrm.AWARD_YEAR_TERM_ID = stuawards.AWARD_YEAR_TERM_ID')
				->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', array('AWARD_YEAR_ID', 'AWARD_YEAR'), 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
				->join('FAID_AWARD_CODE', 'faidawardcode', array('AWARD_CODE', 'AWARD_DESCRIPTION'), 'faidawardcode.AWARD_CODE_ID = stuawards.AWARD_CODE_ID')
				->left_join('FAID_STUDENT_AWARD_YEAR_AWARDS', 'faidstuawardyraward', array('AWARD_YEAR_AWARD_ID', 'GROSS_AMOUNT' => 'yr_GROSS_AMOUNT'), 'faidstuawardyraward.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID AND faidstuawardyraward.AWARD_CODE_ID = stuawards.AWARD_CODE_ID')
				->predicate('faidstuawardyr.STUDENT_ID', $this->record->getSelectedRecordID())
				->predicate('faidstuawardyr.AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
				->order_by('AWARD_CODE', 'ASC', 'faidawardcode')
				->execute();
			while ($awards_row = $awards_result->fetch()) {
				$awards[$awards_row['AWARD_CODE_ID']]['AWARD_DESCRIPTION'] = $awards_row['AWARD_DESCRIPTION'];
				$awards[$awards_row['AWARD_CODE_ID']]['AWARD_YEAR_AWARD_ID'] = $awards_row['AWARD_YEAR_AWARD_ID'];
				$awards[$awards_row['AWARD_CODE_ID']]['GROSS_AMOUNT'] = $awards_row['yr_GROSS_AMOUNT'];
				$awards[$awards_row['AWARD_CODE_ID']]['terms'][$awards_row['AWARD_YEAR_TERM_ID']]['AWARD_ID'] = $awards_row['AWARD_ID'];
				$awards[$awards_row['AWARD_CODE_ID']]['terms'][$awards_row['AWARD_YEAR_TERM_ID']]['GROSS_AMOUNT'] = $awards_row['GROSS_AMOUNT'];
				if (!isset($awards[$awards_row['AWARD_CODE_ID']]['TOTAL'])) $awards[$awards_row['AWARD_CODE_ID']]['TOTAL'] = 0;
				$awards[$awards_row['AWARD_CODE_ID']]['TOTAL'] += $awards_row['GROSS_AMOUNT'];
				if (!isset($awards_terms_totals['total']))
					$awards_terms_totals['total'] = 0;
				$awards_terms_totals['total'] += $awards_row['GROSS_AMOUNT'];
				if (!isset($awards_terms_totals[$awards_row['AWARD_YEAR_TERM_ID']]))
					$awards_terms_totals[$awards_row['AWARD_YEAR_TERM_ID']] = 0;
				$awards_terms_totals[$awards_row['AWARD_YEAR_TERM_ID']] += $awards_row['GROSS_AMOUNT'];
			}

		}
		return $this->render('KulaHEdFinancialAidBundle:Package:package_index.html.twig', array('award_year' => $award_year, 'award_terms' => $award_terms, 'awards' => $awards, 'awards_terms_totals' => $awards_terms_totals));
	}
}