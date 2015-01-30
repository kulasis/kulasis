<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Field;

use Kula\Core\Component\Field\Field;

class Meets extends Field {
  
  public static function calculate($data) {
    
    $meetings = array();
    
    $meetings_result = $this->db()->db_select('SCHD_SECTION_MEETINGS', 'meetings')
      ->fields('meetings', array('MEETING_DAY', 'START_TIME', 'END_TIME'))
      ->join('CORE_LOOKUP_VALUES', 'values', 'values.CODE = meetings.MEETING_DAY AND values.LOOKUP_ID = 29')
      ->condition('meetings.SECTION_ID', $data)
      ->orderBy('SECTION_ID', 'ASC')
      ->orderBy('SORT', 'ASC');
    $meetings_result = $meetings_result->execute();
    $i = 0;
    while ($meetings_row = $meetings_result->fetch()) {
      
      $meetings[$i] = $meetings_row['MEETING_DAY'] . ' ( ' . $meetings_row['START_TIME'] ' - ' . $meetings_row['END_TIME'] . ' )';
      
    $i++;
    }
    
    return implode(', ', $meetings);
    
  }
  
}