<?php

namespace Kula\Bundle\HEd\StudentBillingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class ConstituentTransaction implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array();
		
		$result = \Kula\Component\Database\DB::connect('read')->select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
			->fields('transactions', array('CONSTITUENT_TRANSACTION_ID', 'TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'APPLIED_BALANCE'))
			->join('BILL_CODE', 'codes', array('CODE', 'CODE_TYPE'), 'transactions.CODE_ID = codes.CODE_ID')
			->left_join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'transactions.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
			->left_join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_ABBREVIATION'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'orgterms.TERM_ID = term.TERM_ID')
			->predicate('transactions.CONSTITUENT_ID', $param['CONSTITUENT_ID'])
			->order_by('START_DATE', 'DESC', 'term')
			->order_by('TRANSACTION_DATE', 'DESC')
			->order_by('CODE', 'ASC')
			->execute();
		while ($row = $result->fetch()) {
			$menu[$row['CONSTITUENT_TRANSACTION_ID']] = $row['ORGANIZATION_ABBREVIATION'].' '.$row['TERM_ABBREVIATION'].' '.date('m/d/Y', strtotime($row['TRANSACTION_DATE'])).' '.$row['CODE_TYPE'].' '.$row['CODE'].' '.$row['TRANSACTION_DESCRIPTION'].' '.$row['AMOUNT'].' '.$row['APPLIED_BALANCE'];
		}
		
		return $menu;
		
	}
	
}