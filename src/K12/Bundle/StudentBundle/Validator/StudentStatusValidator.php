<?php

namespace Kula\K12\Bundle\StudentBundle\Validator;

use Kula\Core\Component\Validator\Validator as BaseValidator;

use Symfony\Component\Validator\Constraints as Assert;

class StudentStatusValidator extends BaseValidator {
  
  public function setRequiredContraints() {
    
    $constraints['LEVEL'] = array(new Assert\NotNull());
    $constraints['GRADE'] = array(new Assert\NotNull());
    $constraints['ENTER_DATE'] = array(new Assert\NotNull());
    $constraints['ENTER_CODE'] = array(new Assert\NotNull());
    
    return $constraints;
  }
  
  public function setOptionalConstraints() {
    /*
    $constraints['object'] = new Assert\Expression(array(
            'expression' => 'this in ["ORGANIZATION_ID", "NON_ORGANIZATION_ID"] and this.ORGANIZATION_ID == "" and this.NON_ORGANIZATION_ID == ""',
            'message' => 'Both Organization and Non-Organization cannot be set.'));
    */
    //return $constraints;
  }
  
}