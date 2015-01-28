<?php

namespace Kula\HEd\Bundle\StudentBundle\Field;

use Kula\Core\Component\Field\Field;

class Document implements Field {
  
  public function select($schema, $param) {

    $menu = array();
    
    $result = $this->db()->db_select('STUD_DOCUMENT', 'doc')
      ->fields('doc', array('DOCUMENT_ID', 'DOCUMENT_CODE', 'DOCUMENT_NAME'))
      ->condition('INACTIVE', '0')
      ->orderBy('DOCUMENT_CODE', 'ASC')->execute();
    while ($row = $result->fetch()) {
      $menu[$row['DOCUMENT_ID']] = $row['DOCUMENT_CODE'].' - '.$row['DOCUMENT_NAME'];
    }
    
    return $menu;
    
  }
  
}