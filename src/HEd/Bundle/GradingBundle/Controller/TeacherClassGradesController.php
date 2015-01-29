<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class TeacherClassGradesController extends Controller {
  
  public function currentAction() {
    $this->authorize();
    $this->setRecordType('SECTION');
    
    if ($this->determineIfGradesOpen()) {
      
      // Finalize Grades
      
      // Add new grades
      if ($this->request->request->get('add')) {
        $course_history_service = new \Kula\Bundle\HEd\CourseHistoryBundle\CourseHistoryService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record);
        $new_grades = $this->request->request->get('add')['STUD_STUDENT_COURSE_HISTORY']['new'];
        foreach($new_grades as $student_class_id => $mark) {
          if (isset($mark['MARK']))
            $course_history_service->insertCourseHistoryForClass($student_class_id, $mark['MARK'], 'Y');
        }
      }
    
      // Edit grades
      $edit_request = $this->request->request->get('edit');
      if (isset($edit_request['STUD_STUDENT_COURSE_HISTORY'])) {
        $course_history_service = new \Kula\Bundle\HEd\CourseHistoryBundle\CourseHistoryService($this->db('write'), new \Kula\Component\Database\PosterFactory, $this->record);
        $edit_grades = $this->request->request->get('edit')['STUD_STUDENT_COURSE_HISTORY'];
        foreach($edit_grades as $student_course_history_id => $mark) {
          if (isset($mark['MARK']) AND $mark['MARK'] != '')
            $course_history_service->updateCourseHistoryForClass($student_course_history_id, $mark['MARK'], isset($mark['COMMENTS']) ? $mark['COMMENTS'] : null, 'Y');
          else
            $course_history_service->deleteCourseHistoryForClass($student_course_history_id);
        }
      }
    
    }
    
    $dropped_conditions = new \Kula\Component\Database\Query\Predicate('OR');
    $dropped_conditions = $dropped_conditions->predicate('class.DROPPED', null);
    $dropped_conditions = $dropped_conditions->predicate('class.DROPPED', 'N');
    
    $students = array();
    
    $students = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'MARK_SCALE_ID'))
      ->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID', 'LEVEL', 'GRADE', 'ENTER_CODE'), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
      ->left_join('STUD_STUDENT_COURSE_HISTORY', 'stucoursehistory', array('COURSE_HISTORY_ID', 'MARK', 'COMMENTS', 'TEACHER_SET'), 'stucoursehistory.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->left_join('STUD_MARK_SCALE_MARKS', 'scalemarks', array('ALLOW_COMMENTS', 'REQUIRE_COMMENTS'), 'scalemarks.MARK = stucoursehistory.MARK AND class.MARK_SCALE_ID = scalemarks.MARK_SCALE_ID')
      ->predicate('class.SECTION_ID', $this->record->getSelectedRecordID())
      ->predicate($dropped_conditions)
      ->order_by('DROPPED', 'ASC')
      ->order_by('LAST_NAME', 'ASC')
      ->order_by('FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    $cleared_for_submission = true;
    if ($students) {
      
      foreach($students as $id => $row) {
        
        if ($row['MARK'] == '' OR ($row['REQUIRE_COMMENTS'] == 'Y' AND $row['COMMENTS'] == '')) {
          $cleared_for_submission = false;
        }
        
      }
      
    }
    
    // Get submitted grades info
    $submitted_grades_info = $this->db()->select('STUD_SECTION', 'section')
      ->fields('section', array('TEACHER_GRADES_COMPLETED', 'TEACHER_GRADES_COMPLETED_TIMESTAMP'))
      ->left_join('CORE_USER', 'user', array('USERNAME'), 'user.USER_ID = section.TEACHER_GRADES_COMPLETED_USERSTAMP')
      ->predicate('section.SECTION_ID', $this->record->getSelectedRecordID())
      ->execute()->fetch();

    if ($cleared_for_submission AND ($submitted_grades_info['TEACHER_GRADES_COMPLETED'] == 'N' OR $submitted_grades_info['TEACHER_GRADES_COMPLETED'] == '') AND $this->request->request->get('submit_grades') == 'Y') {
      $poster = new \Kula\Component\Database\PosterFactory;
      $info = array('STUD_SECTION' => array($this->record->getSelectedRecordID() => array(
        'TEACHER_GRADES_COMPLETED' => array('checkbox_hidden' => 'N', 'checkbox' => 'Y'),
        'TEACHER_GRADES_COMPLETED_USERSTAMP' => $this->session->get('user_id'),
        'TEACHER_GRADES_COMPLETED_TIMESTAMP' => date('Y-m-d H:i:s')
      )));

      $poster->newPoster(null, $info);
      
      $submitted_grades_info = $this->db()->select('STUD_SECTION', 'section')
        ->fields('section', array('TEACHER_GRADES_COMPLETED', 'TEACHER_GRADES_COMPLETED_TIMESTAMP'))
        ->left_join('CORE_USER', 'user', array('USERNAME'), 'user.USER_ID = section.TEACHER_GRADES_COMPLETED_USERSTAMP')
        ->predicate('section.SECTION_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
      
    }
    
    return $this->render('KulaHEdCourseHistoryBundle:TeacherClassGrades:index.html.twig', array('students' => $students, 'gradesopen' => $this->determineIfGradesOpen(), 'cleared_for_submission' => $cleared_for_submission, 'section_info' => $submitted_grades_info));
  }
  
  public function droppedAction() {
    $this->authorize();
    $this->setRecordType('SECTION');
    
    $students = array();
    
    $students = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE', 'DROPPED', 'MARK_SCALE_ID'))
      ->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_STATUS_ID', 'LEVEL', 'GRADE', 'ENTER_CODE'), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->join('STUD_STUDENT', 'student', null, 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'constituent', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'student.STUDENT_ID = constituent.CONSTITUENT_ID')
      ->left_join('STUD_STUDENT_COURSE_HISTORY', 'stucoursehistory', array('COURSE_HISTORY_ID', 'MARK', 'MARK_SCALE_ID'), 'stucoursehistory.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->predicate('class.SECTION_ID', $this->record->getSelectedRecordID())
      ->predicate('class.DROPPED', 'Y')
      ->order_by('DROPPED', 'ASC')
      ->order_by('LAST_NAME', 'ASC')
      ->order_by('FIRST_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdCourseHistoryBundle:TeacherClassGrades:dropped.html.twig', array('students' => $students, 'gradesopen' => $this->determineIfGradesOpen()));
  }
  
  private function determineIfGradesOpen() {
    
    $grades_open = false;
    
    $school_grading_info = $this->db()->select('STUD_SCHOOL_TERM', 'schoolterm')
      ->fields('schoolterm', array('TEACHER_GRADES_OPEN', 'TEACHER_GRADES_CLOSE'))
      ->predicate('SCHOOL_TERM_ID', $this->get('kula.focus')->getOrganizationTermIDs())
      ->execute()->fetch();    
    
    $teacher_grading_info = $this->db()->select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
      ->fields('stafforgterm', array('TEACHER_GRADES_OPEN', 'TEACHER_GRADES_CLOSE'))
      ->predicate('STAFF_ORGANIZATION_TERM_ID', $this->get('kula.focus')->getTeacherOrganizationTermID())
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