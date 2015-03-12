<?php

namespace Kula\HEd\Bundle\BillingBundle\Field;

use Kula\Core\Component\Field\Field;

class RefundType extends Field {
  
  public function select($schema, $param) {

    $menu = array(
      'TUITION' => 'Tuition',
      'COURSEFEE' => 'Course Fees'
    );
    
    return $menu;
    
  }
  
}