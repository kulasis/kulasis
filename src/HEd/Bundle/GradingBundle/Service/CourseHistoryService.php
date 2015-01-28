<?php

namespace Kula\HEd\Bundle\GradingBundle\Service;

class CourseHistoryService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;

  public function __construct(\Kula\Component\Database\Connection $db, 
                            \Kula\Component\Database\PosterFactory $poster_factory,
                            \Kula\Component\Record\Record $record) {
    $this->database = $db;
    $this->record = $record;
    $this->poster_factory = $poster_factory;
  
  }
  
  // insert course history
  public function insertCourseHistoryForClass($student_class_id, $mark, $teacher_set = 'N') {
    
    if ($mark) {
    
    // Get course history data
    $course_info = $this->database->select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('START_DATE', 'STUDENT_CLASS_ID', 'LEVEL', 'MARK_SCALE_ID'))
      ->join('STUD_SECTION', 'section', array('END_DATE', 'CREDITS'), 'section.SECTION_ID = class.SECTION_ID')
      ->join('STUD_COURSE', 'course', array('COURSE_ID', 'COURSE_NUMBER', 'COURSE_TITLE'), 'course.COURSE_ID = section.COURSE_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', array('ORGANIZATION_ID'), 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', array('TERM_NAME', 'END_DATE' => 'term_END_DATE'), 'term.TERM_ID = orgterms.TERM_ID')
      ->join('STUD_STUDENT_STATUS', 'status', array('STUDENT_ID'), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->left_join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', array('STAFF_ID'), 'stafforgterms.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
      ->left_join('STUD_STAFF', 'staff', array('ABBREVIATED_NAME'), 'staff.STAFF_ID = stafforgterms.STAFF_ID')
      ->predicate('class.STUDENT_CLASS_ID', $student_class_id)
      ->execute()->fetch();
    
    $course_history_data['STUDENT_ID'] = $course_info['STUDENT_ID'];
    $course_history_data['COURSE_ID'] = $course_info['COURSE_ID'];
    $course_history_data['ORGANIZATION_ID'] = $course_info['ORGANIZATION_ID'];
    $course_history_data['CALENDAR_YEAR'] = date('Y', strtotime($course_info['term_END_DATE']));
    $course_history_data['CALENDAR_MONTH'] = date('n', strtotime($course_info['term_END_DATE']));
    $course_history_data['TERM'] = $course_info['TERM_NAME'];
    $course_history_data['START_DATE'] = $course_info['START_DATE'];
    $course_history_data['COMPLETED_DATE'] = $course_info['END_DATE'];
    $course_history_data['COURSE_NUMBER'] = $course_info['COURSE_NUMBER'];
    $course_history_data['COURSE_TITLE'] = $course_info['COURSE_TITLE'];
    $course_history_data['STUDENT_CLASS_ID'] = $course_info['STUDENT_CLASS_ID'];
    $course_history_data['LEVEL'] = $course_info['LEVEL'];
    $course_history_data['INSTRUCTOR_ID'] = $course_info['STAFF_ID'];
    $course_history_data['INSTRUCTOR'] = $course_info['ABBREVIATED_NAME'];
    $course_history_data['MARK_SCALE_ID'] = $course_info['MARK_SCALE_ID'];
    $course_history_data['CREDITS_ATTEMPTED'] = $course_info['CREDITS'];
    $course_history_data['MARK'] = $mark;
    $course_history_data['TEACHER_SET']['checkbox_hidden'] = 'N';
    $course_history_data['TEACHER_SET']['checkbox'] = $teacher_set;
    
    // Get award data
    $award_data = $this->_determineAward($course_info['MARK_SCALE_ID'], $mark, $course_info['CREDITS']);
    $course_history_data['CREDITS_EARNED'] = $award_data['CREDITS_EARNED'];
    $course_history_data['GPA_VALUE'] = $award_data['GPA_VALUE'];
    $course_history_data['QUALITY_POINTS'] = $award_data['QUALITY_POINTS'];
    $student_course_history_poster = $this->poster_factory->newPoster(array('STUD_STUDENT_COURSE_HISTORY' => array('new' => $course_history_data)));
    $student_course_history_id = $student_course_history_poster->getResultForTable('insert', 'STUD_STUDENT_COURSE_HISTORY')['new'];
    
    return $student_course_history_id;
    
    }
  }
  
  // update course history
  public function updateCourseHistoryForClass($course_history_id, $mark, $comments = null, $teacher_set = 'N') {
    
    // Get mark scale id
    $course_info = $this->database->select('STUD_STUDENT_COURSE_HISTORY')
      ->fields(null, array('MARK_SCALE_ID', 'CREDITS_ATTEMPTED', 'TEACHER_SET'))
      ->predicate('COURSE_HISTORY_ID', $course_history_id)
      ->execute()->fetch();
    
    // Get award data
    $award_data = $this->_determineAward($course_info['MARK_SCALE_ID'], $mark, $course_info['CREDITS_ATTEMPTED']);
    $course_history_data['MARK'] = $mark;
    
    if ((isset($award_data['COMMENTS']) AND $award_data['COMMENTS'] == 'Y' AND $teacher_set == 'Y') OR $teacher_set == 'N') {
      $course_history_data['COMMENTS'] = $comments;
    } else {
      $course_history_data['COMMENTS'] = null;
    }
    
    $course_history_data['CREDITS_EARNED'] = $award_data['CREDITS_EARNED'];
    $course_history_data['GPA_VALUE'] = $award_data['GPA_VALUE'];
    $course_history_data['QUALITY_POINTS'] = $award_data['QUALITY_POINTS'];
    $course_history_data['TEACHER_SET']['checkbox_hidden'] = $course_info['TEACHER_SET'];
    $course_history_data['TEACHER_SET']['checkbox'] = $teacher_set;
    $student_course_history_poster = $this->poster_factory->newPoster(null, array('STUD_STUDENT_COURSE_HISTORY' => array($course_history_id => $course_history_data)));
    $student_course_history_id = $student_course_history_poster->getResultForTable('update', 'STUD_STUDENT_COURSE_HISTORY')[$course_history_id];
    
    return $student_course_history_id;
  }
  
  public function deleteCourseHistoryForClass($course_history_id) {
    $student_course_history_poster = $this->poster_factory->newPoster(null, null, array('STUD_STUDENT_COURSE_HISTORY' => array($course_history_id => array('delete_row' => 'Y'))));
    $student_course_history_id = $student_course_history_poster->getResultForTable('delete', 'STUD_STUDENT_COURSE_HISTORY')[$course_history_id];
    return $student_course_history_id;
  }
  
  public function insertCourseHistoryForCH($data) {
    $student_course_history_poster = $this->poster_factory->newPoster(array('STUD_STUDENT_COURSE_HISTORY' => array('new' => $data)));
    $student_course_history_id = $student_course_history_poster->getResultForTable('insert', 'STUD_STUDENT_COURSE_HISTORY')['new'];
    return $student_course_history_id;
  }
  
  public function updateCourseHistoryForCH($id, $data) {
    
    if (isset($data['MARK_SCALE_ID']))
      $mark_scale_id = $data['MARK_SCALE_ID'];
    else {
    $current_info = $this->database->select('STUD_STUDENT_COURSE_HISTORY')
      ->fields(null, array('MARK_SCALE_ID'))
      ->predicate('COURSE_HISTORY_ID', $id)
      ->execute()->fetch();
    $mark_scale_id = $current_info['MARK_SCALE_ID'];
    }
    
    $data += $this->_determineAward($mark_scale_id, $data['MARK'], $data['CREDITS_ATTEMPTED']);
    
    $student_course_history_poster = $this->poster_factory->newPoster(null, array('STUD_STUDENT_COURSE_HISTORY' => array($id => $data)));
    $student_course_history_id = $student_course_history_poster->getResultForTable('insert', 'STUD_STUDENT_COURSE_HISTORY')[$id];
    return $student_course_history_id;
  }
  
  private function _determineAward($mark_scale_id, $mark, $credits_attempted) {
    
    // Get GPA Value
    $mark_info = $this->database->select('STUD_MARK_SCALE_MARKS')
      ->fields(null, array('MARK', 'GETS_CREDIT', 'GPA_VALUE', 'ALLOW_COMMENTS', 'REQUIRE_COMMENTS'))
      ->predicate('MARK_SCALE_ID', $mark_scale_id)
      ->predicate('MARK', $mark)
      ->execute()->fetch();
    
    if ($mark_info['MARK'] == '')
      $award_data['MARK'] = null;
    
    // Determine credit
    if ($mark_info['GETS_CREDIT'] == 'Y')
      $award_data['CREDITS_EARNED'] = $credits_attempted;
    else
      $award_data['CREDITS_EARNED'] = 0.0;
    
    $award_data['GPA_VALUE'] = $mark_info['GPA_VALUE'];
    $award_data['QUALITY_POINTS'] = $mark_info['GPA_VALUE'] * $award_data['CREDITS_EARNED'];
    
    if ($mark_info['ALLOW_COMMENTS'] == 'Y' OR $mark_info['REQUIRE_COMMENTS'] == 'Y') {
      $award_data['COMMENTS'] = 'Y';
    }
    
    return $award_data;
  }
  
}