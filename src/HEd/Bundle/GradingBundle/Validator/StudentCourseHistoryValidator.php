<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Validator;

use Symfony\Component\Validator\Constraints as Assert;

class StudentCourseHistoryValidator extends \Kula\Component\Validator\BaseValidator {
	
	public function setRequiredContraints() {
		
		//$constraints['LOOKUP_ID'] = array(new Assert\NotNull());
		//$constraints['CODE'] = array(new Assert\NotNull());
	//	$constraints['DESCRIPTION'] = array(new Assert\NotNull());
		
		//return $constraints;
	}
	
	public function setOptionalConstraints() {
		$constraints['CALENDAR_MONTH'] = array(new Assert\GreaterThanOrEqual(1), new Assert\LessThanOrEqual(12));
		/*
		$constraints['object'] = new Assert\Expression(array(
            'expression' => 'this in ["ORGANIZATION_ID", "NON_ORGANIZATION_ID"] and this.ORGANIZATION_ID == "" and this.NON_ORGANIZATION_ID == ""',
            'message' => 'Both Organization and Non-Organization cannot be set.'));
		*/
		return $constraints;
	}
	
}