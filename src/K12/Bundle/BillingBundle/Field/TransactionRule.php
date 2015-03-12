<?php

namespace Kula\K12\Bundle\BillingBundle\Field;

use Kula\Core\Component\Field\Field;

class TransactionRule extends Field {
  
  public function select($schema, $param) {

    $menu = array(
      'NEWSTU' => 'New Student',
      'ALLSTU' => 'All Students',
      'TUITION' => 'Tuition',
      'AUDIT' => 'Audit',
      'OVERLOAD' => 'Overload',
      'LATE' => 'Late Fee',
      'ADDDROP' => 'Add/Drop Fee'
    );
    
    return $menu;
    
  }
  
}