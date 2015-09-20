<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class TeacherStudentScheduleController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('Teacher.HEd.Advisor.Student');

    return $this->render('KulaHEdSchedulingBundle:TeacherStudentSchedule:index.html.twig', array('classes' => $this->_currentSchedule()));
  }
  
  private function _currentSchedule() {
    $classes = array();
    
    $classes = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'LEVEL', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED', 'COURSE_ID'))
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->leftJoin('STUD_COURSE', 'course2', 'course2.COURSE_ID = class.COURSE_ID')
      ->fields('course2', array('COURSE_NUMBER' => 'second_COURSE_NUMBER', 'COURSE_TITLE'  => 'second_COURSE_TITLE'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_SECTION_MEETINGS', 'meetings', 'meetings.SECTION_ID = section.SECTION_ID')
      ->fields('meetings', array('SECTION_MEETING_ID', 'MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'))
      ->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = meetings.ROOM_ID')
      ->fields('rooms', array('ROOM_NUMBER'))
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->condition('class.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
      ->condition('class.DROPPED', '0')
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->execute()->fetchAll();

    return $classes;
  }
  
}