<?php

namespace Kula\HEd\Bundle\BillingBundle\Field;

use Kula\Core\Component\Field\Field;

class BillingMode extends Field {
  
  public function select($schema, $param) {

    $menu = array(
      'STAND' => 'Standard',
      'HOUR' => 'Hourly'
    );
    
    return $menu;
    
  }
  
}