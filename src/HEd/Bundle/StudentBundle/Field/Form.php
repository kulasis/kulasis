<?php

namespace Kula\HEd\Bundle\StudentBundle\Field;

use Kula\Core\Component\Field\Field;

class Form extends Field {
  
  public function select($schema, $param) {

    $menu = array();

    $result = $this->db()->db_select('STUD_FORM', 'form')
      ->fields('form', array('FORM_ID', 'FORM_NAME'))
      ->orderBy('FORM_NAME', 'ASC');

    if ($param['ORGANIZATION_TERMS'])
      $result = $result->condition('form.ORGANIZATION_TERM_ID', $param['ORGANIZATION_TERMS']);
    
    $result = $result->execute();
    while ($row = $result->fetch()) {
      $menu[$row['FORM_ID']] = $row['FORM_NAME'];
    }
    
    return $menu;
    
  }
  
}

