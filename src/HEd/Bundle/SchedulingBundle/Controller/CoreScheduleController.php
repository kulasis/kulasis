<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreScheduleController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student.Status');
    
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

    return $this->render('KulaHEdSchedulingBundle:CoreSchedule:index.html.twig', array('drop_date' => $drop_date, 'classes' => $this->_currentSchedule()));
  }
  
  public function gradesAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student.Status');
    
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
          $course_history_service->updateCourseHistoryForClass($student_course_history_id, $mark['HEd.Student.CourseHistory.Mark'], isset($mark['HEd.Student.CourseHistory.Comments']) ? $mark['HEd.Student.CourseHistory.Comments'] : null);
        else
          $course_history_service->deleteCourseHistoryForClass($student_course_history_id);
      }
    }
    
    $classes = array();
    
    $classes = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED', 'DROPPED', 'DROP_DATE'))
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME'))
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
    
    return $this->render('KulaHEdSchedulingBundle:CoreSchedule:grades.html.twig', array('classes' => $classes));
  }
  
  public function historyAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student.Status');
    
    $classes = array();
    
    $classes = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'DROP_DATE', 'CREATED_TIMESTAMP', 'PAID'))
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'CREDITS'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->condition('class.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->execute()->fetchAll();
    
    $classes_history = array();
    
    $classes_history = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'DROP_DATE', 'CREATED_TIMESTAMP', 'PAID'))
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME', 'CREDITS'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->condition('org.ORGANIZATION_ID', $this->focus->getSchoolIDs())
      ->condition('stustatus.STUDENT_ID', $this->record->getSelectedRecord()['STUDENT_ID'])
      ->orderBy('START_DATE', 'DESC', 'term')
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:CoreSchedule:history.html.twig', array('classes' => $classes, 'classes_history' => $classes_history));
  }
  
  public function detailAction($id) {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Student.Status');
      
    $class = array();
    
    $class = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'CHANGE_REASON', 'CHANGE_NOTES', 'DEGREE_REQ_GRP_ID', 'COURSE_ID', 'REPEAT_TAG_ID', 'REGISTRATION_TYPE'))
      ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
      ->condition('STUDENT_CLASS_ID', $id)
      ->execute()->fetch();
        
    return $this->render('KulaHEdSchedulingBundle:CoreSchedule:schedule_detail.html.twig', array('class' => $class));  
  }
  
  public function addAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student.Status');
    $start_date = '';
    
    if ($this->request->request->get('add')) {  
      $schedule_service = $this->get('kula.HEd.scheduling.schedule');

      if (isset($this->request->request->get('add')['HEd.Student.Class']['new']['HEd.Student.Class.SectionID']))
        $new_classes = $this->request->request->get('add')['HEd.Student.Class']['new']['HEd.Student.Class.SectionID'];
      $start_date = date('Y-m-d', strtotime($this->request->request->get('add')['HEd.Student.Class']['new']['HEd.Student.Class.StartDate']));
      
      if (isset($new_classes)) {
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
      return $this->forward('Core_HEd_Scheduling_StudentSchedule', array('record_type' => 'Core.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()));
    }
    
    if ($this->request->request->get('wait_list')) {
      return $this->forward('Core_HEd_Scheduling_StudentSchedule_WaitList', array('record_type' => 'Core.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()));
    }
    
    $search_classes = array();
    
    if ($this->request->request->get('search')) {
      $current_section_ids = array();
      
      // Get current classes
      $current_section_ids_result = $this->db()->db_select('STUD_STUDENT_CLASSES')
        ->fields('STUD_STUDENT_CLASSES', array('SECTION_ID'))
        ->condition('STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
        ->condition('DROPPED', '0')
        ->execute();
      while ($row = $current_section_ids_result->fetch()) {
        $current_section_ids[] = $row['SECTION_ID'];
      }
      
      $query = $this->searcher->prepareSearch($this->request->request->get('search'), 'STUD_SECTION', 'SECTION_ID');
      $query = $query->fields('STUD_SECTION', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME', 'CAPACITY', 'ENROLLED_TOTAL', 'CREDITS', 'WAIT_LISTED_TOTAL'));
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
      $query = $query->condition('STUD_SECTION.ORGANIZATION_TERM_ID', $this->record->getSelectedRecord()['ORGANIZATION_TERM_ID']);
      if (count($current_section_ids) > 0) $query = $query->condition('STUD_SECTION.SECTION_ID', $current_section_ids, 'NOT IN');
      $query = $query->leftJoin('CONS_CONSTITUENT', 'CONS_CONSTITUENT', 'CONS_CONSTITUENT.CONSTITUENT_ID = staff.STAFF_ID');
      $query = $query->orderBy('SECTION_NUMBER', 'ASC');
      $query = $query->range(0, 100);
      $search_classes = $query->execute()->fetchAll();
      
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
    
    return $this->render('KulaHEdSchedulingBundle:CoreSchedule:add.html.twig', array('search_classes' => $search_classes, 'classes' => $this->_currentSchedule(), 'start_date' => $start_date));  
  }
  
  public function waitlistAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student.Status');
    
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
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgtrm', 'section.STAFF_ORGANIZATION_TERM_ID = stafforgtrm.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_SECTION_MEETINGS', 'meetings', 'meetings.SECTION_ID = section.SECTION_ID')
      ->fields('meetings', array('SECTION_MEETING_ID', 'MON','TUE','WED','THU','FRI','SAT','SUN', 'START_TIME', 'END_TIME'))
      ->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = meetings.ROOM_ID')
      ->fields('rooms', array('ROOM_NUMBER'))
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgtrm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->condition('waitlist.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->execute()->fetchAll();
    
    foreach($classes as $key => $class) {
      $classes[$key]['meets'] = '';
      if ($class['MON'] == '1') $classes[$key]['meets'] .= 'M';
      if ($class['TUE'] == '1') $classes[$key]['meets'] .= 'T';
      if ($class['WED'] == '1') $classes[$key]['meets'] .= 'W';
      if ($class['THU'] == '1') $classes[$key]['meets'] .= 'R';
      if ($class['FRI'] == '1') $classes[$key]['meets'] .= 'F';
      if ($class['SAT'] == '1') $classes[$key]['meets'] .= 'S';
      if ($class['SUN'] == '1') $classes[$key]['meets'] .= 'U';
    }
    
    return $this->render('KulaHEdSchedulingBundle:CoreSchedule:waitlist.html.twig', array('classes' => $classes));
  }
  
  public function calculateTotalsAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student.Status');
    
    $student_billing_service = $this->get('kula.HEd.billing.student');
    
    if ($this->record->getSelectedRecordID()) {
      $student_billing_service->processBilling($this->record->getSelectedRecordID(), 'Schedule Changed');
      $this->addFlash('success', 'Recalculated bill.');
    } else {
      // get all students for orgyr term
      $stus_result = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID'))
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stustatus.STUDENT_ID')
        ->fields('cons', array('PERMANENT_NUMBER'))
        ->condition('stustatus.STATUS', null, 'IS NULL')
        ->condition('stustatus.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
        ->execute();
      while ($stu = $stus_result->fetch()) {
        $student_billing_service->processBilling($stu['STUDENT_STATUS_ID'], null, false);
        $this->addFlash('success', 'Recalculated bill for ' . $stu['PERMANENT_NUMBER'] . '.');
      }
    }
    
    return $this->forward('Core_HEd_Scheduling_StudentSchedule', array('record_type' => 'Core.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.HEd.Student.Status', 'record_id' => $this->record->getSelectedRecordID()));
  }
  
  private function _currentSchedule() {
    $classes = array();
    
    $classes = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'LEVEL', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED', 'COURSE_ID', 'PAID'))
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME'))
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
    /*
    foreach($classes as $key => $class) {
      $classes[$key]['meets'] = '';
      if ($class['MON'] == '1') $classes[$key]['meets'] .= 'M';
      if ($class['TUE'] == '1') $classes[$key]['meets'] .= 'T';
      if ($class['WED'] == '1') $classes[$key]['meets'] .= 'W';
      if ($class['THU'] == '1') $classes[$key]['meets'] .= 'R';
      if ($class['FRI'] == '1') $classes[$key]['meets'] .= 'F';
      if ($class['SAT'] == '1') $classes[$key]['meets'] .= 'S';
      if ($class['SUN'] == '1') $classes[$key]['meets'] .= 'U';
    }
    */
    return $classes;
  }
}