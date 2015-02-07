<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISScheduleController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student.Status');
    
    // set start date
    $term_info = $this->db()->db_select('CORE_TERM')
      ->fields('CORE_TERM', array('START_DATE', 'END_DATE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'CORE_TERM.TERM_ID = orgterms.TERM_ID')
      ->condition('ORGANIZATION_TERM_ID', $this->record->getSelectedRecord()['ORGANIZATION_TERM_ID'])
      ->execute()->fetch();
    
    if ($term_info['START_DATE'] < date('Y-m-d'))
      $drop_date = date('Y-m-d');
    else
      $drop_date = $term_info['START_DATE'];
    
    if ($this->request->request->get('drop')) {
      
      $schedule_service = $this->get('kula.HEd.scheduling.schedule');
      
      $classes_to_delete = $this->request->request->get('drop')['HEd.Student.Class'];
      $drop_date = date('Y-m-d', strtotime($this->request->request->get('non')['HEd.Student.Class']['HEd.Student.Class.DropDate']));
      
      foreach($classes_to_delete as $class_id => $class_row) {
        $schedule_service->dropClassForStudentStatus($class_id, $drop_date);
      }
      
    }

    return $this->render('KulaHEdSchedulingBundle:SISSchedule:index.html.twig', array('drop_date' => $drop_date, 'classes' => $this->_currentSchedule()));
  }
  
  public function gradesAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student.Status');
    
    // Add new grades
    if ($this->request->request->get('add')) {
      $course_history_service = $this->get('kula.HEd.grading.coursehistory');
      $new_grades = $this->request->request->get('add')['HEd.Student.CourseHistory']['new'];
      foreach($new_grades as $student_class_id => $mark) {
        if (isset($mark['HEd.Student.CourseHistory.Mark']))
          $course_history_service->insertCourseHistoryForClass($student_class_id, $mark['HEd.Student.CourseHistory.Mark']);
      }
    }
    
    // Edit grades
    $edit_request = $this->request->request->get('edit');
    if (isset($edit_request['HEd.Student.CourseHistory'])) {
      $course_history_service = $this->get('kula.HEd.grading.coursehistory');
      $edit_grades = $this->request->request->get('edit')['HEd.Student.CourseHistory'];
      foreach($edit_grades as $student_course_history_id => $mark) {
        if (isset($mark['HEd.Student.CourseHistory.Mark']) AND $mark['HEd.Student.CourseHistory.Mark'] != '') 
          $course_history_service->updateCourseHistoryForClass($student_course_history_id, $mark['HEd.Student.CourseHistory.Mark'], $mark['HEd.Student.CourseHistory.Comments']);
        else
          $course_history_service->deleteCourseHistoryForClass($student_course_history_id);
      }
    }
    
    $classes = array();
    
    $classes = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED', 'DROPPED', 'DROP_DATE'))
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', 'coursehistory.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->fields('coursehistory', array('COURSE_HISTORY_ID', 'MARK', 'COMMENTS'))
      ->leftJoin('STUD_MARK_SCALE_MARKS', 'scalemarks', 'scalemarks.MARK = coursehistory.MARK AND class.MARK_SCALE_ID = scalemarks.MARK_SCALE_ID')
      ->fields('scalemarks', array('ALLOW_COMMENTS', 'REQUIRE_COMMENTS'))
      ->condition('class.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
      ->orderBy('DROPPED', 'ASC')
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->orderBy('DROP_DATE', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:SISSchedule:grades.html.twig', array('classes' => $classes));
  }
  
  public function historyAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student.Status');
    
    $classes = array();
    
    $classes = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'DROP_DATE', 'CREATED_TIMESTAMP'))
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'CREDITS'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->condition('class.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:SISSchedule:history.html.twig', array('classes' => $classes));
  }
  
  public function detailAction($id, $sub_id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student.Status');
      
    $class = array();
    
    $class = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'CHANGE_REASON', 'CHANGE_NOTES', 'DEGREE_REQ_GRP_ID'))
      ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
      ->condition('STUDENT_CLASS_ID', $sub_id)
      ->execute()->fetch();
        
    return $this->render('KulaHEdSchedulingBundle:SISSchedule:schedule_detail.html.twig', array('class' => $class));  
  }
  
  public function addAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student.Status');
    $start_date = '';
    
    if ($this->request->request->get('add')) {  
      $schedule_service = $this->get('kula.HEd.scheduling.schedule');

      if (isset($this->request->request->get('add')['HEd.Student.Class']['new']['HEd.Student.Class.SectionID']))
        $new_classes = $this->request->request->get('add')['HEd.Student.Class']['new']['HEd.Student.Class.SectionID'];
      $start_date = date('Y-m-d', strtotime($this->request->request->get('add')['HEd.Student.Class']['new']['HEd.Student.Class.StartDate']));
      
      if (isset($new_classes)) {
        var_dump($new_classes);
        foreach($new_classes as $new_class) {
          $schedule_service->addClassForStudentStatus($this->record->getSelectedRecordID(), $new_class, $start_date);
        }
      }
    }
    
    if ($this->request->request->get('wait_list')) {
      $schedule_service = $this->get('kula.HEd.scheduling.schedule');
      
      if (isset($this->request->request->get('wait_list')['HEd.Student.WaitList']['HEd.Student.WaitList.SectionID']))
        $new_classes = $this->request->request->get('wait_list')['HEd.Student.WaitList']['HEd.Student.WaitList.SectionID'];
      
      if (isset($new_classes)) {
        foreach($new_classes as $new_class) {
          $schedule_service->addWaitListClassForStudentStatus($this->record->getSelectedRecordID(), $new_class);
        }
      }
    }
    
    if ($this->request->request->get('add')) {
      return $this->forward('sis_HEd_student_schedule', array('record_type' => 'SIS.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'SIS.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()));
    }
    
    if ($this->request->request->get('wait_list')) {
      return $this->forward('sis_HEd_student_schedule_waitlist', array('record_type' => 'SIS.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'SIS.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()));
    }
    
    $search_classes = array();
    
    if ($this->request->request->get('search')) {
      $current_section_ids = array();
      $condition_or = $this->db()->db_or();
      $condition_or = $condition_or->condition('DROPPED', null)->condition('DROPPED', '0');
      
      // Get current classes
      $current_section_ids_result = $this->db()->db_select('STUD_STUDENT_CLASSES')
        ->fields('STUD_STUDENT_CLASSES', array('SECTION_ID'))
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
        ->condition($condition_or)
        ->execute();
      while ($row = $current_section_ids_result->fetch()) {
        $current_section_ids[] = $row['SECTION_ID'];
      }
      
      $query = $this->searcher->prepareSearch($this->request->request->get('search'), 'STUD_SECTION', 'SECTION_ID');
      $query = $query->fields('STUD_SECTION', array('SECTION_ID', 'SECTION_NUMBER', 'CAPACITY', 'ENROLLED_TOTAL', 'CREDITS', 'WAIT_LISTED_TOTAL'));
      $query = $query->join('STUD_COURSE', 'course', 'STUD_SECTION.COURSE_ID = course.COURSE_ID');
      $query = $query->fields('course', array('COURSE_NUMBER','COURSE_TITLE'));
      $query = $query->leftJoin('STUD_SECTION_MEETINGS', 'meetings', 'meetings.SECTION_ID = STUD_SECTION.SECTION_ID');
      $query = $query->fields('meetings', array('MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'));
      $query = $query->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = meetings.ROOM_ID');
      $query = $query->fields('rooms', array('ROOM_NUMBER'));
      $query = $query->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = STUD_SECTION.STAFF_ORGANIZATION_TERM_ID');
      $query = $query->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID');
      $query = $query->fields('staff', array('ABBREVIATED_NAME'));
      $query = $query->condition('STUD_SECTION.STATUS', null);
      $query = $query->condition('STUD_SECTION.ORGANIZATION_TERM_ID', $this->record->getSelectedRecord()['ORGANIZATION_TERM_ID']);
      if (count($current_section_ids) > 0) $query = $query->condition('STUD_SECTION.SECTION_ID', $current_section_ids, 'NOT IN');
      $query = $query->orderBy('SECTION_NUMBER', 'ASC');
      $query = $query->range(0, 100);
      $search_classes = $query->execute()->fetchAll();
      
      foreach($search_classes as $key => $class) {
        $search_classes[$key]['meets'] = '';
        if ($class['MON'] == 'Y') $search_classes[$key]['meets'] .= 'M';
        if ($class['TUE'] == 'Y') $search_classes[$key]['meets'] .= 'T';
        if ($class['WED'] == 'Y') $search_classes[$key]['meets'] .= 'W';
        if ($class['THU'] == 'Y') $search_classes[$key]['meets'] .= 'R';
        if ($class['FRI'] == 'Y') $search_classes[$key]['meets'] .= 'F';
        if ($class['SAT'] == 'Y') $search_classes[$key]['meets'] .= 'S';
        if ($class['SUN'] == 'Y') $search_classes[$key]['meets'] .= 'U';
      }
      
      // set start date
      $term_info = $this->db()->db_select('CORE_TERM')
        ->fields('CORE_TERM', array('START_DATE'))
        ->join('CORE_ORGANIZATION_TERMS', null, 'CORE_TERM.TERM_ID = CORE_ORGANIZATION_TERMS.TERM_ID')
        ->condition('ORGANIZATION_TERM_ID', $this->record->getSelectedRecord()['ORGANIZATION_TERM_ID'])
        ->execute()->fetch();
      
      if ($term_info['START_DATE'] < date('Y-m-d'))
        $start_date = date('Y-m-d');
      else
        $start_date = $term_info['START_DATE'];
      
    } else {
      $this->setSubmitMode('search');
      
    }
    
    return $this->render('KulaHEdSchedulingBundle:SISSchedule:add.html.twig', array('search_classes' => $search_classes, 'classes' => $this->_currentSchedule(), 'start_date' => $start_date));  
  }
  
  public function waitlistAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student.Status');
    
    if ($this->request->request->get('drop')) {
      
      $schedule_service = $this->get('kula.HEd.scheduling.schedule');
      
      $classes_to_delete = $this->request->request->get('drop')['HEd.Student.WaitList'];
      
      foreach($classes_to_delete as $class_id => $class_row) {
        $schedule_service->dropWaitListClassForStudentStatus($class_id);
      }
    }
    
    $classes = array();
    
    $classes = $this->db()->db_select('STUD_STUDENT_WAIT_LIST', 'waitlist')
      ->fields('waitlist', array('STUDENT_WAIT_LIST_ID', 'ADDED_TIMESTAMP'))
      ->join('STUD_SECTION', 'section', 'waitlist.SECTION_ID = section.SECTION_ID')
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
      ->condition('waitlist.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
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
    
    return $this->render('KulaHEdSchedulingBundle:SISSchedule:waitlist.html.twig', array('classes' => $classes));
  }
  
  public function calculateTotalsAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student.Status');
    
    $student_billing_service = $this->get('kula.HEd.billing.student');
    
    $student_billing_service->processBilling($this->record->getSelectedRecordID(), 'Schedule Changed');
    
    return $this->forward('sis_HEd_student_schedule', array('record_type' => 'SIS.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'SIS.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()));
  }
  
  private function _currentSchedule() {
    $classes = array();
    
    $condition_or = $this->db()->db_or();
    $condition_or = $condition_or->condition('class.DROPPED', null)->condition('class.DROPPED', 'N');
    
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
      ->fields('meetings', array('MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'))
      ->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = meetings.ROOM_ID')
      ->fields('rooms', array('ROOM_NUMBER'))
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->condition('class.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
      ->condition($condition_or)
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
    
    return $classes;  
  }
}