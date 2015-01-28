<?php

namespace Kula\Bundle\HEd\OfferingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class BillItemType implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {
		
		$menu = array();
		
		$result = \Kula\Component\Database\DB::connect('read')->select('BILL_ITEM_TYPES', 'bill_item_types')
	->fields('bill_item_types', array('ITEM_TYPE_ID', 'ITEM_CODE', 'ITEM_DESCRIPTION'))
	->order_by('ITEM_DESCRIPTION', 'ASC')->execute();
		while ($row = $result->fetch()) {
			$menu[$row['ITEM_TYPE_ID']] = $row['ITEM_DESCRIPTION'];
		}
		
		return $menu;
		
	}
	
}