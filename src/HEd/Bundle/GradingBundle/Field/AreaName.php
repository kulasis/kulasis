<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class AreaName extends Field {
  
  public function select($schema, $param) {
    
    $menu = array();
    
    $result = $this->db()->db_select('STUD_DEGREE_AREA', 'area')
      ->fields('area', array('AREA_ID', 'AREA_NAME'))
      ->join('CORE_LOOKUP_VALUES', 'area_types', "area_types.CODE = area.AREA_TYPE AND area_types.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Grading.Degree.AreaTypes')")
      ->fields('area_types', array('DESCRIPTION' => 'area_type'))
      ->orderBy('DESCRIPTION', 'ASC')
      ->orderBy('AREA_NAME', 'ASC')
      ->execute();
    while ($row = $result->fetch()) {
      $menu[$row['AREA_ID']] = $row['AREA_NAME'].' - '.$row['area_type'];
    }
    
    return $menu;
    
  }
  
}