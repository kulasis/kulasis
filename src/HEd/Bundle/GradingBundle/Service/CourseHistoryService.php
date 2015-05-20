<?php

namespace Kula\HEd\Bundle\GradingBundle\Service;

class CourseHistoryService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;

  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory,
                              $record = null) {
    $this->database = $db;
    $this->record = $record;
    $this->posterFactory = $poster_factory;
  
  }
  
  // insert course history
  public function insertCourseHistoryForClass($student_class_id, $mark, $teacher_set = '0') {
    
    if ($mark) {
    
    // Get course history data
    $course_info = $this->database->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('START_DATE', 'STUDENT_CLASS_ID', 'LEVEL', 'MARK_SCALE_ID'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = class.SECTION_ID')
      ->fields('section', array('END_DATE', 'CREDITS'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->fields('orgterms', array('ORGANIZATION_ID'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_NAME', 'END_DATE' => 'term_END_DATE'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_ID'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', 'stafforgterms.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
      ->fields('stafforgterms', array('STAFF_ID'))
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterms.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->condition('class.STUDENT_CLASS_ID', $student_class_id)
      ->execute()->fetch();
    
    $course_history_data['HEd.Student.CourseHistory.StudentID'] = $course_info['STUDENT_ID'];
    $course_history_data['HEd.Student.CourseHistory.CourseID'] = $course_info['COURSE_ID'];
    $course_history_data['HEd.Student.CourseHistory.OrganizationID'] = $course_info['ORGANIZATION_ID'];
    $course_history_data['HEd.Student.CourseHistory.CalendarYear'] = date('Y', strtotime($course_info['term_END_DATE']));
    $course_history_data['HEd.Student.CourseHistory.CalendarMonth'] = date('n', strtotime($course_info['term_END_DATE']));
    $course_history_data['HEd.Student.CourseHistory.Term'] = $course_info['TERM_NAME'];
    $course_history_data['HEd.Student.CourseHistory.StartDate'] = $course_info['START_DATE'];
    $course_history_data['HEd.Student.CourseHistory.CompletedDate'] = $course_info['END_DATE'];
    $course_history_data['HEd.Student.CourseHistory.CourseNumber'] = $course_info['COURSE_NUMBER'];
    $course_history_data['HEd.Student.CourseHistory.CourseTitle'] = $course_info['COURSE_TITLE'];
    $course_history_data['HEd.Student.CourseHistory.StudentClassID'] = $course_info['STUDENT_CLASS_ID'];
    $course_history_data['HEd.Student.CourseHistory.Level'] = $course_info['LEVEL'];
    $course_history_data['HEd.Student.CourseHistory.StaffID'] = $course_info['STAFF_ID'];
    $course_history_data['HEd.Student.CourseHistory.Staff'] = $course_info['ABBREVIATED_NAME'];
    $course_history_data['HEd.Student.CourseHistory.MarkScaleID'] = $course_info['MARK_SCALE_ID'];
    $course_history_data['HEd.Student.CourseHistory.CreditsAttempted'] = $course_info['CREDITS'];
    $course_history_data['HEd.Student.CourseHistory.Mark'] = $mark;
    $course_history_data['HEd.Student.CourseHistory.TeacherSet'] = $teacher_set;
    
    // Get award data
    $award_data = $this->determineAward($course_info['MARK_SCALE_ID'], $mark, $course_info['CREDITS']);
    $course_history_data['HEd.Student.CourseHistory.CreditsEarned'] = $award_data['HEd.Student.CourseHistory.CreditsEarned'];
    $course_history_data['HEd.Student.CourseHistory.GPAValue'] = $award_data['HEd.Student.CourseHistory.GPAValue'];
    $course_history_data['HEd.Student.CourseHistory.QualityPoints'] = $award_data['HEd.Student.CourseHistory.QualityPoints'];
    
    return $this->posterFactory->newPoster()->add('HEd.Student.CourseHistory', 'new', $course_history_data)->process()->getResult();
    
    }
  }
  
  // update course history
  public function updateCourseHistoryForClass($course_history_id, $mark, $comments = null, $teacher_set = 0) {
    
    // Get mark scale id
    $course_info = $this->database->db_select('STUD_STUDENT_COURSE_HISTORY')
      ->fields('STUD_STUDENT_COURSE_HISTORY', array('MARK_SCALE_ID', 'CREDITS_ATTEMPTED', 'TEACHER_SET'))
      ->condition('COURSE_HISTORY_ID', $course_history_id)
      ->execute()->fetch();
    
    // Get award data
    $award_data = $this->determineAward($course_info['MARK_SCALE_ID'], $mark, $course_info['CREDITS_ATTEMPTED']);
    $course_history_data['HEd.Student.CourseHistory.Mark'] = $mark;
    
    if ((isset($award_data['COMMENTS']) AND $award_data['COMMENTS'] == 'Y' AND $teacher_set == 1) OR $teacher_set == 0) {
      $course_history_data['HEd.Student.CourseHistory.Comments'] = $comments;
    } else {
      $course_history_data['HEd.Student.CourseHistory.Comments'] = null;
    }
    
    $course_history_data['HEd.Student.CourseHistory.CreditsEarned'] = $award_data['HEd.Student.CourseHistory.CreditsEarned'];
    $course_history_data['HEd.Student.CourseHistory.GPAValue'] = $award_data['HEd.Student.CourseHistory.GPAValue'];
    $course_history_data['HEd.Student.CourseHistory.QualityPoints'] = $award_data['HEd.Student.CourseHistory.QualityPoints'];
    $course_history_data['HEd.Student.CourseHistory.TeacherSet'] = $teacher_set;
    
    return $this->posterFactory->newPoster()->edit('HEd.Student.CourseHistory', $course_history_id, $course_history_data)->process()->getResult();
  }
  
  public function deleteCourseHistoryForClass($course_history_id) {
    return $this->posterFactory->newPoster()->delete('HEd.Student.CourseHistory', $course_history_id)->process()->getResult();
  }
  
  public function insertCourseHistoryForCH($data) {
    return $this->posterFactory->newPoster()->add('HEd.Student.CourseHistory', 'new', $data)->process()->getResult();
  }
  
  public function updateCourseHistoryForCH($id, $data) {
    
    if (isset($data['HEd.Student.CourseHistory.MarkScaleID']))
      $mark_scale_id = $data['HEd.Student.CourseHistory.MarkScaleID'];
    else {
    $current_info = $this->database->db_select('STUD_STUDENT_COURSE_HISTORY')
      ->fields('STUD_STUDENT_COURSE_HISTORY', array('MARK_SCALE_ID'))
      ->condition('COURSE_HISTORY_ID', $id)
      ->execute()->fetch();
    $mark_scale_id = $current_info['MARK_SCALE_ID'];
    }
    
    $data += $this->determineAward($mark_scale_id, $data['HEd.Student.CourseHistory.Mark'], $data['HEd.Student.CourseHistory.CreditsAttempted']);
    unset($data['COMMENTS']);
    return $this->posterFactory->newPoster()->edit('HEd.Student.CourseHistory', $id, $data)->process()->getResult();
  }
  
  private function determineAward($mark_scale_id, $mark, $credits_attempted) {
    
    // Get GPA Value
    $mark_info = $this->database->db_select('STUD_MARK_SCALE_MARKS')
      ->fields('STUD_MARK_SCALE_MARKS', array('MARK', 'GETS_CREDIT', 'GPA_VALUE', 'ALLOW_COMMENTS', 'REQUIRE_COMMENTS'))
      ->condition('MARK_SCALE_ID', $mark_scale_id)
      ->condition('MARK', $mark)
      ->execute()->fetch();
    
    if ($mark_info['MARK'] == '')
      $award_data['HEd.Student.CourseHistory.Mark'] = null;
    
    // Determine credit
    if ($mark_info['GETS_CREDIT'] == 1)
      $award_data['HEd.Student.CourseHistory.CreditsEarned'] = $credits_attempted;
    else
      $award_data['HEd.Student.CourseHistory.CreditsEarned'] = 0.0;
    
    $award_data['HEd.Student.CourseHistory.GPAValue'] = $mark_info['GPA_VALUE'];
    $award_data['HEd.Student.CourseHistory.QualityPoints'] = $mark_info['GPA_VALUE'] * $award_data['HEd.Student.CourseHistory.CreditsEarned'];
    
    if ($mark_info['ALLOW_COMMENTS'] == 1 OR $mark_info['REQUIRE_COMMENTS'] == 1) {
      $award_data['COMMENTS'] = 'Y';
    }
    
    return $award_data;
  }
  
}