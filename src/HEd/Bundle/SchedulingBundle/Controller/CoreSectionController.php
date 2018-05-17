<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreSectionController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Section');
    
    $section = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID','CREDITS', 'START_DATE', 'END_DATE', 'CAPACITY', 'MINIMUM', 'ENROLLED_TOTAL', 'PAID_TOTAL', 'WAIT_LISTED_TOTAL', 'MARK_SCALE_ID', 'NO_CLASS_DATES', 'SUPPLIES_REQUIRED', 'SUPPLIES_OPTIONAL', 'SUPPLIES_PRICE', 'OPEN_REGISTRATION', 'CLOSE_REGISTRATION', 'ALLOW_REGISTRATION', 'PARENT_ENROLL'))
      ->condition('section.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetch();
    
    $meeting_times = $this->db()->db_select('STUD_SECTION_MEETINGS')
      ->fields('STUD_SECTION_MEETINGS')
      ->condition('SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:CoreSection:index.html.twig', array('section' => $section, 'meeting_times' => $meeting_times));
  }
  
  public function coursesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Section');
    
    $courses = $this->db()->db_select('STUD_SECTION_COURSES', 'courses')
      ->fields('courses', array('SECTION_COURSE_ID', 'COURSE_ID'))
      ->condition('courses.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:CoreSection:courses.html.twig', array('courses' => $courses));
  }

  public function sectionsAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Section');
    
    $sections = $this->db()->db_select('STUD_SECTION_SECTIONS', 'sections')
      ->fields('sections', array('SECTION_SECTION_ID', 'RELATED_SECTION_ID', 'OPTIONAL'))
      ->condition('sections.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:CoreSection:sections.html.twig', array('sections' => $sections));
  }
  
  public function staffAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.HEd.Section');
    
    $staff = $this->db()->db_select('STUD_SECTION_STAFF', 'staff')
      ->fields('staff', array('SECTION_STAFF_ID', 'SECTION_ID', 'STAFF_ORGANIZATION_TERM_ID'))
      ->condition('staff.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdSchedulingBundle:CoreSection:staff.html.twig', array('staff' => $staff));
  }
  
  public function rosterAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Section');
    
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
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'LEVEL', 'PAID'))
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
    
    return $this->render('KulaHEdSchedulingBundle:CoreSection:roster.html.twig', array('students' => $students, 'drop_date' => $drop_date));
  }
  
  public function waitlistAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Section');
    
    $students = array();
    
    if ($this->request->request->get('delete')) {
      
      $schedule_service = $this->get('kula.HEd.scheduling.schedule');
      
      $classes_to_delete = $this->request->request->get('delete')['HEd.Student.WaitList'];
      
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
    
    return $this->render('KulaHEdSchedulingBundle:CoreSection:waitlist.html.twig', array('students' => $students));
  }
  
  public function gradesAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Section');
    
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
    $edit = $this->request->request->get('edit');
    if (isset($edit['HEd.Student.CourseHistory'])) {
      $course_history_service = $this->get('kula.HEd.grading.coursehistory');
      $edit_grades = $this->request->request->get('edit')['HEd.Student.CourseHistory'];
      foreach($edit_grades as $student_course_history_id => $mark) {
        if (isset($mark['HEd.Student.CourseHistory.Mark']) AND $mark['HEd.Student.CourseHistory.Mark'] != '')
          $course_history_service->updateCourseHistoryForClass($student_course_history_id, $mark['HEd.Student.CourseHistory.Mark'], isset($mark['HEd.Student.CourseHistory.Comments']) ? $mark['HEd.Student.CourseHistory.Comments'] : null);
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
    
    if (isset($edit['HEd.Section'])) {
      
      foreach ($edit['HEd.Section'] as $section_id => $section_row) {
        
        if (isset($section_row['HEd.Section.TeacherGradesCompleted']['checkbox']) AND $section_row['HEd.Section.TeacherGradesCompleted']['checkbox'] == '1' AND $section_row['HEd.Section.TeacherGradesCompleted']['checkbox_hidden'] != '1') {
          
          // Set as finalized
          $sectionInfo['HEd.Section.TeacherGradesCompleted'] = 1;
          $sectionInfo['HEd.Section.TeacherGradesCompletedUserstamp'] = $this->session->get('user_id');
          $sectionInfo['HEd.Section.TeacherGradesCompletedTimestamp'] = date('Y-m-d H:i:s');
          
          $this->newPoster()->edit('HEd.Section', $section_id, $sectionInfo)->process()->getResult();
          unset($sectionInfo);
        }
        
        if (!isset($section_row['HEd.Section.TeacherGradesCompleted']['checkbox']) AND $section_row['HEd.Section.TeacherGradesCompleted']['checkbox_hidden'] == '1') {
          // Unset as finalized
          $sectionInfo['HEd.Section.TeacherGradesCompleted'] = 0;
          $sectionInfo['HEd.Section.TeacherGradesCompletedUserstamp'] = null;
          $sectionInfo['HEd.Section.TeacherGradesCompletedTimestamp'] = null;
          
          $this->newPoster()->edit('HEd.Section', $section_id, $sectionInfo)->process()->getResult();
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

    return $this->render('KulaHEdSchedulingBundle:CoreSection:grades.html.twig', array('students' => $students, 'section_info' => $submitted_grades_info));
  }
  
  public function addAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Section', 'Y');
    $this->formAction('Core_HEd_Scheduling_Section_Create');
    return $this->render('KulaHEdSchedulingBundle:CoreSection:add.html.twig');
  }
  
  public function createAction() {
    $this->authorize();
    
    if ($sectionInfo = $this->form('add', 'HEd.Section', 0)) {
      
      $transaction = $this->db()->db_transaction();
      
      // Get Course
      $course_info = $this->db()->db_select('STUD_COURSE', 'course')
        ->fields('course', array('MARK_SCALE_ID', 'COURSE_NUMBER', 'CREDITS'))
        ->condition('course.COURSE_ID', $sectionInfo['HEd.Section.CourseID'])
        ->execute()->fetch();
		print_r($course_info);
	  // Get term start and end date
	  $term_dates = $this->db()->db_select('CORE_TERM', 'term')
		->fields('term', array('START_DATE', 'END_DATE'))
		->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.TERM_ID = term.TERM_ID')
		->condition('orgterms.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermID())
		->execute()->fetch();
	  $sectionInfo['HEd.Section.StartDate'] = $term_dates['START_DATE'];
	  $sectionInfo['HEd.Section.EndDate'] = $term_dates['END_DATE'];
      
      // Get last section number
      $section_number = $this->db()->db_select('STUD_SECTION', 'section')
        ->fields('section', array('SECTION_NUMBER'))
        ->condition('section.COURSE_ID', $sectionInfo['HEd.Section.CourseID'])
        ->condition('section.ORGANIZATION_TERM_ID', $sectionInfo['HEd.Section.OrganizationTermID'])
        ->orderBy('SECTION_NUMBER', 'DESC', 'section')
        ->execute()->fetch();
      if ($section_number['SECTION_NUMBER']) {
        // Split section
        $split_section = explode('-', $section_number['SECTION_NUMBER']);
        $token_to_consider = count($split_section) - 1;
        $new_number = str_pad($split_section[$token_to_consider] + 1, 2, '0', STR_PAD_LEFT);
        $sectionInfo['HEd.Section.SectionNumber'] = $course_info['COURSE_NUMBER'].'-'.$new_number;
      } else {
        $sectionInfo['HEd.Section.SectionNumber'] = $course_info['COURSE_NUMBER'].'-01';
      }
      $sectionInfo['HEd.Section.MarkScaleID'] = $course_info['MARK_SCALE_ID'];
      $sectionInfo['HEd.Section.Credits'] = $course_info['CREDITS'];
      
      $sectionID = $this->newPoster()->add('HEd.Section', 0, $sectionInfo)->process()->getResult();
      
      if ($sectionID) {
        $transaction->commit();
        $this->addFlash('success', 'Created section.');
        return $this->forward('Core_HEd_Scheduling_Section', array('record_type' => 'Core.HEd.Section', 'record_id' => $sectionID), array('record_type' => 'Core.HEd.Section', 'record_id' => $sectionID));
      } else {
        $transaction->rollback();
      }
    } 
  }
  
  public function deleteAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Section');
    
    $deletedSection = $this->newPoster()->delete('HEd.Section', $this->record->getSelectedRecordID())->process()->getResult();
    
    if ($deletedSection == 1) {
      $this->addFlash('success', 'Deleted section.');
    }
    
    return $this->forward('Core_HEd_Scheduling_Section');
  }
  
  public function inactivateAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Section');
    
    
    if ($this->record->getSelectedRecord()['STATUS'] == 'I') {
      $rows_affected = $this->newPoster()->edit('HEd.Section', $this->record->getSelectedRecordID(), array('HEd.Section.Status' => null))->process()->getResult();
      $success_message = 'Activated section.';
    } else {
      $rows_affected = $this->newPoster()->edit('HEd.Section', $this->record->getSelectedRecordID(), array('HEd.Section.Status' => 'I'))->process()->getResult();
      $success_message = 'Inactivated section.';
    }
    
    if ($rows_affected == 1) {
      $this->addFlash('success', $success_message);
      
      return $this->forward('Core_HEd_Scheduling_Section', array('record_type' => 'Core.HEd.Section', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'Core.HEd.Section', 'record_id' => $this->record->getSelectedRecordID()));
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

    // Get Paid Totals
    $paid_totals = array();
    $paid_totals_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('SECTION_ID'))
      ->expression('COUNT(*)', 'paid_total')
      ->join('STUD_SECTION', 'section', 'class.SECTION_ID = section.SECTION_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'section.ORGANIZATION_TERM_ID = orgterms.ORGANIZATION_TERM_ID')
      ->condition('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
      ->condition('orgterms.TERM_ID', $this->focus->getTermID())
      ->condition($this->db()->db_or()->condition('DROPPED', null)->condition('DROPPED', 0))
      ->condition('class.PAID', 1)
      ->groupBy('SECTION_ID', 'class')
      ->execute();
    while ($paid_totals_row = $paid_totals_result->fetch()) {
      $paid_totals[$paid_totals_row['SECTION_ID']] = $paid_totals_row['paid_total'];
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

      $this->newPoster()->edit('HEd.Section', $sections_row['SECTION_ID'], array(
        'HEd.Section.EnrolledTotal' => (isset($enrolled_totals[$sections_row['SECTION_ID']]) AND $enrolled_totals[$sections_row['SECTION_ID']] > 0) ? $enrolled_totals[$sections_row['SECTION_ID']] : 0,
        'HEd.Section.PaidTotal' => (isset($paid_totals[$sections_row['SECTION_ID']]) AND $paid_totals[$sections_row['SECTION_ID']] > 0) ? $paid_totals[$sections_row['SECTION_ID']] : 0,
        'HEd.Section.WaitListedTotal' => (isset($waitlist_totals[$sections_row['SECTION_ID']]) AND $waitlist_totals[$sections_row['SECTION_ID']] > 0) ? $waitlist_totals[$sections_row['SECTION_ID']] : 0
      ))->process();
      
    }
    
    $this->addFlash('success', 'Recalculated section totals.');
    return $this->forward('Core_HEd_Scheduling_Section');
    
  }
  
  public function contactInfoAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Section');
    
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
      }
    }
    
    return $this->render('KulaHEdSchedulingBundle:CoreSection:contact_info.html.twig', array('students' => $students, 'email_addresses' => $email_addresses));
    
  }

  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('HEd.Section')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}