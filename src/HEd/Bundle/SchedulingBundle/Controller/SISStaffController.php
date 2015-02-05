<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISStaffController extends Controller {
  
  public function staff_scheduleAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Staff.SchoolTerm');
    
    $classes = array();
    
    if ($this->record->getSelectedRecordID()) {
    
    $classes = $this->db()->select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_SECTION_MEETINGS', 'meetings', 'meetings.SECTION_ID = section.SECTION_ID')
      ->fields('meetings', array('MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'))
      ->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = meetings.ROOM_ID')
      ->fields('rooms', array('ROOM_NUMBER'))
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->condition('section.STAFF_ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->execute()->fetchAll();
    
      foreach($classes as $key => $class) {
        $classes[$key]['meets'] = '';
        if ($class['MON'] == 'Y') $classes[$key]['meets'] .= 'M';
        if ($class['TUE'] == 'Y') $classes[$key]['meets'] .= 'T';
        if ($class['WED'] == 'Y') $classes[$key]['meets'] .= 'W';
        if ($class['THU'] == 'Y') $classes[$key]['meets'] .= 'R';
        if ($class['FRI'] == 'Y') $classes[$key]['meets'] .= 'F';
        if ($class['SAT'] == 'Y') $classes[$key]['meets'] .= 'S';
        if ($class['SUN'] == 'Y') $classes[$key]['meets'] .= 'U';
      }
    
    }
    
    return $this->render('KulaHEdSchedulingBundle:SISStaff:schedule.html.twig', array('classes' => $classes));  
  }
  
}