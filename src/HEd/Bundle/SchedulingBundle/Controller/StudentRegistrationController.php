<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class StudentRegistrationController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('Student.HEd.Student');
    
    $registration = array();
    
    if ($this->record->getSelectedRecordID()) {
      $registration = $this->get('kula.HEd.scheduling.registration')->getAvailableRegistration($this->record->getSelectedRecordID());    
    }

    return $this->render('KulaHEdSchedulingBundle:StudentRegistration:index.html.twig', array('registration' => $registration));
  }
  
  public function startAction($registration_id) {
    $this->authorize();
    $this->setRecordType('Student.HEd.Student');
    
    if ($this->record->getSelectedRecordID()) {
      $student_status_id = $this->get('kula.HEd.scheduling.registration')->enroll($registration_id);
      return $this->redirectToRoute('Student_HEd_Registration_Catalog', array('student_status_id' => $student_status_id['student_status']));
    }
    
  }
  
  public function catalogAction($student_status_id) {
    $this->authorize();
    $this->setRecordType('Student.HEd.Student.Status');

    $student_status = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('ORGANIZATION_TERM_ID'))
        ->condition('STUDENT_STATUS_ID', $student_status_id)
        ->execute()->fetch();

    $query = $this->db()->db_select('STUD_SECTION', 'STUD_SECTION');
    $query = $query->fields('STUD_SECTION', array('SECTION_ID', 'SECTION_NUMBER', 'CAPACITY', 'ENROLLED_TOTAL', 'CREDITS', 'WAIT_LISTED_TOTAL'));
    $query = $query->join('STUD_COURSE', 'course', 'STUD_SECTION.COURSE_ID = course.COURSE_ID');
    $query = $query->fields('course', array('COURSE_NUMBER','COURSE_TITLE'));
    $query = $query->leftJoin('STUD_SECTION_MEETINGS', 'meetings', 'meetings.SECTION_ID = STUD_SECTION.SECTION_ID');
    $query = $query->fields('meetings', array('SECTION_MEETING_ID', 'START_TIME', 'END_TIME'));
    $query = $query->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = meetings.ROOM_ID');
    $query = $query->fields('rooms', array('ROOM_NUMBER'));
    $query = $query->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = STUD_SECTION.STAFF_ORGANIZATION_TERM_ID');
    $query = $query->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID');
    $query = $query->fields('staff', array('ABBREVIATED_NAME'));
    $query = $query->condition('STUD_SECTION.STATUS', null);
    $query = $query->condition('STUD_SECTION.ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID']);
    //if (count($current_section_ids) > 0) $query = $query->condition('STUD_SECTION.SECTION_ID', $current_section_ids, 'NOT IN');
    $query = $query->leftJoin('CONS_CONSTITUENT', 'CONS_CONSTITUENT', 'CONS_CONSTITUENT.CONSTITUENT_ID = staff.STAFF_ID');
    $query = $query->orderBy('SECTION_NUMBER', 'ASC');
    $search_classes = $query->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:StudentRegistration:catalog.html.twig', array('search_classes' => $search_classes));
  }
  
  public function viewCatalogAction($organization_term_id) {
    $this->authorize();
    
    $search_classes = array();
    
    $query = $this->db()->db_select('STUD_SECTION', 'STUD_SECTION');
    $query = $query->fields('STUD_SECTION', array('SECTION_ID', 'SECTION_NUMBER', 'CAPACITY', 'ENROLLED_TOTAL', 'CREDITS', 'WAIT_LISTED_TOTAL'));
    $query = $query->join('STUD_COURSE', 'course', 'STUD_SECTION.COURSE_ID = course.COURSE_ID');
    $query = $query->fields('course', array('COURSE_NUMBER','COURSE_TITLE'));
    $query = $query->leftJoin('STUD_SECTION_MEETINGS', 'meetings', 'meetings.SECTION_ID = STUD_SECTION.SECTION_ID');
    $query = $query->fields('meetings', array('SECTION_MEETING_ID', 'START_TIME', 'END_TIME'));
    $query = $query->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = meetings.ROOM_ID');
    $query = $query->fields('rooms', array('ROOM_NUMBER'));
    $query = $query->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = STUD_SECTION.STAFF_ORGANIZATION_TERM_ID');
    $query = $query->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID');
    $query = $query->fields('staff', array('ABBREVIATED_NAME'));
    $query = $query->condition('STUD_SECTION.STATUS', null);
    $query = $query->condition('STUD_SECTION.ORGANIZATION_TERM_ID', $organization_term_id);
    //if (count($current_section_ids) > 0) $query = $query->condition('STUD_SECTION.SECTION_ID', $current_section_ids, 'NOT IN');
    $query = $query->leftJoin('CONS_CONSTITUENT', 'CONS_CONSTITUENT', 'CONS_CONSTITUENT.CONSTITUENT_ID = staff.STAFF_ID');
    $query = $query->orderBy('SECTION_NUMBER', 'ASC');
    $search_classes = $query->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:StudentRegistration:view_catalog.html.twig', array('search_classes' => $search_classes));
  }
  
}