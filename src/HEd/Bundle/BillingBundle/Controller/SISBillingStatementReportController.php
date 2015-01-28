<?php

namespace Kula\Bundle\HEd\StudentBillingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\ReportController;

class BillingStatementReportController extends ReportController {
	
	private $pdf;
	
	private $show_pending_fa;
	private $show_only_with_balances;

	private $student_balances_for_orgterm;
	private $student_balances;
	
	public function indexAction() {
		$this->authorize();
		//$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
		if ($this->request->query->get('record_type') == 'STUDENT' AND $this->request->query->get('record_id') != '')
			$this->setRecordType('STUDENT');
		return $this->render('KulaHEdStudentBillingBundle:BillingStatementReport:reports_billingstatement.html.twig');
	}
	
	public function generateAction()
	{	
		$this->authorize();
		
		$this->pdf = new \Kula\Bundle\HEd\StudentBillingBundle\Controller\BillingStatementReport("P");
		$this->pdf->SetFillColor(245,245,245);
		$this->pdf->row_count = 0;

		$report_settings = $this->request->request->get('non');
		if (isset($report_settings['DUE_DATE']))
			$this->pdf->due_date = $report_settings['DUE_DATE'];
		// Pending FA Setting
		if (isset($report_settings['SHOW_PENDING_FA']) AND $report_settings['SHOW_PENDING_FA'] == 'Y')
			$this->show_pending_fa = true;
		else
			$this->show_pending_fa = false;
		
		if (isset($report_settings['ONLY_BALANCES']) AND $report_settings['ONLY_BALANCES'] == 'Y')
			$this->show_only_with_balances = $report_settings['ONLY_BALANCES'];
		elseif (isset($report_settings['ONLY_NEGATIVE_BALANCES']) AND $report_settings['ONLY_NEGATIVE_BALANCES'] == 'Y')
		  $this->show_only_with_balances = $report_settings['ONLY_NEGATIVE_BALANCES'];
		else
			$this->show_only_with_balances = false;

		// Add on selected record
		$record_id = $this->request->request->get('record_id');
		$record_type = $this->request->request->get('record_type');
		
		// Get current term start date
		$focus_term_info = $this->db()->select('CORE_TERM', 'term')
			->fields('term', array('START_DATE'))
			->predicate('term.TERM_ID', $this->focus->getTermID())
			->execute()->fetch();
		
		// Get students with balances
		$students_with_balances_result = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
			->fields('transactions', array('CONSTITUENT_ID'))
			->expressions(array('SUM(AMOUNT)' => 'total_amount'))
			->group_by('CONSTITUENT_ID')
			->order_by('CONSTITUENT_ID');
		
		if ($this->focus->getTermID() != '') {
			$org_term_ids = $this->focus->getOrganizationTermIDs();
			if ($this->focus->getTermID() != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
				$students_with_balances_result = $students_with_balances_result->predicate('transactions.ORGANIZATION_TERM_ID', $org_term_ids)
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', null, 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', null, 'term.TERM_ID = orgterms.TERM_ID');
			}
		}
		
		// Add on selected record
		if (isset($record_id) AND $record_id != '' AND $record_type == 'STUDENT')
			$students_with_balances_result = $students_with_balances_result->predicate('transactions.CONSTITUENT_ID', $record_id);
		$students_with_balances_result = $students_with_balances_result->execute();
		while ($balance_row = $students_with_balances_result->fetch()) {
			if ($balance_row['total_amount'] > 0 AND isset($report_settings['ONLY_BALANCES']) AND $report_settings['ONLY_BALANCES'])
				$this->student_balances[$balance_row['CONSTITUENT_ID']] = $balance_row;
			elseif ($balance_row['total_amount'] < 0 AND isset($report_settings['ONLY_NEGATIVE_BALANCES']) AND $report_settings['ONLY_NEGATIVE_BALANCES'])
				$this->student_balances[$balance_row['CONSTITUENT_ID']] = $balance_row;
		}
		
		//kula_print_r($this->student_balances);
		//die();
		
		// Get Balances
		$this->student_balances_for_orgterm = array();
		
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0) {
		
			$or_query_conditions = new \Kula\Component\Database\Query\Predicate('OR');
			$or_query_conditions = $or_query_conditions->predicate('term.TERM_ID', null);
			$or_query_conditions = $or_query_conditions->predicate('term.START_DATE', $focus_term_info['START_DATE'], '<');
		
		$terms_with_balances_result = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
			->fields('transactions', array('CONSTITUENT_ID'))
			->expressions(array('SUM(AMOUNT)' => 'total_amount'))
			->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
			->left_join('CORE_ORGANIZATION', 'org', null, 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
			->left_join('CORE_TERM', 'term', null, 'term.TERM_ID = orgterms.TERM_ID')
			->predicate($or_query_conditions)
			->group_by('CONSTITUENT_ID')
			->order_by('CONSTITUENT_ID');
		//echo $terms_with_balances_result->sql();
		//var_dump($terms_with_balances_result->arguments());
		//die();
		// Add on selected record
		if (isset($record_id) AND $record_id != '' AND $record_type == 'STUDENT')
			$terms_with_balances_result = $terms_with_balances_result->predicate('transactions.CONSTITUENT_ID', $record_id);
		$terms_with_balances_result = $terms_with_balances_result->execute();
		while ($balance_row = $terms_with_balances_result->fetch()) {
			$this->student_balances_for_orgterm[$balance_row['CONSTITUENT_ID']][] = $balance_row;
		}
		} 
		
		// Get Data and Load
		$result = $this->db()->select('STUD_STUDENT', 'student')
			->fields('student', array('STUDENT_ID'))
			->join('CONS_CONSTITUENT', 'stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
			->left_join('CONS_PHONE', 'phone', array('PHONE_NUMBER'), 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
			->left_join('CONS_ADDRESS', 'billaddr', array('ADDRESS' => 'bill_ADDRESS', 'CITY' => 'bill_CITY', 'STATE' => 'bill_STATE', 'ZIPCODE' => 'bill_ZIPCODE', 'COUNTRY' => 'bill_COUNTRY'), 'billaddr.ADDRESS_ID = student.BILLING_ADDRESS_ID')
			->left_join('CONS_ADDRESS', 'mailaddr', array('ADDRESS' => 'mail_ADDRESS', 'CITY' => 'mail_CITY', 'STATE' => 'mail_STATE', 'ZIPCODE' => 'mail_ZIPCODE', 'COUNTRY' => 'mail_COUNTRY'), 'mailaddr.ADDRESS_ID = stucon.MAILING_ADDRESS_ID')
			->left_join('CONS_ADDRESS', 'residenceaddr', array('ADDRESS' => 'residence_ADDRESS', 'CITY' => 'residence_CITY', 'STATE' => 'residence_STATE', 'ZIPCODE' => 'residence_ZIPCODE', 'COUNTRY' => 'residence_COUNTRY'), 'residenceaddr.ADDRESS_ID = stucon.RESIDENCE_ADDRESS_ID');
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if ($this->focus->getTermID() != '' AND isset($org_term_ids) AND count($org_term_ids) > 0) {
			$result = $result->predicate('status.ORGANIZATION_TERM_ID', $org_term_ids)
				->left_join('STUD_STUDENT_STATUS', 'status', array('PAYMENT_PLAN'), 'status.STUDENT_ID = student.STUDENT_ID')
				->left_join('CORE_LOOKUP_VALUES', 'grade_values', array('DESCRIPTION' => 'GRADE'), 'grade_values.CODE = status.GRADE AND grade_values.LOOKUP_ID = 20')
				->left_join('CORE_LOOKUP_VALUES', 'entercode_values', array('DESCRIPTION' => 'ENTER_CODE'), 'entercode_values.CODE = status.ENTER_CODE AND entercode_values.LOOKUP_ID = 16')
				->left_join('STUD_STUDENT_DEGREES', 'studdegrees', null, 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
				->left_join('STUD_DEGREE', 'degree', array('DEGREE_NAME'), 'degree.DEGREE_ID = studdegrees.DEGREE_ID')	
				->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
				->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
				->left_join('CORE_TERM', 'term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'), 'term.TERM_ID = orgterms.TERM_ID');
		}
		
		if ($this->show_only_with_balances == 'Y') {
			$result = $result->predicate('student.STUDENT_ID', array_keys($this->student_balances));
		}
		
		if ($report_settings['FROM_ADD_DATE'] != '') {
			$result = $result->predicate('status.CREATED_TIMESTAMP', date('Y-m-d', strtotime($report_settings['FROM_ADD_DATE'])), '>=');
		}
		
		// Add on selected record
		if (isset($record_id) AND $record_id != '' AND $record_type == 'STUDENT')
			$result = $result->predicate('student.STUDENT_ID', $record_id);

		if (isset($report_settings['student']) AND $report_settings['student'] != '') {
			
			$exploded_stus = explode(",", $report_settings['student']);
			
			if ($exploded_stus) {
				foreach($exploded_stus as $key => $value) {
					$exploded_stus[$key] = trim($value);
				}
			}
			
			$result = $result->predicate('stucon.PERMANENT_NUMBER', $exploded_stus);
		}

		$result = $result
			->order_by('LAST_NAME', 'ASC', 'stucon')
			->order_by('FIRST_NAME', 'ASC', 'stucon')
			->order_by('STUDENT_ID', 'ASC', 'student');
		//echo $result->sql();
		//var_dump($result->arguments());
		//die();
		$result = $result->execute();
		
		while ($row = $result->fetch()) {
			if ($row['bill_ADDRESS']) {
				
				// Get billing addresses
				$billing_addresses_query_conditions = new \Kula\Component\Database\Query\Predicate('OR');
				$billing_addresses_query_conditions = $billing_addresses_query_conditions->predicate('EFFECTIVE_DATE', null);
				$billing_addresses_query_conditions = $billing_addresses_query_conditions->predicate('EFFECTIVE_DATE', date('Y-m-d'), '<=');
				
				$billing_addresses_result = $this->db()->select('CONS_ADDRESS', 'address')
					->fields('address', array('RECIPIENT', 'ADDRESS', 'CITY', 'STATE', 'ZIPCODE'))
					//->predicate($billing_addresses_query_conditions)
					->predicate('CONSTITUENT_ID', $row['STUDENT_ID'])
					->predicate('ACTIVE', 'Y')
					->predicate('UNDELIVERABLE', 'N')
					->predicate('ADDRESS_TYPE', 'B')
					->execute();
				while ($billing_addresses_row = $billing_addresses_result->fetch()) {
					$row['address'] = 'bill';
					$row['billing_address'] = $billing_addresses_row;
					$this->createStatement($row['STUDENT_ID'], $row);
				}
			}
			if ($row['mail_ADDRESS'] OR $row['residence_ADDRESS']) {
				unset($row['address']);
				$row['address'] = 'mail';
				$this->createStatement($row['STUDENT_ID'], $row);
			}
		}
		
    // Closing line
	  return $this->pdfResponse($this->pdf->Output('','S'));
		
	}
	
	public function createStatement($student_id, $data) {
		
		if (isset($this->student_balances_for_orgterm[$student_id]))
			$previous_balance = $this->student_balances_for_orgterm[$student_id];
		else 
			$previous_balance = 0;
		
		$transactions = $this->getTransactionsForStudent($student_id);
		
		$do_statement = true;
		//if ($this->show_only_with_balances AND (count($transactions) > 0))
		//	$do_statement = true;

		
		if ($do_statement) {
		
		$this->pdf->balance = 0;
		$this->pdf->setData($data);
		$this->pdf->row_count = 1;
		$this->pdf->row_page_count = 1;
		$this->pdf->row_total_count = 1;
		$this->pdf->StartPageGroup();
		$this->pdf->AddPage();
		
		if (isset($this->student_balances_for_orgterm[$student_id]))
			$this->pdf->previous_balances($this->student_balances_for_orgterm[$student_id]);
		
		
		$last_term_id = 0;
		foreach($transactions as $row) {
			$this->pdf->table_row($row);
			$last_term_id = $row['TERM_ID'];
		}
		if ($this->show_pending_fa) $this->getPendingFinancialAid($student_id, $last_term_id);
		$this->pdf->total_balance();
		$this->getHolds($student_id);
		$this->pdf->remit_payment();
		
		}
	}
	
	public function getTransactionsForStudent($student_id) {
		$result = $this->db()->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
			->fields('transactions', array('TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED'))
			->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
			->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->left_join('CORE_TERM', 'term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'), 'term.TERM_ID = orgterms.TERM_ID')
			->predicate('transactions.CONSTITUENT_ID', $student_id)
			->predicate('transactions.SHOW_ON_STATEMENT', 'Y');
		
		$org_term_ids = $this->focus->getOrganizationTermIDs();
		if (isset($org_term_ids) AND count($org_term_ids) > 0)
			$result = $result->predicate('transactions.ORGANIZATION_TERM_ID', $org_term_ids);
		$result = $result
			->order_by('START_DATE', 'ASC', 'term')
			->order_by('TRANSACTION_DATE', 'ASC', 'transactions')
		  ->execute();
		
		return $result->fetchAll();
	}
	
	public function getPendingFinancialAid($student_id, $term_id) {
		$awards_result = $this->db()->select('FAID_STUDENT_AWARDS', 'faidstuawrds')
			->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
			->join('FAID_AWARD_CODE', 'awardcode', array('AWARD_DESCRIPTION'), 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
			->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE'), 'faidstuawrds.AWARD_YEAR_TERM_ID = faidstuawrdyrtrm.AWARD_YEAR_TERM_ID')
			->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', array('AWARD_YEAR'), 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
			->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ID', 'TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
			->predicate('faidstuawardyr.STUDENT_ID', $student_id)
			->predicate('term.TERM_ID', $term_id)
			->predicate('faidstuawrds.AWARD_STATUS', 'PEND')
			->predicate('faidstuawrds.SHOW_ON_STATEMENT', 'Y')
			->predicate('faidstuawrds.NET_AMOUNT', 0, '>')
			->execute();
		while ($awards_row = $awards_result->fetch()) {
			$this->pdf->fa_table_row($awards_row);
		}
		
	}
	
	public function getHolds($student_id) {
		$holds_result = $this->db()->select('STUD_STUDENT_HOLDS', 'stuholds')
				->fields('stuholds', array('STUDENT_HOLD_ID', 'HOLD_ID', 'HOLD_DATE', 'COMMENTS', 'VOIDED', 'VOIDED_REASON', 'VOIDED_TIMESTAMP'))
				->join('STUD_HOLD', 'hold', array('HOLD_NAME'), 'stuholds.HOLD_ID = hold.HOLD_ID')
				->left_join('CORE_USER', 'user', array('USERNAME'), 'user.USER_ID = stuholds.VOIDED_USERSTAMP')
				->predicate('stuholds.STUDENT_ID', $student_id)
				->predicate('stuholds.VOIDED', 'N')
				->order_by('HOLD_DATE', 'ASC', 'stuholds')
			  ->execute();
		$first = 0;
		while ($holds_row = $holds_result->fetch()) {
			if ($first == 0) $this->pdf->holds_header();
			$this->pdf->hold_row($holds_row);
			$first++;
		}
	}
}