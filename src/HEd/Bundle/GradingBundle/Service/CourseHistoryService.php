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
      ->fields('class', array('START_DATE', 'STUDENT_CLASS_ID', 'LEVEL', 'MARK_SCALE_ID', 'COURSE_FOR_GRADE_ID', 'REPEAT_TAG_ID', 'DEGREE_REQ_GRP_ID', 'CREDITS_ATTEMPTED'))
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
    if ($course_info['COURSE_FOR_GRADE_ID']) {
      $course_history_data['HEd.Student.CourseHistory.CourseID'] = $course_info['COURSE_FOR_GRADE_ID'];
    } else {
      $course_history_data['HEd.Student.CourseHistory.CourseID'] = $course_info['COURSE_ID'];
    }
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
    $course_history_data['HEd.Student.CourseHistory.RepeatTagID'] = $course_info['REPEAT_TAG_ID'];
    $course_history_data['HEd.Student.CourseHistory.Mark'] = $mark;
    $course_history_data['HEd.Student.CourseHistory.DegreeRequirementGroupID'] = $course_info['DEGREE_REQ_GRP_ID'];
    $course_history_data['HEd.Student.CourseHistory.TeacherSet'] = $teacher_set;
    
    if ($course_info['CREDITS_ATTEMPTED'] != '') {
      $credits_attempted = $course_info['CREDITS_ATTEMPTED'];
    } else {
      $credits_attempted = $course_info['CREDITS'];
    }

    // Get award data
    $course_history_data += $this->determineAward($course_info['MARK_SCALE_ID'], $mark, $credits_attempted);
    unset($course_history_data['COMMENTS']);

    $result = $this->posterFactory->newPoster()->add('HEd.Student.CourseHistory', 'new', $course_history_data)->process()->getResult();

    // apply repeat tag
    if ($course_history_data['HEd.Student.CourseHistory.RepeatTagID'] != '') {
      $this->calculateRepeatTag($result);
    }
    
    return $result;
    
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
	  $course_history_data = $award_data;
    $course_history_data['HEd.Student.CourseHistory.Mark'] = $mark;
    
    if ((isset($award_data['COMMENTS']) AND $award_data['COMMENTS'] == 'Y' AND $teacher_set == 1) OR $teacher_set == 0) {
      $course_history_data['HEd.Student.CourseHistory.Comments'] = $comments;
    } else {
      $course_history_data['HEd.Student.CourseHistory.Comments'] = null;
    }
    
    unset($course_history_data['COMMENTS']);

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

    $poster = $this->posterFactory->newPoster()->edit('HEd.Student.CourseHistory', $id, $data)->process();
    $poster_record = $poster->getPosterRecord('HEd.Student.CourseHistory', $id);

    // apply repeat tag
    if (isset($data['HEd.Student.CourseHistory.RepeatTagID']) AND $data['HEd.Student.CourseHistory.RepeatTagID'] != '') {
      $this->calculateRepeatTag($id);
    }

    // if repeat tag removed
    if ($poster_record->getOriginalField('REPEAT_TAG_ID') != '' AND isset($data['HEd.Student.CourseHistory.RepeatTagID']) AND $data['HEd.Student.CourseHistory.RepeatTagID'] == '') {
      $this->removeRepeatTag($id);
    }

    return $poster->getResult();
  }
  
  private function determineAward($mark_scale_id, $mark, $credits_attempted) {
    
    // Get GPA Value
    $mark_info = $this->database->db_select('STUD_MARK_SCALE_MARKS')
      ->fields('STUD_MARK_SCALE_MARKS', array('MARK', 'GETS_CREDIT', 'GPA_VALUE', 'ALLOW_COMMENTS', 'REQUIRE_COMMENTS'))
      ->join('STUD_MARK_SCALE', 'STUD_MARK_SCALE', 'STUD_MARK_SCALE.MARK_SCALE_ID = STUD_MARK_SCALE_MARKS.MARK_SCALE_ID')
      ->fields('STUD_MARK_SCALE', array('AUDIT'))
      ->condition('STUD_MARK_SCALE_MARKS.MARK_SCALE_ID', $mark_scale_id)
      ->condition('MARK', $mark)
      ->execute()->fetch();
    
    if ($mark_info['MARK'] == '')
      $award_data['HEd.Student.CourseHistory.Mark'] = null;
      
  	if ($mark_info['AUDIT'] == 1) {
  		$award_data['HEd.Student.CourseHistory.CreditsAttempted'] = 0.0;
  	} else {
  		$award_data['HEd.Student.CourseHistory.CreditsAttempted'] = $credits_attempted;
  	}

    $award_data['HEd.Student.CourseHistory.CalculatedTermCreditsAttempted'] = $award_data['HEd.Student.CourseHistory.CreditsAttempted'];
    $award_data['HEd.Student.CourseHistory.CalculatedCumulativeCreditsAttempted'] = $award_data['HEd.Student.CourseHistory.CreditsAttempted'];
	
    // Determine credit
    if ($mark_info['GETS_CREDIT'] == 1)
      $award_data['HEd.Student.CourseHistory.CreditsEarned'] = $credits_attempted;
    else
      $award_data['HEd.Student.CourseHistory.CreditsEarned'] = 0.0;

    $award_data['HEd.Student.CourseHistory.CalculatedTermCreditsEarned'] = $award_data['HEd.Student.CourseHistory.CreditsEarned'];
    $award_data['HEd.Student.CourseHistory.CalculatedCumulativeCreditsEarned'] = $award_data['HEd.Student.CourseHistory.CreditsEarned'];

    $award_data['HEd.Student.CourseHistory.GPAValue'] = $mark_info['GPA_VALUE'];
    $award_data['HEd.Student.CourseHistory.QualityPoints'] = $mark_info['GPA_VALUE'] * $award_data['HEd.Student.CourseHistory.CreditsEarned'];
    
    if ($mark_info['ALLOW_COMMENTS'] == 1 OR $mark_info['REQUIRE_COMMENTS'] == 1) {
      $award_data['COMMENTS'] = 'Y';
    }
    
    return $award_data;
  }

  private function calculateRepeatTag($course_history_id) {

    $course_history = $this->database->db_select('STUD_STUDENT_COURSE_HISTORY', 'crshist')
      ->fields('crshist', array('COURSE_ID', 'LEVEL', 'STUDENT_ID'))
      ->join('STUD_REPEAT_TAG', 'repeattag', 'repeattag.REPEAT_TAG_ID = crshist.REPEAT_TAG_ID')
      ->fields('repeattag')
      ->condition('crshist.COURSE_HISTORY_ID', $course_history_id)
      ->execute()->fetch();

    // Step 1: Find all previous course history records of same course ID and level
    $previous_course_history = $this->database->db_select('STUD_STUDENT_COURSE_HISTORY', 'crshist')
      ->fields('crshist', array('COURSE_HISTORY_ID'))
      ->condition('crshist.STUDENT_ID', $course_history['STUDENT_ID'])
      ->condition('crshist.COURSE_ID', $course_history['COURSE_ID'])
      ->condition('crshist.LEVEL', $course_history['LEVEL']);
    if ($course_history_id) {
      $previous_course_history = $previous_course_history->condition('crshist.COURSE_HISTORY_ID', $course_history_id, '!=');
    }
    $previous_course_history = $previous_course_history->execute();
    while ($previous_course_history_row = $previous_course_history->fetch()) {

      $award_data = array();

      // Step 2: Apply repeat tag calculations to them
      if ($course_history['INCLUDE_TERM_CREDITS_ATTMPT'] == 0)
        $award_data['HEd.Student.CourseHistory.CalculatedTermCreditsAttempted'] = null;
      if ($course_history['INCLUDE_TERM_CREDITS_EARNED'] == 0)
        $award_data['HEd.Student.CourseHistory.CalculatedTermCreditsEarned'] = null;
      if ($course_history['INCLUDE_CUM_CREDITS_ATTMPT'] == 0)
        $award_data['HEd.Student.CourseHistory.CalculatedCumulativeCreditsAttempted'] = null;
      if ($course_history['INCLUDE_CUM_CREDITS_EARNED'] == 0)
        $award_data['HEd.Student.CourseHistory.CalculatedCumulativeCreditsEarned'] = null;
      if ($course_history['INCLUDE_TERM_GPA'] == 0)
        $award_data['HEd.Student.CourseHistory.IncludeTermGPA'] = 0;
      if ($course_history['INCLUDE_CUM_GPA'] == 0)
        $award_data['HEd.Student.CourseHistory.IncludeCumulativeGPA'] = 0;

      $award_data['HEd.Student.CourseHistory.RepeatTagCourseHistoryID'] = $course_history_id;

      $this->posterFactory->newPoster()->edit('HEd.Student.CourseHistory', $previous_course_history_row['COURSE_HISTORY_ID'], $award_data)->process()->getResult();

    }

  }

  private function removeRepeatTag($course_history_id) {

    // Step 1: Find all previous course history records of same course ID and level
    $previous_course_history = $this->database->db_select('STUD_STUDENT_COURSE_HISTORY', 'crshist')
      ->fields('crshist', array('COURSE_HISTORY_ID', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED'))
      ->condition('crshist.REPEAT_TAG_CRS_HIS_ID', $course_history_id)
      ->execute();
    while ($previous_course_history_row = $previous_course_history->fetch()) {
      $award_data = array();

      $award_data['HEd.Student.CourseHistory.CalculatedTermCreditsAttempted'] = $previous_course_history_row['CREDITS_ATTEMPTED'];
      $award_data['HEd.Student.CourseHistory.CalculatedTermCreditsEarned'] = $previous_course_history_row['CREDITS_EARNED'];
      $award_data['HEd.Student.CourseHistory.CalculatedCumulativeCreditsAttempted'] = $previous_course_history_row['CREDITS_ATTEMPTED'];
      $award_data['HEd.Student.CourseHistory.CalculatedCumulativeCreditsEarned'] = $previous_course_history_row['CREDITS_EARNED'];
      $award_data['HEd.Student.CourseHistory.IncludeTermGPA'] = 1;
      $award_data['HEd.Student.CourseHistory.IncludeCumulativeGPA'] = 1;

      $award_data['HEd.Student.CourseHistory.RepeatTagCourseHistoryID'] = null;

      $this->posterFactory->newPoster()->edit('HEd.Student.CourseHistory', $previous_course_history_row['COURSE_HISTORY_ID'], $award_data)->process()->getResult();
    }

  }
  
}