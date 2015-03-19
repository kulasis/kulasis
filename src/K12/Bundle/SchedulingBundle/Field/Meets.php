<?php

namespace Kula\K12\Bundle\SchedulingBundle\Field;

use Kula\Core\Component\Field\Field;

class Meets extends Field {
  
  public function calculate($data) {
    $meetings = '';
    $meetings_result = $this->db()->db_select('STUD_SECTION_MEETINGS', 'meetings')
      ->fields('meetings', array('MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN', 'START_TIME', 'END_TIME'))
      ->condition('meetings.SECTION_MEETING_ID', $data)
      ->orderBy('START_TIME', 'ASC');
    $meetings_result = $meetings_result->execute();
    while ($meetings_row = $meetings_result->fetch()) {
      
      if ($meetings_row['MON']) $meetings .= 'M';
      if ($meetings_row['TUE']) $meetings .= 'T';
      if ($meetings_row['WED']) $meetings .= 'W';
      if ($meetings_row['THU']) $meetings .= 'R';
      if ($meetings_row['FRI']) $meetings .= 'F';
      if ($meetings_row['SAT']) $meetings .= 'S';
      if ($meetings_row['SUN']) $meetings .= 'U';
      
    }
    
    return $meetings;
    
  }
  
}