<?php

namespace Kula\K12\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreSectionQuickPlacementController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Section');
    
    // Edit classes
    $edit = $this->request->request->get('edit');
    if ($edit AND isset($edit['K12.Student.Class'])) {
      $poster = $this->container->get('kula.core.poster');
      $poster->editMultiple($this->request->request->get('edit'));
      $poster->process();
    }
    
    // Add classes
    $add = $this->request->request->get('add');
    if ($add AND isset($add['K12.Student.Class'])) {
      
      $schedule_service = $this->get('kula.K12.scheduling.schedule');
      foreach($add['K12.Student.Class'] as $stustatusid => $section_id) {
        if ($section_id != '')
          $schedule_service->addClassForStudentStatus($stustatusid, $section_id, date('Y-m-d'));
      }
      
    }
    
    $students = array();
    $students_with_course = array();
    $course_to_search = null;
    $sections = array();
    
    $non = $this->request->request->get('non');
    if ($non AND isset($non['K12.Section']['search_for_section']['K12.Section.CourseID']['value'])) {
      $course_to_search = $non['K12.Section']['search_for_section']['K12.Section.CourseID']['value'];
    }
    
    if ($this->record->getSelectedRecordID() AND $course_to_search) {
      
      $sections[''] = '';
      $sections_result = $this->db()->db_select('STUD_SECTION', 'sec')
        ->fields('sec', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME'))
        ->join('STUD_COURSE', 'crs', 'crs.COURSE_ID = sec.COURSE_ID')
        ->fields('crs', array('COURSE_NUMBER'))
        ->condition('sec.COURSE_ID', $course_to_search)
        ->condition('sec.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
        ->execute();
      while ($sections_row = $sections_result->fetch()) {
        $sections[$sections_row['SECTION_ID']] = $sections_row['SECTION_NUMBER'].' '.$sections_row['SECTION_NAME'];
      }
      
      $students = $this->db()->db_select('STUD_STUDENT_CLASSES', 'cla')
        ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = cla.STUDENT_STATUS_ID')
        ->fields('stustatus', array('STUDENT_STATUS_ID'))
        ->join('STUD_STUDENT', 'stu', 'stu.STUDENT_ID = stustatus.STUDENT_ID')
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stu.STUDENT_ID')
        ->fields('cons', array('FIRST_NAME', 'LAST_NAME', 'PERMANENT_NUMBER', 'GENDER'))
        ->condition('cla.SECTION_ID', $this->record->getSelectedRecordID())
        ->condition('cla.DROPPED', 0)
        ->orderBy('LAST_NAME', 'ASC', 'constituent')
        ->orderBy('FIRST_NAME', 'ASC', 'constituent')
        ->execute()->fetchAll();
      
      $students_with_course_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'cla')
        ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = cla.STUDENT_STATUS_ID')
        ->fields('cla', array('STUDENT_CLASS_ID', 'STUDENT_STATUS_ID', 'SECTION_ID'))
        ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = cla.SECTION_ID')
        ->condition('sec.COURSE_ID', $course_to_search)
        ->condition('cla.DROPPED', 0)
        ->execute();
      while ($students_with_course_row = $students_with_course_result->fetch()) {
        $students_with_course[$students_with_course_row['STUDENT_STATUS_ID']] = $students_with_course_row['SECTION_ID'];
      }
      
    }
    
    return $this->render('KulaK12SchedulingBundle:CoreSectionQuickPlacement:index.html.twig', array('course_to_search' => $course_to_search, 'students' => $students, 'students_with_course' => $students_with_course, 'sections' => $sections));
  }
}