<?php

namespace Kula\HEd\Bundle\GradingBundle\Field;

use Kula\Core\Component\Field\Field;

class DegreeRequirementGroup extends Field {
  
  public function select($schema, $param) {

    $menu = array();
    
    $result = $this->db()->db_select('STUD_STUDENT_DEGREES', 'studegrees')
      ->fields('studegrees', array('EFFECTIVE_DATE'))
      ->join('STUD_DEGREE', 'degree', 'studegrees.DEGREE_ID = degree.DEGREE_ID')
      ->fields('degree', array('DEGREE_NAME', 'DEGREE_ID'))
      ->leftJoin('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentrations', 'studegrees.STUDENT_DEGREE_ID = stuconcentrations.STUDENT_DEGREE_ID')
      ->leftJoin('STUD_DEGREE_REQ_GRP', 'degreereqgrp', 'degreereqgrp.DEGREE_ID = degree.DEGREE_ID OR degreereqgrp.CONCENTRATION_ID = stuconcentrations.CONCENTRATION_ID')
      ->fields('degreereqgrp', array('DEGREE_REQ_GRP_ID', 'GROUP_NAME'))
      ->condition('studegrees.STUDENT_ID', $param['STUDENT_ID'])
      ->orderBy('EFFECTIVE_DATE', 'DESC')
      ->orderBy('GROUP_NAME', 'ASC')
      ->execute();
    while ($row = $result->fetch()) {
      
      $degree_info = $row['DEGREE_NAME'].' - '.date('m/d/Y', strtotime($row['EFFECTIVE_DATE']));
      $menu[$degree_info][$row['DEGREE_REQ_GRP_ID']] = $row['GROUP_NAME'];
      
      unset($degree_info);
    }
    
    return $menu;
    
  }
  
}