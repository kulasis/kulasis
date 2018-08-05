<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class TeacherSectionController extends Controller {
  
  public function rosterAction() {
    $this->authorize();
    $this->setRecordType('Teacher.HEd.Section');

    $students = array();
    
    $students = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'PAID', 'CREDITS_ATTEMPTED'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID', 'LEVEL', 'GRADE', 'ENTER_CODE'))
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = class.SECTION_ID')
      ->fields('sec', array('CREDITS'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'constituent', 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->leftJoin('STUD_STUDENT_DEGREES_AREAS', 'stuareas', 'stuareas.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->fields('stuareas', array('AREA_ID'))
      ->leftJoin('CONS_EMAIL_ADDRESS', 'email', 'email.CONSTITUENT_ID = constituent.CONSTITUENT_ID AND email.EMAIL_ADDRESS_ID = constituent.PRIMARY_EMAIL_ID')
      ->fields('email', array('EMAIL_ADDRESS', 'EMAIL_ADDRESS_TYPE'))
      ->condition('class.SECTION_ID', $this->record->getSelectedRecordID())
      ->condition('class.DROPPED', 0)
      ->orderBy('DROPPED', 'ASC')
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:TeacherSection:roster.html.twig', array('students' => $students));
  }

  public function dropped_rosterAction() {
    $this->authorize();
    $this->setRecordType('Teacher.HEd.Section');
    
    $students = array();
    
    $students = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'PAID', 'CREDITS_ATTEMPTED'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID', 'LEVEL', 'GRADE', 'ENTER_CODE'))
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = class.SECTION_ID')
      ->fields('sec', array('CREDITS'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'constituent', 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->leftJoin('STUD_STUDENT_DEGREES_AREAS', 'stuareas', 'stuareas.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->fields('stuareas', array('AREA_ID'))
      ->leftJoin('CONS_EMAIL_ADDRESS', 'email', 'email.CONSTITUENT_ID = constituent.CONSTITUENT_ID AND email.EMAIL_ADDRESS_ID = constituent.PRIMARY_EMAIL_ID')
      ->fields('email', array('EMAIL_ADDRESS', 'EMAIL_ADDRESS_TYPE'))
      ->condition('class.SECTION_ID', $this->record->getSelectedRecordID())
      ->condition('class.DROPPED', 1)
      ->orderBy('DROPPED', 'ASC')
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:TeacherSection:roster.html.twig', array('students' => $students));
  }

}