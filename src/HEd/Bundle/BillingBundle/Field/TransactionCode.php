<?php

namespace Kula\Bundle\HEd\StudentBillingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class TransactionCode implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array();
		
		//$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
		//$or_condition = $or_condition->predicate('scale.INACTIVE_AFTER', null)
		//	->predicate('scale.INACTIVE_AFTER', date('Y-m-d'), '>');
		
		$result = \Kula\Component\Database\DB::connect('read')->select('BILL_CODE', 'code')
	->fields('code', array('CODE_ID', 'CODE', 'CODE_DESCRIPTION'));
	//->predicate($or_condition)
	if (isset($param['CODE_TYPE']))
		$result = $result->predicate('CODE_TYPE', $param['CODE_TYPE']);
	$result = $result->order_by('CODE', 'ASC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['CODE_ID']] = $row['CODE'] . ' - ' . $row['CODE_DESCRIPTION'];
		}
		
		return $menu;
		
	}
	
}