<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISSectionController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Section');
    
    $section = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID','CREDITS', 'START_DATE', 'END_DATE', 'CAPACITY', 'MINIMUM', 'ENROLLED_TOTAL', 'WAIT_LISTED_TOTAL', 'MARK_SCALE_ID'))
      ->condition('section.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetch();
    
    $meeting_times = $this->db()->db_select('STUD_SECTION_MEETINGS')
      ->condition('SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:SISSection:index.html.twig', array('section' => $section, 'meeting_times' => $meeting_times));
  }
  
  public function coursesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Section');
    
    $courses = $this->db()->db_select('STUD_SECTION_COURSES', 'courses')
      ->fields('courses', array('SECTION_COURSE_ID', 'COURSE_ID'))
      ->condition('courses.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:SISSection:courses.html.twig', array('courses' => $courses));
  }
  
  public function staffAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Section');
    
    $staff = $this->db()->db_select('STUD_SECTION_STAFF', 'staff')
      ->fields('staff', array('SECTION_STAFF_ID', 'SECTION_ID', 'STAFF_ORGANIZATION_TERM_ID'))
      ->condition('staff.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:SISSection:staff.html.twig', array('staff' => $staff));
  }
  
  public function rosterAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Section');
    
    $students = array();
    
    // set start date
    $term_info = $this->db()->db_select('CORE_TERM')
      ->fields(null, array('START_DATE', 'END_DATE'))
      ->join('CORE_ORGANIZATION_TERMS', null, 'CORE_TERM.TERM_ID = CORE_ORGANIZATION_TERMS.TERM_ID')
      ->condition('ORGANIZATION_TERM_ID', $this->record->getSelectedRecord()['ORGANIZATION_TERM_ID'])
      ->execute()->fetch();
    
    if ($term_info['START_DATE'] < date('Y-m-d'))
      $drop_date = date('Y-m-d');
    else
      $drop_date = $term_info['START_DATE'];
    
    if ($this->request->request->get('delete')) {
      
      $schedule_service = $this->get('kula.HEd.scheduling.schedule');
      
      $classes_to_delete = $this->request->request->get('delete')['STUD_STUDENT_CLASSES'];
      $drop_date = date('Y-m-d', strtotime($this->request->request->get('edit')['STUD_STUDENT_CLASSES']['DROP_DATE']));
      
      foreach($classes_to_delete as $class_id => $class_row) {
        $schedule_service->dropClassForStudentStatus($class_id, $drop_date);
      }
      
    }
    
    $students = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'constituent', 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->condition('class.SECTION_ID', $this->record->getSelectedRecordID())
      ->orderBy('DROPPED', 'ASC')
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:SISSection:roster.html.twig', array('students' => $students, 'drop_date' => $drop_date));
  }
  
  public function waitlistAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Section');
    
    $students = array();
    
    if ($this->request->request->get('delete')) {
      
      $schedule_service = $this->get('kula.HEd.scheduling.schedule');
      
      $classes_to_delete = $this->request->request->get('delete')['STUD_STUDENT_WAIT_LIST'];
      
      foreach($classes_to_delete as $class_id => $class_row) {
        $schedule_service->dropWaitListClassForStudentStatus($class_id);
      }
      
    }
    
    $students = $this->db()->db_select('STUD_STUDENT_WAIT_LIST', 'waitlist')
      ->fields('waitlist', array('STUDENT_WAIT_LIST_ID', 'ADDED_TIMESTAMP'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = waitlist.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'constituent', 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->condition('waitlist.SECTION_ID', $this->record->getSelectedRecordID())
      ->orderBy('ADDED_TIMESTAMP', 'ASC')
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:SISSection:waitlist.html.twig', array('students' => $students));
  }
  
  public function gradesAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Section');
    
    // Add new grades
    if ($this->request->request->get('add')) {
      $course_history_service = $this->get('kula.HEd.grading.coursehistory');
      $new_grades = $this->request->request->get('add')['STUD_STUDENT_COURSE_HISTORY']['new'];
      foreach($new_grades as $student_class_id => $mark) {
        if (isset($mark['MARK']))
          $course_history_service->insertCourseHistoryForClass($student_class_id, $mark['MARK']);
      }
    }
    
    // Edit grades
    $edit = $this->request->request->get('edit');
    if (isset($edit['STUD_STUDENT_COURSE_HISTORY'])) {
      $course_history_service = $this->get('kula.HEd.grading.coursehistory');
      $edit_grades = $this->request->request->get('edit')['STUD_STUDENT_COURSE_HISTORY'];
      foreach($edit_grades as $student_course_history_id => $mark) {
        if (isset($mark['MARK']) AND $mark['MARK'] != '')
          $course_history_service->updateCourseHistoryForClass($student_course_history_id, $mark['MARK'], $mark['COMMENTS']);
        else
          $course_history_service->deleteCourseHistoryForClass($student_course_history_id);
      }
    }
    
    $students = array();
    
    $students = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'MARK_SCALE_ID', 'START_DATE', 'END_DATE', 'DROPPED'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'constituent', 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = class.SECTION_ID')    
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', 'coursehistory.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->fields('coursehistory', array('MARK', 'COURSE_HISTORY_ID', 'COMMENTS'))
      ->leftJoin('STUD_MARK_SCALE_MARKS', 'scalemarks', 'scalemarks.MARK = coursehistory.MARK AND class.MARK_SCALE_ID = scalemarks.MARK_SCALE_ID')
      ->fields('scalemarks', array('ALLOW_COMMENTS', 'REQUIRE_COMMENTS'))
      ->condition('class.SECTION_ID', $this->record->getSelectedRecordID())
      ->orderBy('DROPPED', 'ASC')
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    if (isset($edit['STUD_SECTION'])) {
      
      foreach ($edit['STUD_SECTION'] as $section_id => $section_row) {
        
        if (isset($section_row['TEACHER_GRADES_COMPLETED']['checkbox']) AND $section_row['TEACHER_GRADES_COMPLETED']['checkbox'] == 'Y' AND $section_row['TEACHER_GRADES_COMPLETED']['checkbox_hidden'] != 'Y') {
          // Set as finalized
          $poster = $this->newPoster();
          $info = array('STUD_SECTION' => array($this->record->getSelectedRecordID() => array(
            'TEACHER_GRADES_COMPLETED' => array('checkbox_hidden' => 'N', 'checkbox' => 'Y'),
            'TEACHER_GRADES_COMPLETED_USERSTAMP' => $this->session->get('user_id'),
            'TEACHER_GRADES_COMPLETED_TIMESTAMP' => date('Y-m-d H:i:s')
          )));

          $poster->newPoster(null, $info);
        }
        
        if (!isset($section_row['TEACHER_GRADES_COMPLETED']['checkbox']) AND $section_row['TEACHER_GRADES_COMPLETED']['checkbox_hidden'] == 'Y') {
          // Unset as finalized
          $poster = $this->newPoster();
          $info = array('STUD_SECTION' => array($this->record->getSelectedRecordID() => array(
            'TEACHER_GRADES_COMPLETED' => array('checkbox_hidden' => 'Y', 'checkbox' => null),
            'TEACHER_GRADES_COMPLETED_USERSTAMP' => null,
            'TEACHER_GRADES_COMPLETED_TIMESTAMP' => null
          )));

          $poster->newPoster(null, $info);
        }  
        
      }
      
    }
    
    // Get submitted grades info
    $submitted_grades_info = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('TEACHER_GRADES_COMPLETED', 'TEACHER_GRADES_COMPLETED_TIMESTAMP'))
      ->leftJoin('CORE_USER', 'user', 'user.USER_ID = section.TEACHER_GRADES_COMPLETED_USERSTAMP')
      ->fields('user', array('USERNAME'))
      ->condition('section.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetch();

    return $this->render('KulaHEdSchedulingBundle:SISSection:grades.html.twig', array('students' => $students, 'section_info' => $submitted_grades_info));
  }
  
  public function addAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Section', 'Y');
    $this->formAction('sis_offering_sections_create');
    return $this->render('KulaHEdSchedulingBundle:SISSection:add.html.twig');
  }
  
  public function createAction() {
    $this->authorize();
    
    $add = $this->request->request->get('add');
    
    foreach($add as $table => $add_row) {
      foreach($add_row as $key => $row) {
        
        // Get Course
        $course_info = $this->db()->db_select('STUD_COURSE', 'course')
          ->fields('course', array('MARK_SCALE_ID', 'COURSE_NUMBER'))
          ->condition('course.COURSE_ID', $row['COURSE_ID'])
          ->execute()->fetch();
        
        // Get last section number
        $section_number = $this->db()->db_select('STUD_SECTION', 'section')
          ->fields('section', array('SECTION_NUMBER'))
          ->condition('section.COURSE_ID', $row['COURSE_ID'])
          ->condition('section.ORGANIZATION_TERM_ID', $row['hidden']['ORGANIZATION_TERM_ID'])
          ->orderBy('SECTION_NUMBER', 'DESC', 'section')
          ->execute()->fetch();
        if ($section_number['SECTION_NUMBER']) {
          // Split section
          $split_section = explode('-', $section_number['SECTION_NUMBER']);
          $new_number = str_pad($split_section[1] + 1, 2, '0', STR_PAD_LEFT);
          $add[$table][$key]['SECTION_NUMBER'] = $course_info['COURSE_NUMBER'].'-'.$new_number;
        } else {
          $add[$table][$key]['SECTION_NUMBER'] = $course_info['COURSE_NUMBER'].'-01';
        }
        
        
        $add[$table][$key]['MARK_SCALE_ID'] = $course_info['MARK_SCALE_ID'];
      }
    }
    
    $this->poster = new \Kula\Component\Database\Poster($add);
    
    
    $id = $this->poster->getResultForTable('insert', 'STUD_SECTION')[0];
    return $this->forward('sis_HEd_offering_sections', array('record_type' => 'SIS.HEd.Section', 'record_id' => $id), array('record_type' => 'SIS.HEd.Section', 'record_id' => $id));
  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Section');
    
    $rows_affected = $this->db()->delete('STUD_SECTION')
        ->condition('SECTION_ID', $this->record->getSelectedRecordID())->execute();
    
    if ($rows_affected == 1) {
      $this->flash->add('success', 'Deleted section.');
    }
    
    return $this->forward('sis_HEd_offering_sections');
  }
  
  public function inactivateAction() {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Section');
    
    
    if ($this->record->getSelectedRecord()['STATUS'] == 'I') {
      $rows_affected = $this->db()->update('STUD_SECTION')
        ->fields(array('STATUS' => null))
          ->condition('SECTION_ID', $this->record->getSelectedRecordID())->execute();
      $success_message = 'Activated section.';
    } else {
      $rows_affected = $this->db()->update('STUD_SECTION')
        ->fields(array('STATUS' => 'I'))
          ->condition('SECTION_ID', $this->record->getSelectedRecordID())->execute();
      $success_message = 'Inactivated section.';
    }
    
    if ($rows_affected == 1) {
      $this->flash->add('success', $success_message);
      
      return $this->forward('sis_HEd_offering_sections', array('record_type' => 'SIS.HEd.Section', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'SIS.HEd.Section', 'record_id' => $this->record->getSelectedRecordID()));
    }
  }
  
  public function recalculate_section_totalsAction() {
    $this->authorize();
    
    $condition_or = $this->db()->db_or();
    $condition_or = $condition_or->condition('DROPPED', null)->condition('DROPPED', 'N');
    
    // Get Enrolled Totals
    $enrolled_totals = array();
    $enrolled_totals_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('SECTION_ID'))
      ->expressions(array('COUNT(*)' => 'enrolled_total'))
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'section.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->condition('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
      ->condition('orgterms.TERM_ID', $this->focus->getTermID())
      ->condition($condition_or)
      ->group_by('SECTION_ID', 'class')
      ->execute();
    while ($enrolled_totals_row = $enrolled_totals_result->fetch()) {
      $enrolled_totals[$enrolled_totals_row['SECTION_ID']] = $enrolled_totals_row['enrolled_total'];
    }
        
    // Get Wait list Totals
    $waitlist_totals = array();
    $waitlist_totals_result = $this->db()->db_select('STUD_STUDENT_WAIT_LIST', 'waitlist')
      ->fields('waitlist', array('SECTION_ID'))
      ->expressions(array('COUNT(*)' => 'waitlist_total'))
      ->join('STUD_SECTION', 'section', 'waitlist.SECTION_ID = section.SECTION_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'section.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->condition('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
      ->condition('orgterms.TERM_ID', $this->focus->getTermID())
      ->group_by('SECTION_ID', 'waitlist')
      ->execute();
    while ($waitlist_totals_row = $waitlist_totals_result->fetch()) {
      $waitlist_totals[$waitlist_totals_row['SECTION_ID']] = $waitlist_totals_row['waitlist_total'];
    }
    
    // Loop through each section
    $sections_result = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'section.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->condition('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
      ->condition('orgterms.TERM_ID', $this->focus->getTermID())
      ->execute();
    while ($sections_row = $sections_result->fetch()) {
      
      $section_data = array('STUD_SECTION' => array($sections_row['SECTION_ID'] => array(
        'ENROLLED_TOTAL' => $enrolled_totals[$sections_row['SECTION_ID']] > 0 ? $enrolled_totals[$sections_row['SECTION_ID']] : 0,
        'WAIT_LISTED_TOTAL' => $waitlist_totals[$sections_row['SECTION_ID']] > 0 ? $waitlist_totals[$sections_row['SECTION_ID']] : 0
      )));
      
      
      $poster_obj = new \Kula\Component\Database\Poster(null, $section_data);
      unset($poster_obj);
    }
    
    $this->flash->add('success', 'Recalculated section totals.');
    return $this->forward('sis_HEd_offering_sections');
    
  }
  
}