<?php

namespace Kula\K12\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreSectionController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Section');
    
    $section = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID','CREDITS', 'START_DATE', 'END_DATE', 'CAPACITY', 'MINIMUM', 'ENROLLED_TOTAL', 'WAIT_LISTED_TOTAL', 'MARK_SCALE_ID', 'NO_CLASS_DATES', 'SUPPLIES_REQUIRED', 'SUPPLIES_OPTIONAL', 'SUPPLIES_PRICE'))
      ->condition('section.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetch();
    
    $meeting_times = $this->db()->db_select('STUD_SECTION_MEETINGS')
      ->fields('STUD_SECTION_MEETINGS')
      ->condition('SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaK12SchedulingBundle:CoreSection:index.html.twig', array('section' => $section, 'meeting_times' => $meeting_times));
  }
  
  public function coursesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Section');
    
    $courses = $this->db()->db_select('STUD_SECTION_COURSES', 'courses')
      ->fields('courses', array('SECTION_COURSE_ID', 'COURSE_ID'))
      ->condition('courses.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaK12SchedulingBundle:CoreSection:courses.html.twig', array('courses' => $courses));
  }
  
  public function staffAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.K12.Section');
    
    $staff = $this->db()->db_select('STUD_SECTION_STAFF', 'staff')
      ->fields('staff', array('SECTION_STAFF_ID', 'SECTION_ID', 'STAFF_ORGANIZATION_TERM_ID'))
      ->condition('staff.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaK12SchedulingBundle:CoreSection:staff.html.twig', array('staff' => $staff));
  }
  
  public function rosterAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Section');
    
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
      
      $schedule_service = $this->get('kula.K12.scheduling.schedule');
      
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
    
    return $this->render('KulaK12SchedulingBundle:CoreSection:roster.html.twig', array('students' => $students, 'drop_date' => $drop_date));
  }
  
  public function waitlistAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Section');
    
    $students = array();
    
    if ($this->request->request->get('delete')) {
      
      $schedule_service = $this->get('kula.K12.scheduling.schedule');
      
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
    
    return $this->render('KulaK12SchedulingBundle:CoreSection:waitlist.html.twig', array('students' => $students));
  }
  
  public function gradesAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Section');
    
    // Add new grades
    if ($this->request->request->get('add')) {
      $course_history_service = $this->get('kula.K12.grading.coursehistory');
      $new_grades = $this->request->request->get('add')['K12.Student.CourseHistory']['new'];
      foreach($new_grades as $student_class_id => $mark) {
        if (isset($mark['K12.Student.CourseHistory.Mark']))
          $course_history_service->insertCourseHistoryForClass($student_class_id, $mark['K12.Student.CourseHistory.Mark']);
      }
    }
    
    // Edit grades
    $edit = $this->request->request->get('edit');
    if (isset($edit['K12.Student.CourseHistory'])) {
      $course_history_service = $this->get('kula.K12.grading.coursehistory');
      $edit_grades = $this->request->request->get('edit')['K12.Student.CourseHistory'];
      foreach($edit_grades as $student_course_history_id => $mark) {
        if (isset($mark['K12.Student.CourseHistory.Mark']) AND $mark['K12.Student.CourseHistory.Mark'] != '')
          $course_history_service->updateCourseHistoryForClass($student_course_history_id, $mark['K12.Student.CourseHistory.Mark'], isset($mark['K12.Student.CourseHistory.Comments']) ? $mark['K12.Student.CourseHistory.Comments'] : null);
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
    
    if (isset($edit['K12.Section'])) {
      
      foreach ($edit['K12.Section'] as $section_id => $section_row) {
        
        if (isset($section_row['K12.Section.TeacherGradesCompleted']['checkbox']) AND $section_row['K12.Section.TeacherGradesCompleted']['checkbox'] == 'Y' AND $section_row['K12.Section.TeacherGradesCompleted']['checkbox_hidden'] != 'Y') {
          // Set as finalized
          $sectionInfo['K12.Section.TeacherGradesCompleted'] = 1;
          $sectionInfo['K12.Section.TeacherGradesCompletedUserstamp'] = $this->session->get('user_id');
          $sectionInfo['K12.Section.TeacherGradesCompletedTimestamp'] = date('Y-m-d H:i:s');
          
          $this->newPoster()->edit('K12.Section', $section_id, $sectionInfo)->process()->getResult();
          unset($sectionInfo);
        }
        
        if (!isset($section_row['K12.Section.TeacherGradesCompleted']['checkbox']) AND $section_row['K12.Section.TeacherGradesCompleted']['checkbox_hidden'] == 'Y') {
          // Unset as finalized
          $sectionInfo['K12.Section.TeacherGradesCompleted'] = 0;
          $sectionInfo['K12.Section.TeacherGradesCompletedUserstamp'] = null;
          $sectionInfo['K12.Section.TeacherGradesCompletedTimestamp'] = null;
          
          $this->newPoster()->edit('K12.Section', $section_id, $sectionInfo)->process()->getResult();
          unset($sectionInfo);
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

    return $this->render('KulaK12SchedulingBundle:CoreSection:grades.html.twig', array('students' => $students, 'section_info' => $submitted_grades_info));
  }
  
  public function addAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Section', 'Y');
    $this->formAction('Core_K12_Scheduling_Section_Create');
    return $this->render('KulaK12SchedulingBundle:CoreSection:add.html.twig');
  }
  
  public function createAction() {
    $this->authorize();
    
    if ($sectionInfo = $this->form('add', 'K12.Section', 0)) {
      
      $transaction = $this->db()->db_transaction();
      
      // Get Course
      $course_info = $this->db()->db_select('STUD_COURSE', 'course')
        ->fields('course', array('MARK_SCALE_ID', 'COURSE_NUMBER'))
        ->condition('course.COURSE_ID', $sectionInfo['K12.Section.CourseID'])
        ->execute()->fetch();
      
      // Get last section number
      $section_number = $this->db()->db_select('STUD_SECTION', 'section')
        ->fields('section', array('SECTION_NUMBER'))
        ->condition('section.COURSE_ID', $sectionInfo['K12.Section.CourseID'])
        ->condition('section.ORGANIZATION_TERM_ID', $sectionInfo['K12.Section.OrganizationTermID'])
        ->orderBy('SECTION_NUMBER', 'DESC', 'section')
        ->execute()->fetch();
      if ($section_number['SECTION_NUMBER']) {
        // Split section
        $split_section = explode('-', $section_number['SECTION_NUMBER']);
        $new_number = str_pad($split_section[1] + 1, 2, '0', STR_PAD_LEFT);
        $sectionInfo['K12.Section.SectionNumber'] = $course_info['COURSE_NUMBER'].'-'.$new_number;
      } else {
        $sectionInfo['K12.Section.SectionNumber'] = $course_info['COURSE_NUMBER'].'-01';
      }
      
      $sectionID = $this->newPoster()->add('K12.Section', 0, $sectionInfo)->process()->getResult();
      
      if ($sectionID) {
        $transaction->commit();
        $this->addFlash('success', 'Created section.');
        return $this->forward('Core_K12_Scheduling_Section', array('record_type' => 'Core.K12.Section', 'record_id' => $sectionID), array('record_type' => 'Core.K12.Section', 'record_id' => $sectionID));
      } else {
        $transaction->rollback();
      }
    } 
  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Section');
    
    $deletedSection = $this->newPoster()->delete('K12.Section', $this->record->getSelectedRecordID())->process()->getResult();
    
    if ($deletedSection == 1) {
      $this->addFlash('success', 'Deleted section.');
    }
    
    return $this->forward('Core_K12_Scheduling_Section');
  }
  
  public function inactivateAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Section');
    
    
    if ($this->record->getSelectedRecord()['STATUS'] == 'I') {
      $rows_affected = $this->newPoster()->edit('K12.Section', $this->record->getSelectedRecordID(), array('K12.Section.Status' => null))->process()->getResult();
      $success_message = 'Activated section.';
    } else {
      $rows_affected = $this->newPoster()->edit('K12.Section', $this->record->getSelectedRecordID(), array('K12.Section.Status' => 'I'))->process()->getResult();
      $success_message = 'Inactivated section.';
    }
    
    if ($rows_affected == 1) {
      $this->addFlash('success', $success_message);
      
      return $this->forward('Core_K12_Scheduling_Section', array('record_type' => 'Core.K12.Section', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.K12.Section', 'record_id' => $this->record->getSelectedRecordID()));
    }
  }
  
  public function recalculate_section_totalsAction() {
    $this->authorize();
    
    // Get Enrolled Totals
    $enrolled_totals = array();
    $enrolled_totals_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('SECTION_ID'))
      ->expression('COUNT(*)', 'enrolled_total')
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'section.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->condition('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
      ->condition('orgterms.TERM_ID', $this->focus->getTermID())
      ->condition($this->db()->db_or()->condition('DROPPED', null)->condition('DROPPED', 0))
      ->groupBy('SECTION_ID', 'class')
      ->execute();
    while ($enrolled_totals_row = $enrolled_totals_result->fetch()) {
      $enrolled_totals[$enrolled_totals_row['SECTION_ID']] = $enrolled_totals_row['enrolled_total'];
    }
        
    // Get Wait list Totals
    $waitlist_totals = array();
    $waitlist_totals_result = $this->db()->db_select('STUD_STUDENT_WAIT_LIST', 'waitlist')
      ->fields('waitlist', array('SECTION_ID'))
      ->expression('COUNT(*)', 'waitlist_total')
      ->join('STUD_SECTION', 'section', 'waitlist.SECTION_ID = section.SECTION_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'section.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->condition('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
      ->condition('orgterms.TERM_ID', $this->focus->getTermID())
      ->groupBy('SECTION_ID', 'waitlist')
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

      $this->newPoster()->edit('K12.Section', $sections_row['SECTION_ID'], array(
        'K12.Section.EnrolledTotal' => (isset($enrolled_totals[$sections_row['SECTION_ID']]) AND $enrolled_totals[$sections_row['SECTION_ID']] > 0) ? $enrolled_totals[$sections_row['SECTION_ID']] : 0,
        'K12.Section.WaitListedTotal' => (isset($waitlist_totals[$sections_row['SECTION_ID']]) AND $waitlist_totals[$sections_row['SECTION_ID']] > 0) ? $waitlist_totals[$sections_row['SECTION_ID']] : 0
      ))->process();
      
    }
    
    $this->addFlash('success', 'Recalculated section totals.');
    return $this->forward('Core_K12_Scheduling_Section');
    
  }
  
  public function contactInfoAction() {
    $this->authorize();
    $this->setRecordType('Core.K12.Section');
    
    $students = array();
    $email_addresses = array();
    
    $students = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER' => 'stucon_PERMAMENT_NUMBER', 'LAST_NAME' => 'stucon_LAST_NAME', 'FIRST_NAME' => 'stucon_FIRST_NAME', 'MIDDLE_NAME' => 'stucon_MIDDLE_NAME', 'GENDER' => 'stucon_GENDER'))
      ->leftJoin('CONS_EMAIL_ADDRESS', 'stuemail', 'stuemail.CONSTITUENT_ID = stucon.CONSTITUENT_ID AND stuemail.UNDELIVERABLE = 0 AND stuemail.ACTIVE = 1')
      ->fields('stuemail', array('EMAIL_ADDRESS' => 'stu_EMAIL_ADDRESS'))
      ->leftJoin('CONS_PHONE', 'stuphone', 'stuphone.CONSTITUENT_ID = stucon.CONSTITUENT_ID AND stuphone.ACTIVE = 1')
      ->fields('stuphone', array('PHONE_NUMBER' => 'stu_PHONE_NUMBER', 'PHONE_TYPE' => 'stu_PHONE_TYPE'))
      ->leftJoin('CONS_RELATIONSHIP', 'relation', 'relation.CONSTITUENT_ID = stucon.CONSTITUENT_ID')
      ->leftJoin('CONS_CONSTITUENT', 'parcon', 'parcon.CONSTITUENT_ID = relation.RELATED_CONSTITUENT_ID')
      ->fields('parcon', array('LAST_NAME' => 'parcon_LAST_NAME', 'FIRST_NAME' => 'parcon_FIRST_NAME'))
      ->leftJoin('CONS_PHONE', 'primary_phone', 'primary_phone.PHONE_NUMBER_ID = parcon.PRIMARY_PHONE_ID')
      ->fields('primary_phone', array('PHONE_NUMBER', 'PHONE_TYPE'))
      ->leftJoin('CONS_EMAIL_ADDRESS', 'primary_email', 'primary_email.EMAIL_ADDRESS_ID = parcon.PRIMARY_EMAIL_ID')
      ->fields('primary_email', array('EMAIL_ADDRESS', 'EMAIL_ADDRESS_TYPE'))
      ->condition('class.DROPPED', 0)
      ->condition('class.SECTION_ID', $this->record->getSelectedRecordID())
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    if ($students) {
      foreach($students as $student) {
        if (array_search($student['stu_EMAIL_ADDRESS'], $email_addresses) === false) {
          if ($student['stu_EMAIL_ADDRESS'] != '')
            $email_addresses[] = $student['stu_EMAIL_ADDRESS'];
        }
        if (array_search($student['EMAIL_ADDRESS'], $email_addresses) === false) {
          if ($student['EMAIL_ADDRESS'] != '')
            $email_addresses[] = $student['EMAIL_ADDRESS'];
        }
      }
    }
    
    return $this->render('KulaK12SchedulingBundle:CoreSection:contact_info.html.twig', array('students' => $students, 'email_addresses' => $email_addresses));
    
  }
  
}