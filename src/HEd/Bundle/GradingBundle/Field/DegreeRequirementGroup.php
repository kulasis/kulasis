<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Field;

use Kula\Component\Database\CalculatedFieldInterface;

class DegreeRequirementGroup implements CalculatedFieldInterface {
	
	public static function select($schema, $param) {

		$menu = array();
		
		$result = \Kula\Component\Database\DB::connect('read')->select('STUD_STUDENT_DEGREES', 'studegrees')
	->fields('studegrees', array('EFFECTIVE_DATE'))
	->join('STUD_DEGREE', 'degree', array('DEGREE_NAME', 'DEGREE_ID'), 'studegrees.DEGREE_ID = degree.DEGREE_ID')
	->join('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentrations', null, 'studegrees.STUDENT_DEGREE_ID = stuconcentrations.STUDENT_DEGREE_ID')
	->join('STUD_DEGREE_REQ_GRP', 'degreereqgrp', array('DEGREE_REQ_GRP_ID', 'GROUP_NAME'), 'degreereqgrp.DEGREE_ID = degree.DEGREE_ID OR degreereqgrp.CONCENTRATION_ID = stuconcentrations.CONCENTRATION_ID')
	->predicate('studegrees.STUDENT_ID', $param['STUDENT_ID'])
	->order_by('EFFECTIVE_DATE', 'DESC')
			->order_by('GROUP_NAME', 'ASC')
			->execute();
		while ($row = $result->fetch()) {
			
			$degree_info = $row['DEGREE_NAME'].' - '.date('m/d/Y', strtotime($row['EFFECTIVE_DATE']));
			$menu[$degree_info][$row['DEGREE_REQ_GRP_ID']] = $row['GROUP_NAME'];
			
			unset($degree_info);
		}
		
		return $menu;
		
	}
	
}