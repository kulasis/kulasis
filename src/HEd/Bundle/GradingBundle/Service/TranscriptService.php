<?php

namespace Kula\HEd\Bundle\GradingBundle\Service;

class TranscriptService {
  
  protected $db;

  public function __construct(\Kula\Core\Component\DB\DB $db) {
    $this->db = $db;
    $this->course_history_data = array();
    $this->current_schedule_data = array();
    $this->degrees_awarded_data = array();
    $this->student_data = array();
  }
  
  public function loadTranscriptForStudent($student_id, $level = null) {
    
    $this->loadStudentData($student_id, $level);
    $this->loadDegreesAwarded($student_id, $level);
    $this->loadTranscriptData($student_id, $level);
    $this->loadCurrentSchedule($student_id, $level);
    
  }
  
  public function getTranscriptData() {
    return $this->course_history_data;
  }
  
  public function getCurrentScheduleData() {
    return $this->current_schedule_data;
  }

  public function getDegreeData() {
    return $this->degrees_awarded_data;
  }
  
  public function getStudentData() {
    return $this->student_data;
  }

  private function loadStudentData($student_id, $level = null) {
    
    $status_info = $this->db->db_select('STUD_STUDENT', 'student')
      ->fields('student', array('STUDENT_ID', 'ORIGINAL_ENTER_DATE', 'HIGH_SCHOOL_GRADUATION_DATE'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'BIRTH_DATE'))
      ->leftJoin('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CORE_LOOKUP_VALUES', 'grade_values', "grade_values.CODE = status.GRADE AND grade_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Grade')")
      ->fields('grade_values', array('DESCRIPTION' => 'GRADE'))
      ->leftJoin('STUD_STUDENT_DEGREES', 'studdegrees', 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->fields('studdegrees', array('STUDENT_DEGREE_ID'))
      ->leftJoin('STUD_DEGREE', 'degree', 'degree.DEGREE_ID = studdegrees.DEGREE_ID')
      ->fields('degree', array('DEGREE_NAME'))
      ->condition('status.STUDENT_ID', $student_id);

    if ($level) {
     $status_info = $status_info->condition('status.LEVEL', $level);  
    }
    
    $this->student_data = $status_info->orderBy('ENTER_DATE', 'DESC')->execute()->fetch();

    $this->student_data['areas'] = '';

    $areas = array();

    // get areas
    $areas_info = $this->db->db_select('STUD_STUDENT_DEGREES_AREAS', 'stuareas')
      ->join('STUD_DEGREE_AREA', 'area', 'stuareas.AREA_ID = area.AREA_ID')
      ->fields('area', array('AREA_NAME'))
      ->join('CORE_LOOKUP_VALUES', 'area_types', "area_types.CODE = area.AREA_TYPE AND area_types.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Grading.Degree.AreaTypes')")
      ->fields('area_types', array('DESCRIPTION' => 'area_type'))
      ->condition('stuareas.STUDENT_DEGREE_ID', $this->student_data['STUDENT_DEGREE_ID'])
      ->execute();
    while ($areas_row = $areas_info->fetch()) {
      $areas[] = $areas_row['area_type'].': '.$areas_row['AREA_NAME'];
    }
    $this->student_data['areas'] = implode(', ', $areas);
  }
  
  public function loadDegreesAwarded($student_id, $level = null) {

    $this->degrees_awarded_data = array();

    // Get Degrees
    $degrees_res = $this->db->db_select('STUD_STUDENT_DEGREES', 'studdegrees')
      ->fields('studdegrees', array('STUDENT_DEGREE_ID', 'DEGREE_AWARDED', 'GRADUATION_DATE', 'CONFERRED_DATE'))
      ->join('STUD_DEGREE', 'degree', 'studdegrees.DEGREE_ID = degree.DEGREE_ID')
      ->fields('degree', array('DEGREE_NAME', 'LEVEL'))
      ->condition('studdegrees.STUDENT_ID', $student_id)
      ->condition('studdegrees.DEGREE_AWARDED', 1);
    if ($level) {
      $degrees_res = $degrees_res->condition('degree.LEVEL', $level);
    }
      $degrees_res = $degrees_res->execute();
    while ($degree_row = $degrees_res->fetch()) {
      
      if (isset($this->degrees_awarded_data[$degree_row['LEVEL']]))
        $i = count($this->degrees_awarded_data[$degree_row['LEVEL']]);
      else
        $i = 0;

      $this->degrees_awarded_data[$degree_row['LEVEL']][$i] = $degree_row;

      // Get areas
      $areas_result = $this->db->db_select('STUD_STUDENT_DEGREES_AREAS', 'studarea')
        ->fields('studarea', array('AREA_ID'))
        ->join('STUD_DEGREE_AREA', 'area', 'studarea.AREA_ID = area.AREA_ID')
        ->fields('area', array('AREA_NAME'))
        ->join('CORE_LOOKUP_VALUES', 'area_types', "area_types.CODE = area.AREA_TYPE AND area_types.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Grading.Degree.AreaTypes')")
        ->fields('area_types', array('DESCRIPTION' => 'area_type'))
        ->condition('studarea.STUDENT_DEGREE_ID', $degree_row['STUDENT_DEGREE_ID'])
        ->orderBy('area_types.SORT', 'ASC')
        ->orderBy('area_types.DESCRIPTION', 'ASC')
        ->orderBy('area.AREA_NAME', 'ASC')
        ->execute();
      while ($areas_row = $areas_result->fetch()) {
        $this->degrees_awarded_data[$degree_row['LEVEL']][$i]['areas'][] = $areas_row;
      }

    }
    
  }
  
  public function loadTranscriptData($student_id, $level = null) {
    
    $this->course_history_data = array();

    // Add on level
    $level_condition = '';
    if ($level) {
      $level_condition = ' AND coursehistory.LEVEL = \''.$level.'\'';
    }
    
    // Get student
    $result = $this->db->db_select('STUD_STUDENT', 'student')
      ->distinct()
      ->fields('student', array('STUDENT_ID', 'ORIGINAL_ENTER_DATE', 'HIGH_SCHOOL_GRADUATION_DATE'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'BIRTH_DATE'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', 'coursehistory.STUDENT_ID = student.STUDENT_ID '.$level_condition)
      ->fields('coursehistory', array('COURSE_HISTORY_ID', 'ORGANIZATION_ID', 'LEVEL', 'COURSE_NUMBER', 'COURSE_TITLE', 'MARK', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'QUALITY_POINTS', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'NON_ORGANIZATION_ID', 'TRANSFER_CREDITS', 'GPA_VALUE', 'MARK_SCALE_ID'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'level_values', "level_values.CODE = coursehistory.LEVEL AND level_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Level')")
      ->fields('level_values', array('DESCRIPTION' => 'LEVEL_DESCRIPTION'))
      ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = coursehistory.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->leftJoin('CORE_NON_ORGANIZATION', 'nonorg', 'nonorg.NON_ORGANIZATION_ID = coursehistory.NON_ORGANIZATION_ID')
      ->fields('nonorg', array('NON_ORGANIZATION_NAME'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY_TERMS', 'crshisterms', 'crshisterms.STUDENT_ID = coursehistory.STUDENT_ID AND (crshisterms.CALENDAR_YEAR = coursehistory.CALENDAR_YEAR OR (crshisterms.CALENDAR_YEAR IS NULL AND coursehistory.CALENDAR_YEAR IS NULL))
        AND (crshisterms.CALENDAR_MONTH = coursehistory.CALENDAR_MONTH OR (crshisterms.CALENDAR_MONTH IS NULL AND coursehistory.CALENDAR_YEAR IS NULL))
        AND (crshisterms.TERM = coursehistory.TERM OR (crshisterms.TERM IS NULL AND coursehistory.TERM IS NULL))
        AND (crshisterms.LEVEL = coursehistory.LEVEL OR (crshisterms.LEVEL IS NULL AND coursehistory.LEVEL IS NULL))')
      ->fields('crshisterms', array('COMMENTS', 'TERM_CREDITS_ATTEMPTED', 'TERM_CREDITS_EARNED', 'TERM_HOURS', 'TERM_POINTS', 'TERM_GPA', 'CUM_CREDITS_ATTEMPTED', 'CUM_CREDITS_EARNED', 'CUM_HOURS', 'CUM_POINTS', 'CUM_GPA', 'INST_CREDITS_ATTEMPTED', 'INST_CREDITS_EARNED', 'INST_HOURS' ,'INST_POINTS', 'INST_GPA', 'TRNS_CREDITS_ATTEMPTED', 'TRNS_CREDITS_EARNED', 'TRNS_HOURS', 'TRNS_POINTS', 'TRNS_GPA', 'TOTAL_CREDITS_ATTEMPTED', 'TOTAL_CREDITS_EARNED', 'TOTAL_HOURS', 'TOTAL_POINTS', 'TOTAL_GPA'));
    $result = $result->condition('student.STUDENT_ID', $student_id);

    $result = $result
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->orderBy('student.STUDENT_ID', 'ASC')
      ->orderBy('coursehistory.LEVEL', 'ASC')
      ->orderBy('coursehistory.CALENDAR_YEAR', 'ASC')
      ->orderBy('coursehistory.CALENDAR_MONTH', 'ASC')
      ->orderBy('coursehistory.TERM', 'ASC')
      ->orderBy('nonorg.NON_ORGANIZATION_NAME', 'ASC')
      ->orderBy('org.ORGANIZATION_NAME', 'ASC')
      ->execute();
    
    $last_calendar_month = 0;
    $last_calendar_year = 0;
    $last_term = 0;
    $last_level = '';
    $last_level_code = '';
    $last_non_organization_name = '';
    $last_organization_name = '';
    $term_counter = -1; // first loop through conditional to increase will execute, making the first term 0
    $course_counter = 0;
    while ($row = $result->fetch()) {
      
      if ($last_calendar_month !== $row['CALENDAR_MONTH'] || $last_calendar_year !== $row['CALENDAR_YEAR'] || $last_term !== $row['TERM']) {
        $term_counter++;
        $organization_counter = 0;
        $last_non_organization_name = null;
        $last_organization_name = null;
        
        $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['CALENDAR_MONTH'] = $row['CALENDAR_MONTH'];
        $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['CALENDAR_YEAR'] = $row['CALENDAR_YEAR'];
        $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['TERM'] = $row['TERM'];
        $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['LEVEL'] = $row['LEVEL'];
        $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['LEVEL_DESCRIPTION'] = $row['LEVEL_DESCRIPTION'];
        $this->course_history_data['levels'][$row['LEVEL']]['level_description'] = $row['LEVEL_DESCRIPTION'];
        
        // Load Standings
        $standings_info_result = $this->db->db_select('STUD_STUDENT_COURSE_HISTORY_STANDING', 'chstanding')
          ->join('STUD_STANDING', 'standing', 'chstanding.STANDING_ID = standing.STANDING_ID')
          ->fields('standing', array('STANDING_DESCRIPTION'))
          ->condition('chstanding.STUDENT_ID', $row['STUDENT_ID'])
          ->condition('chstanding.CALENDAR_MONTH', $row['CALENDAR_MONTH'])
          ->condition('chstanding.CALENDAR_YEAR', $row['CALENDAR_YEAR'])
          ->condition('chstanding.TERM', $row['TERM'])
          ->execute();
        while ($standings_info_row = $standings_info_result->fetch()) {
          $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['standings'][] = $standings_info_row['STANDING_DESCRIPTION'];
        }
      }
      
      if ($last_organization_name !== $row['ORGANIZATION_NAME'] || $last_non_organization_name !== $row['NON_ORGANIZATION_NAME']) {
      	$organization_counter++;
      	$course_counter = 0;

      // Course history info
      if ($row['NON_ORGANIZATION_NAME'] != '')
          $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['ORGANIZATION_NAME'] = $row['NON_ORGANIZATION_NAME'];
        else
          $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['ORGANIZATION_NAME'] = $row['ORGANIZATION_NAME'];
        
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['NON_ORGANIZATION_NAME'] = $row['NON_ORGANIZATION_NAME'];

  	  }

      
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['courses'][$course_counter]['COURSE_NUMBER'] = $row['COURSE_NUMBER'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['courses'][$course_counter]['COURSE_TITLE'] = $row['COURSE_TITLE'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['courses'][$course_counter]['MARK'] = $row['MARK'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['courses'][$course_counter]['MARK_SCALE_ID'] = $row['MARK_SCALE_ID'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['courses'][$course_counter]['CREDITS_ATTEMPTED'] = $row['CREDITS_ATTEMPTED'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['courses'][$course_counter]['CREDITS_EARNED'] = $row['CREDITS_EARNED'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['courses'][$course_counter]['QUALITY_POINTS'] = $row['QUALITY_POINTS'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['courses'][$course_counter]['TRANSFER_CREDITS'] = $row['TRANSFER_CREDITS'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['orgs'][$organization_counter]['courses'][$course_counter]['GPA_VALUE'] = $row['GPA_VALUE'];
      
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['TERM_CREDITS_ATTEMPTED'] = $row['TERM_CREDITS_ATTEMPTED'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['TERM_CREDITS_EARNED'] = $row['TERM_CREDITS_EARNED'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['TERM_HOURS'] = $row['TERM_HOURS'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['TERM_POINTS'] = $row['TERM_POINTS'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['TERM_GPA'] = $row['TERM_GPA'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['CUM_CREDITS_ATTEMPTED'] = $row['CUM_CREDITS_ATTEMPTED'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['CUM_CREDITS_EARNED'] = $row['CUM_CREDITS_EARNED'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['CUM_HOURS'] = $row['CUM_HOURS'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['CUM_POINTS'] = $row['CUM_POINTS'];
      $this->course_history_data['levels'][$row['LEVEL']]['terms'][$term_counter]['CUM_GPA'] = $row['CUM_GPA'];
      
      $this->course_history_data['levels'][$row['LEVEL']]['CUM_CREDITS_ATTEMPTED'] = $row['CUM_CREDITS_ATTEMPTED'];
      $this->course_history_data['levels'][$row['LEVEL']]['CUM_CREDITS_EARNED'] = $row['CUM_CREDITS_EARNED'];
      $this->course_history_data['levels'][$row['LEVEL']]['CUM_HOURS'] = $row['CUM_HOURS'];
      $this->course_history_data['levels'][$row['LEVEL']]['CUM_POINTS'] = $row['CUM_POINTS'];
      $this->course_history_data['levels'][$row['LEVEL']]['CUM_GPA'] = $row['CUM_GPA'];
      $this->course_history_data['levels'][$row['LEVEL']]['INST_CREDITS_ATTEMPTED'] = $row['INST_CREDITS_ATTEMPTED'];
      $this->course_history_data['levels'][$row['LEVEL']]['INST_CREDITS_EARNED'] = $row['INST_CREDITS_EARNED'];
      $this->course_history_data['levels'][$row['LEVEL']]['INST_HOURS'] = $row['INST_HOURS'];
      $this->course_history_data['levels'][$row['LEVEL']]['INST_POINTS'] = $row['INST_POINTS'];
      $this->course_history_data['levels'][$row['LEVEL']]['INST_GPA'] = $row['INST_GPA'];
      $this->course_history_data['levels'][$row['LEVEL']]['TRNS_CREDITS_ATTEMPTED'] = $row['TRNS_CREDITS_ATTEMPTED'];
      $this->course_history_data['levels'][$row['LEVEL']]['TRNS_CREDITS_EARNED'] = $row['TRNS_CREDITS_EARNED'];
      $this->course_history_data['levels'][$row['LEVEL']]['TRNS_HOURS'] = $row['TRNS_HOURS'];
      $this->course_history_data['levels'][$row['LEVEL']]['TRNS_POINTS'] = $row['TRNS_POINTS'];
      $this->course_history_data['levels'][$row['LEVEL']]['TRNS_GPA'] = $row['TRNS_GPA'];
      $this->course_history_data['levels'][$row['LEVEL']]['TOTAL_CREDITS_ATTEMPTED'] = $row['TOTAL_CREDITS_ATTEMPTED'];
      $this->course_history_data['levels'][$row['LEVEL']]['TOTAL_CREDITS_EARNED'] = $row['TOTAL_CREDITS_EARNED'];
      $this->course_history_data['levels'][$row['LEVEL']]['TOTAL_HOURS'] = $row['TOTAL_HOURS'];
      $this->course_history_data['levels'][$row['LEVEL']]['TOTAL_POINTS'] = $row['TOTAL_POINTS'];
      $this->course_history_data['levels'][$row['LEVEL']]['TOTAL_GPA'] = $row['TOTAL_GPA'];
      $this->course_history_data['levels'][$row['LEVEL']]['COMMENTS'] = $row['COMMENTS'];

      $course_counter++;
      
      $last_calendar_month = $row['CALENDAR_MONTH'];
      $last_calendar_year = $row['CALENDAR_YEAR'];
      $last_term = $row['TERM'];
      $last_level = $row['LEVEL_DESCRIPTION'];
      $last_level_code = $row['LEVEL'];
      $last_non_organization_name = $row['NON_ORGANIZATION_NAME'];
      $last_organization_name = $row['ORGANIZATION_NAME'];
    } // end while
    
  }
  
  private function loadCurrentSchedule($student_id, $level = null) {
    
    $this->current_schedule_data = array();  

    // Add on level
    $level_condition = '';
    if ($level) {
      $level_condition = ' AND classes.LEVEL = \''.$level.'\'';
    }
    
    $schedule_result = $this->db->db_select('STUD_STUDENT', 'student')
      ->fields('student', array('STUDENT_ID'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = student.STUDENT_ID')
      ->join('STUD_STUDENT_CLASSES', 'classes', 'classes.STUDENT_STATUS_ID = stustatus.STUDENT_STATUS_ID '.$level_condition)
      ->fields('classes', array('LEVEL', 'CREDITS_ATTEMPTED'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = classes.SECTION_ID')
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_NAME'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'level_values', "level_values.CODE = classes.LEVEL AND level_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Level')")
      ->fields('level_values', array('DESCRIPTION' => 'LEVEL_DESCRIPTION'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'stucoursehistory', 'stucoursehistory.STUDENT_CLASS_ID = classes.STUDENT_CLASS_ID')
      ->condition('stucoursehistory.COURSE_HISTORY_ID', null)
      ->condition('DROPPED', 0)
      ->condition('student.STUDENT_ID', $student_id)
      ->orderBy('student.STUDENT_ID', 'ASC')
      ->orderBy('level_values.DESCRIPTION', 'ASC')
      ->orderBy('term.START_DATE', 'ASC')
      ->orderBy('course.COURSE_NUMBER', 'ASC');
      
    $schedule_result = $schedule_result->execute();
    while ($schedule_row = $schedule_result->fetch()) {
      $this->current_schedule_data[$schedule_row['LEVEL_DESCRIPTION']][$schedule_row['ORGANIZATION_NAME']][$schedule_row['TERM_NAME']][] = $schedule_row; 
    }
  }

}