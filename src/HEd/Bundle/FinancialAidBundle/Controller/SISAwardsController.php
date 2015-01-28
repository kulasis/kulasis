<?php

namespace Kula\Bundle\HEd\FinancialAidBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class AwardsController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->setRecordType('STUDENT');
		
		$edit = $this->request->request->get('edit');
	
		if (count($edit) > 0) {
			$this->db('write')->beginTransaction();
			$this->processForm();
			if (isset($edit['FAID_STUDENT_AWARDS'])) {
			$awards_to_check = array();
				foreach($edit['FAID_STUDENT_AWARDS'] as $award_id => $award) {
					$awards_to_check[$award_id] = $award['NET_AMOUNT'];
				}
				$constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
			$constituent_billing_service->adjustFinancialAidAward($awards_to_check);
			}
			$this->db('write')->commit();
		}
		
		if ($this->request->request->get('post')) {
			
			$this->db('write')->beginTransaction();
			
			$constituent_billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record, $this->session);
			
			$post = $this->request->request->get('post');
			
			foreach($post as $table => $row_info) {
				foreach($row_info as $row_id => $row) {
					$constituent_billing_service->postFinancialAidAward($row_id);
				}
			}
			
			$this->db('write')->commit();
		}
		
		$awards = array();
		
		$fin_aid_year = $this->db()->select('CORE_TERM', 'term')
			->fields('term', array('FINANCIAL_AID_YEAR'))
			->predicate('TERM_ID', $this->focus->getTermID())
			->execute()->fetch();
		
		if ($this->record->getSelectedRecordID()) {

			$awards = $this->db()->select('FAID_STUDENT_AWARDS', 'faidstuawrds')
				->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'DISBURSEMENT_DATE', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
				->join('FAID_AWARD_CODE', 'awardcode', array('AWARD_CODE', 'AWARD_DESCRIPTION'), 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
				->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE', 'ORGANIZATION_TERM_ID', 'SEQUENCE'), 'faidstuawrds.AWARD_YEAR_TERM_ID = faidstuawrdyrtrm.AWARD_YEAR_TERM_ID')
				->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', array('AWARD_YEAR'), 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
				->join('CORE_ORGANIZATION_TERMS', 'orgterm', null, 'orgterm.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
				->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')	
				->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterm.TERM_ID')
				->predicate('faidstuawardyr.STUDENT_ID', $this->record->getSelectedRecordID())
				->predicate('faidstuawardyr.AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
				->execute()->fetchAll();
		}
		
		return $this->render('KulaHEdFinancialAidBundle:Awards:awards.html.twig', array('fin_aid_year' => $fin_aid_year['FINANCIAL_AID_YEAR'], 'awards' => $awards));
	}
	
	public function awards_historyAction() {
		$this->authorize();
		$this->setRecordType('STUDENT');
		
		$awards = array();
		
		if ($this->record->getSelectedRecordID()) {

			$awards = $this->db()->select('FAID_STUDENT_AWARDS', 'faidstuawrds')
				->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'DISBURSEMENT_DATE', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
				->join('FAID_AWARD_CODE', 'awardcode', array('AWARD_CODE', 'AWARD_DESCRIPTION'), 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
				->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE', 'ORGANIZATION_TERM_ID', 'SEQUENCE'), 'faidstuawrds.AWARD_YEAR_TERM_ID = faidstuawrdyrtrm.AWARD_YEAR_TERM_ID')
				->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', array('AWARD_YEAR'), 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
				->join('FAID_STUDENT_AWARD_YEAR_AWARDS', 'faidstuawrdyrawards', array('GROSS_AMOUNT' => 'yr_GROSS_AMOUNT'), 'faidstuawrdyrawards.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID AND faidstuawrdyrawards.AWARD_CODE_ID = faidstuawrds.AWARD_CODE_ID')
				->join('CORE_ORGANIZATION_TERMS', 'orgterm', null, 'orgterm.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
				->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterm.ORGANIZATION_ID')	
				->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterm.TERM_ID')
				->predicate('faidstuawardyr.STUDENT_ID', $this->record->getSelectedRecordID())
				->order_by('AWARD_YEAR', 'DESC', 'faidstuawardyr')
				->order_by('START_DATE', 'DESC', 'term')
				->order_by('AWARD_CODE', 'ASC', 'awardcode')
				->execute()->fetchAll();
			
		}
		
		return $this->render('KulaHEdFinancialAidBundle:Awards:awards_history.html.twig', array('awards' => $awards));
	}
	
	public function billingAction() {
		$this->authorize();
		$this->setRecordType('STUDENT');
		
		$transactions = array();
		
		if ($this->record->getSelectedRecordID()) {

			$transactions = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
				->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE'))
				->join('BILL_CODE', 'code', array('CODE_TYPE', 'CODE'), 'code.CODE_ID = transactions.CODE_ID')
				->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
				->left_join('CORE_ORGANIZATION', 'organization', array('ORGANIZATION_ABBREVIATION'), 'organization.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
				->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
				->predicate('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
				->predicate('transactions.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
				->predicate('AWARD_ID', null, 'IS NOT NULL')
				->order_by('START_DATE', 'DESC', 'term')
				->order_by('CODE', 'ASC', 'code')
				->order_by('TRANSACTION_DATE', 'DESC', 'transactions')
				->order_by('CONSTITUENT_TRANSACTION_ID', 'DESC', 'transactions')
			  ->execute()->fetchAll();
			
		}
		
		return $this->render('KulaHEdFinancialAidBundle:Awards:billing.html.twig', array('transactions' => $transactions));
	}
	
	public function billing_historyAction() {
		$this->authorize();
		$this->setRecordType('STUDENT');
		
		$transactions = array();
		
		if ($this->record->getSelectedRecordID()) {

			$transactions = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
				->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE'))
				->join('BILL_CODE', 'code', array('CODE_TYPE', 'CODE'), 'code.CODE_ID = transactions.CODE_ID')
				->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
				->left_join('CORE_ORGANIZATION', 'organization', array('ORGANIZATION_ABBREVIATION'), 'organization.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
				->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
				->predicate('transactions.CONSTITUENT_ID', $this->record->getSelectedRecordID())
				->predicate('AWARD_ID', null, 'IS NOT NULL')
				->order_by('START_DATE', 'DESC', 'term')
				->order_by('CODE', 'ASC', 'code')
				->order_by('TRANSACTION_DATE', 'DESC', 'transactions')
				->order_by('CONSTITUENT_TRANSACTION_ID', 'DESC', 'transactions')
			  ->execute()->fetchAll();
			
		}
		
		return $this->render('KulaHEdFinancialAidBundle:Awards:billing.html.twig', array('transactions' => $transactions));
	}
}