<?php

namespace Kula\HEd\Bundle\SchoolBundle\Field;

use Kula\Core\Component\Field\Field;

class DefaultEnterDateAction extends Field {
  
  public function select($schema, $param) {

    $menu = array(
      'TODAY' => "Use Today's Date",
      'TERM' => 'Use Term Start Date'
    );
    
    return $menu;
    
  }
  
}