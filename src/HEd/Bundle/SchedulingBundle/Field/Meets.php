<?php

namespace Kula\Bundle\HEd\SchedulingBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class Meets implements CalculatedFieldInterface {
	
	public static function calculate($data) {
		
		$meetings = array();
		
		$meetings_result = \Kula\Component\Database\DB::connect('read')->select('SCHD_SECTION_MEETINGS', 'meetings')
			->fields('meetings', array('MEETING_DAY', 'START_TIME', 'END_TIME'))
			->join('CORE_LOOKUP_VALUES', 'values', null, 'values.CODE = meetings.MEETING_DAY AND values.LOOKUP_ID = 29')
			->predicate('meetings.SECTION_ID', $data)
			->order_by('SECTION_ID', 'ASC')
			->order_by('SORT', 'ASC');
		$meetings_result = $meetings_result->execute();
		$i = 0;
		while ($meetings_row = $meetings_result->fetch()) {
			
			$meetings[$i] = $meetings_row['MEETING_DAY'] . ' ( ' . $meetings_row['START_TIME'] ' - ' . $meetings_row['END_TIME'] . ' )';
			
		$i++;
		}
		
		return implode(', ', $meetings);
		
	}
	
}