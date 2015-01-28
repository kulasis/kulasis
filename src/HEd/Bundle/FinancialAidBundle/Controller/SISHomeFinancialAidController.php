<?php

namespace Kula\Bundle\HEd\FinancialAidBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class HomeFinancialAidController extends Controller {
	
	public function pendingAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('FAID_AWARD_TYPE');
		
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
		$gross_total = 0;
		$net_total = 0;
		
		if ($this->record->getSelectedRecordID()) {
		
		$awards_result = $this->db()->select('FAID_STUDENT_AWARDS', 'faidstuawrds')
			->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'DISBURSEMENT_DATE', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
			->join('FAID_AWARD_CODE', 'awardcode', array('AWARD_CODE', 'AWARD_DESCRIPTION'), 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
			->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'awardterms', array('ORGANIZATION_TERM_ID'), 'awardterms.AWARD_YEAR_TERM_ID = faidstuawrds.AWARD_YEAR_TERM_ID')
			->join('FAID_STUDENT_AWARD_YEAR', 'stuawardyr', null, 'stuawardyr.AWARD_YEAR_ID = awardterms.AWARD_YEAR_ID')
			->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'), 'constituent.CONSTITUENT_ID = stuawardyr.STUDENT_ID')
			->left_join('BILL_CONSTITUENT_TRANSACTIONS', 'transactions', array('CONSTITUENT_TRANSACTION_ID'), 'transactions.AWARD_ID = faidstuawrds.AWARD_ID')
			->predicate('awardterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
			->predicate('transactions.CONSTITUENT_TRANSACTION_ID', null)
			->predicate('faidstuawrds.AWARD_STATUS', 'PEND')
			->predicate('faidstuawrds.SHOW_ON_STATEMENT', 'Y')
			->predicate('faidstuawrds.AWARD_CODE_ID', $this->record->getSelectedRecordID())
			->order_by('LAST_NAME', 'ASC', 'constituent')
			->order_by('FIRST_NAME', 'ASC', 'constituent')
			->order_by('PERMANENT_NUMBER', 'ASC', 'constituent')
			->execute();
		  while ($awards_row = $awards_result->fetch()) {
		  	$awards[] = $awards_row;
				if ($awards_row['DISBURSEMENT_DATE'] != '') {
					$gross_total = bcadd($gross_total, $awards_row['GROSS_AMOUNT']);
					$net_total = bcadd($net_total, $awards_row['NET_AMOUNT']);
				}
		  }
		}
		
		return $this->render('KulaHEdFinancialAidBundle:HomeFinancialAid:pending.html.twig', array('awards' => $awards, 'gross_total' => $gross_total, 'net_total' => $net_total));
	}
	
	public function postedAction() {
		$this->authorize();
		$this->setRecordType('FAID_AWARD_TYPE');
		
		$transactions = array();
		
		if ($this->record->getSelectedRecordID()) {
			
			$transactions = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
				->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED', 'VOIDED', 'APPLIED_BALANCE'))
				->join('BILL_CODE', 'code', array('CODE_TYPE', 'CODE'), 'code.CODE_ID = transactions.CODE_ID')
				->join('FAID_AWARD_CODE', 'faid_code', null, 'code.CODE_ID = faid_code.TRANSACTION_CODE_ID')
				->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
				->join('CORE_ORGANIZATION', 'organization', array('ORGANIZATION_ABBREVIATION'), 'organization.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
				->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
				->join('CONS_CONSTITUENT', 'constituent', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'), 'constituent.CONSTITUENT_ID = transactions.CONSTITUENT_ID')
				->predicate('faid_code.AWARD_CODE_ID', $this->record->getSelectedRecordID())
				->predicate('transactions.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
				->predicate('transactions.AWARD_ID', null, 'IS NOT NULL')
				->order_by('TRANSACTION_DATE', 'DESC', 'transactions')
				->order_by('LAST_NAME', 'ASC', 'constituent')
				->order_by('FIRST_NAME', 'ASC', 'constituent')
				->order_by('PERMANENT_NUMBER', 'ASC', 'constituent')
			  ->execute()->fetchAll();

		}
		
		return $this->render('KulaHEdFinancialAidBundle:HomeFinancialAid:posted.html.twig', array('transactions' => $transactions));
	}
	
}