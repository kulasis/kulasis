<?php

namespace Kula\K12\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISStaffController extends Controller {
  
  public function staff_scheduleAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.K12.Staff.SchoolTerm');
    
    $classes = array();
    
    if ($this->record->getSelectedRecordID()) {
    
    $classes = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_SECTION_MEETINGS', 'meetings', 'meetings.SECTION_ID = section.SECTION_ID')
      ->fields('meetings', array('SECTION_MEETING_ID', 'START_TIME', 'END_TIME'))
      ->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = meetings.ROOM_ID')
      ->fields('rooms', array('ROOM_NUMBER'))
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->condition('section.STAFF_ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->execute()->fetchAll();
    
    }
    
    return $this->render('KulaK12SchedulingBundle:SISStaff:schedule.html.twig', array('classes' => $classes));  
  }
  
}