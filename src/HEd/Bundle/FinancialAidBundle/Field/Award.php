<?php

namespace Kula\Bundle\HEd\FinancialAidBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class Award implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array();
		
		//$or_condition = new \Kula\Component\Database\Query\Predicate('OR');
		//$or_condition = $or_condition->predicate('marks.INACTIVE_AFTER', null)
		//	->predicate('marks.INACTIVE_AFTER', date('Y-m-d'), '>');
		
		$result = \Kula\Component\Database\DB::connect('read')->select('FAID_AWARD_CODE', 'award')
	->fields('award', array('AWARD_CODE_ID', 'AWARD_CODE', 'AWARD_DESCRIPTION'))
	->predicate('INACTIVE', 'N')
	->order_by('AWARD_CODE', 'ASC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['AWARD_CODE_ID']] = $row['AWARD_CODE'].' - '.$row['AWARD_DESCRIPTION'];
		}
		
		return $menu;
		
	}
	
}