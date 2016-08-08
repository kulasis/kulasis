<?php

namespace Kula\Core\Bundle\FrameworkBundle\Service;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class Validator {
	
	private $validator;
	private $validator_delegate_name;
	private $validator_delegate;
	
	private $violations;
	
	public function __construct($validator_delegate, $data) {
		
		// Create validator
		$this->validator = Validation::createValidator();
		
		// Set delegate
		$this->validator_delegate_name = $validator_delegate;
		// Instantiate delegate
		if (class_exists($this->validator_delegate_name))
			$this->validator_delegate = new $this->validator_delegate_name();
		else
			throw new \Exception('Validator delegate missing.');
		
		// Construct constraints
		$required_constraints = $this->validator_delegate->setRequiredContraints();
		if (method_exists($this->validator_delegate, 'setOptionalConstraints')) {
			$optional_constraints = $this->validator_delegate->setOptionalConstraints();
		} else {
			$optional_constraints = array();
		}
		
		// Combine constraints
		if ($required_constraints) {
		foreach($required_constraints as $field => $constraint) {
			$combined_constraints[$field][] = new Assert\Required($constraint);
		}
		}
		
		if ($optional_constraints) {
		foreach($optional_constraints as $field => $constraint) {
			$combined_constraints[$field][] = new Assert\Optional($constraint);
		}
		}
		
		$constraints = new Assert\Collection(array(
			'fields' => $combined_constraints,
			'allowExtraFields' => true,
		));
		
		// Cast data as object for expression language use
		$data['object'] = (object)$data;
		
		// Perform validation
		$this->violations = $this->validator->validate($data, $constraints);
	}
	
	public function getViolations() {
		return $this->violations;
	}
	
}