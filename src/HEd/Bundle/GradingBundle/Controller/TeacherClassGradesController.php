<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class TeacherClassGradesController extends Controller {
  
  public function currentAction() {
    $this->authorize();
    $this->setRecordType('Teacher.HEd.Section');
    
    if ($this->determineIfGradesOpen()) {
      
      // Finalize Grades
      
      // Add new grades
      if ($this->request->request->get('add')) {
        $course_history_service = $this->get('kula.HEd.grading.coursehistory');
        $new_grades = $this->request->request->get('add')['HEd.Student.CourseHistory']['new'];
        foreach($new_grades as $student_class_id => $mark) {
          if (isset($mark['HEd.Student.CourseHistory.Mark']))
            $course_history_service->insertCourseHistoryForClass($student_class_id, $mark['HEd.Student.CourseHistory.Mark'], '1');
        }
      }
    
      // Edit grades
      $edit_request = $this->request->request->get('edit');
      if (isset($edit_request['HEd.Student.CourseHistory'])) {
        $course_history_service = $this->get('kula.HEd.grading.coursehistory');
        $edit_grades = $this->request->request->get('edit')['HEd.Student.CourseHistory'];
        foreach($edit_grades as $student_course_history_id => $mark) {
          if (isset($mark['HEd.Student.CourseHistory.Mark']) AND $mark['HEd.Student.CourseHistory.Mark'] != '')
            $course_history_service->updateCourseHistoryForClass($student_course_history_id, $mark['HEd.Student.CourseHistory.Mark'], isset($mark['HEd.Student.CourseHistory.Comments']) ? $mark['HEd.Student.CourseHistory.Comments'] : null, '1');
          else
            $course_history_service->deleteCourseHistoryForClass($student_course_history_id);
        }
      }
    
    }
    
    $students = array();
    
    $students = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED' => 'class_CREDITS_ATTEMPTED'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID', 'LEVEL', 'GRADE', 'ENTER_CODE'))
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = class.SECTION_ID')
      ->fields('sec', array('CREDITS'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'constituent', 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'stucoursehistory', 'stucoursehistory.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->fields('stucoursehistory', array('COURSE_HISTORY_ID', 'MARK', 'COMMENTS', 'TEACHER_SET', 'CREDITS_ATTEMPTED' => 'coursehistory_CREDITS_ATTEMPTED'))
      ->leftJoin('STUD_MARK_SCALE_MARKS', 'scalemarks', 'scalemarks.MARK = stucoursehistory.MARK AND class.MARK_SCALE_ID = scalemarks.MARK_SCALE_ID')
      ->fields('scalemarks', array('ALLOW_COMMENTS', 'REQUIRE_COMMENTS'))
      ->condition('class.SECTION_ID', $this->record->getSelectedRecordID())
      ->condition('class.DROPPED', 0)
      ->orderBy('DROPPED', 'ASC')
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    $cleared_for_submission = true;
    if ($students) {
      
      foreach($students as $id => $row) {
        
        if ($row['MARK'] == '' OR ($row['REQUIRE_COMMENTS'] == '1' AND $row['COMMENTS'] == '')) {
          $cleared_for_submission = false;
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

    if ($cleared_for_submission AND ($submitted_grades_info['TEACHER_GRADES_COMPLETED'] == '0' OR $submitted_grades_info['TEACHER_GRADES_COMPLETED'] == '') AND $this->request->request->get('submit_grades') == 'Y') {
      
      $poster = $this->newPoster()->edit('HEd.Section', $this->record->getSelectedRecordID(), array(
        'HEd.Section.TeacherGradesCompleted' => 1,
        'HEd.Section.TeacherGradesCompletedUserstamp' => $this->session->get('user_id'),
        'HEd.Section.TeacherGradesCompletedTimestamp' => date('Y-m-d H:i:s')
      ))->process();
      
      $submitted_grades_info = $this->db()->db_select('STUD_SECTION', 'section')
        ->fields('section', array('TEACHER_GRADES_COMPLETED', 'TEACHER_GRADES_COMPLETED_TIMESTAMP'))
        ->leftJoin('CORE_USER', 'user', 'user.USER_ID = section.TEACHER_GRADES_COMPLETED_USERSTAMP')
        ->fields('user', array('USERNAME'))
        ->condition('section.SECTION_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
      
    }
    
    return $this->render('KulaHEdGradingBundle:TeacherClassGrades:index.html.twig', array('students' => $students, 'gradesopen' => $this->determineIfGradesOpen(), 'cleared_for_submission' => $cleared_for_submission, 'section_info' => $submitted_grades_info));
  }
  
  public function droppedAction() {
    $this->authorize();
    $this->setRecordType('Teacher.HEd.Section');
    
    $students = array();
    
    $students = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID', 'LEVEL', 'GRADE', 'ENTER_CODE'))
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = class.SECTION_ID')
      ->fields('sec', array('CREDITS'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'constituent', 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
      ->fields('constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'stucoursehistory', 'stucoursehistory.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->fields('stucoursehistory', array('COURSE_HISTORY_ID', 'MARK', 'MARK_SCALE_ID', 'CREDITS_ATTEMPTED'))
      ->condition('class.SECTION_ID', $this->record->getSelectedRecordID())
      ->condition('class.DROPPED', '1')
      ->orderBy('DROPPED', 'ASC')
      ->orderBy('LAST_NAME', 'ASC')
      ->orderBy('FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:TeacherClassGrades:dropped.html.twig', array('students' => $students, 'gradesopen' => $this->determineIfGradesOpen()));
  }
  
  private function determineIfGradesOpen() {
    
    $grades_open = false;
    
    $school_grading_info = $this->db()->db_select('STUD_SCHOOL_TERM', 'schoolterm')
      ->fields('schoolterm', array('TEACHER_GRADES_OPEN', 'TEACHER_GRADES_CLOSE'))
      ->condition('SCHOOL_TERM_ID', $this->get('kula.core.focus')->getOrganizationTermIDs())
      ->execute()->fetch();    
    
    $teacher_grading_info = $this->db()->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
      ->fields('stafforgterm', array('TEACHER_GRADES_OPEN', 'TEACHER_GRADES_CLOSE'))
      ->condition('STAFF_ORGANIZATION_TERM_ID', $this->get('kula.core.focus')->getTeacherOrganizationTermID())
      ->execute()->fetch();

    $today = time();
    
    // check if grades are open based on teacher grades open and teacher grades close
    if ($today >= strtotime($school_grading_info['TEACHER_GRADES_OPEN']) AND $today <= strtotime($school_grading_info['TEACHER_GRADES_CLOSE'])) {
      $grades_open = true;
    }
    
    if ($teacher_grading_info['TEACHER_GRADES_OPEN'] AND $teacher_grading_info['TEACHER_GRADES_CLOSE'] AND 
    $today >= strtotime($teacher_grading_info['TEACHER_GRADES_OPEN']) AND $today <= strtotime($teacher_grading_info['TEACHER_GRADES_CLOSE'])) {
      $grades_open = true;
    }
    
    return $grades_open;
  }
}