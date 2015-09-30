<?php

namespace Kula\HEd\Bundle\GradingBundle\Service;

class DegreeAuditService {
  
  protected $db;
  
  protected $output;
  
  protected $course_history;
  
  protected $req_grp_totals;
  
  protected $degrees;
  protected $majors;
  protected $minors;
  protected $concentrations;
  

  protected $total_degree_needed;
  protected $total_degree_completed;

  public function __construct(\Kula\Core\Component\DB\DB $db) {
    $this->db = $db;
    $this->output = array();
    $this->course_history = array();
    $this->req_grp_totals = array();
    $this->degrees = array();
    $this->majors = array();
    $this->minors = array();
    $this->concentrations = array();
    $total_degree_needed = 0;
    $total_degree_completed = 0;
  }
  
  public function getDegreeAuditForStudentStatus($student_status_id) {
    
    // Get student
    $student = $this->db->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('LEVEL', 'STUDENT_ID', 'STUDENT_STATUS_ID'))
      ->join('STUD_STUDENT_DEGREES', 'studeg', 'studeg.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->fields('studeg', array('STUDENT_DEGREE_ID', 'EXPECTED_GRADUATION_DATE'))
      ->join('STUD_DEGREE', 'deg', 'deg.DEGREE_ID = studeg.DEGREE_ID')
      ->fields('deg', array('DEGREE_ID', 'DEGREE_NAME'))
      ->leftJoin('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentration', 'stuconcentration.STUDENT_DEGREE_ID = studeg.STUDENT_DEGREE_ID')
      ->leftJoin('STUD_DEGREE_CONCENTRATION', 'degconcentration', 'stuconcentration.CONCENTRATION_ID = degconcentration.CONCENTRATION_ID')
      ->fields('degconcentration', array('CONCENTRATION_NAME'))
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = studeg.EXPECTED_COMPLETION_TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION' => 'expected_completion_term'))
      ->condition('status.STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
    $this->degrees[] = $student['DEGREE_NAME'];
    
    // Get Majors
    $row['majors'] = array();
    $majors_result = $this->db->db_select('STUD_STUDENT_DEGREES_MAJORS', 'studmajor')
      ->fields('studmajor', array('MAJOR_ID'))
      ->join('STUD_DEGREE_MAJOR', 'major', 'studmajor.MAJOR_ID = major.MAJOR_ID')
      ->fields('major', array('MAJOR_NAME'))
      ->condition('studmajor.STUDENT_DEGREE_ID', $student['STUDENT_DEGREE_ID'])
      ->orderBy('major.MAJOR_NAME', 'ASC')
      ->execute();
    while ($majors_row = $majors_result->fetch()) {
      $this->majors[] = $majors_row['MAJOR_NAME'];
      $row['major_ids'][] = $majors_row['MAJOR_ID'];
    }
    
    // Get Minors
    $row['minors'] = array();
    $minors_result = $this->db->db_select('STUD_STUDENT_DEGREES_MINORS', 'studminor')
      ->fields('studminor', array('MINOR_ID'))
      ->join('STUD_DEGREE_MINOR', 'minor', 'studminor.MINOR_ID = minor.MINOR_ID')
      ->fields('minor', array('MINOR_NAME'))
      ->condition('studminor.STUDENT_DEGREE_ID', $student['STUDENT_DEGREE_ID'])
      ->orderBy('minor.MINOR_NAME', 'ASC')
      ->execute();
    while ($minors_row = $minors_result->fetch()) {
      $this->minors[] = $minors_row['MINOR_NAME'];
      $row['minor_ids'][] = $minors_row['MINOR_ID'];
    }
    
    // Get Concentrations
    $row['concentrations'] = array();
    $concentrations_result = $this->db->db_select('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'studconcentration')
      ->fields('studconcentration', array('CONCENTRATION_ID'))
      ->join('STUD_DEGREE_CONCENTRATION', 'concentration', 'studconcentration.CONCENTRATION_ID = concentration.CONCENTRATION_ID')
      ->fields('concentration', array('CONCENTRATION_NAME'))
      ->condition('studconcentration.STUDENT_DEGREE_ID', $student['STUDENT_DEGREE_ID'])
      ->orderBy('concentration.CONCENTRATION_NAME', 'ASC')
      ->execute();
    while ($concentrations_row = $concentrations_result->fetch()) {
      $this->concentrations[] = $concentrations_row['CONCENTRATION_NAME'];
      $row['concentration_ids'][] = $concentrations_row['CONCENTRATION_ID'];
    }
    
    $this->course_history = $this->getCourseHistoryForStudent($student['STUDENT_ID'], $student['LEVEL'], $student['STUDENT_STATUS_ID']);
    
    $requirements = $this->requirements($student['DEGREE_ID'], (isset($row['major_ids'])) ? $row['major_ids'] : null, (isset($row['minor_ids'])) ? $row['minor_ids'] : null, (isset($row['concentration_ids'])) ? $row['concentration_ids'] : null);
    
    
    // Get Degree Requirements but not requirement marked as elective
    $elective_req_id = null;
    if (isset($requirements['degree'][$student['DEGREE_ID']])) {
    foreach($requirements['degree'][$student['DEGREE_ID']] as $req_id => $req_row) {
      if ($req_row['ELECTIVE'] == '1') {
        $elective_req_id = $req_id;
      } else {
        $this->outputRequirementSet($req_id, $req_row);
      }
    }
    }
    
    // Get Minor Requirements
    if (isset($row['minor_ids'])) {
    foreach($row['minor_ids'] as $minor_id) {
    foreach($requirements['minor'][$minor_id] as $req_id => $req_row) {
      $this->outputRequirementSet($req_id, $req_row);
    }
    }
    }
    
    // Get Concentration Requirements
    if (isset($row['concentration_ids'])) {
    foreach($row['concentration_ids'] as $concentration_id) {
    foreach($requirements['concentration'][$concentration_id] as $req_id => $req_row) {
      $this->outputRequirementSet($req_id, $req_row);
    }
    }
    }
    
    // Output elective
    // Must be on end to look at everything not already applied
    if (isset($requirements['degree'][$student['DEGREE_ID']][$elective_req_id])) {
      $this->outputRequirementSet($elective_req_id, $requirements['degree'][$student['DEGREE_ID']][$elective_req_id], 'Y');
    }
    
    /*
    echo "<pre>";
    print_r($this->course_history);
    echo "</pre>";
    
    echo "<pre>";
    print_r($this->output);
    echo "</pre>";
    */
    return $this->output;
  }
  
  private function outputRequirementSet($req_id, $req_row, $elective = null) {
    
    $this->output[$req_id] = $req_row;
    // Loop through courses
    if ($elective) { // AND isset($this->pdf->course_history_data['elective'])
      if (isset($req_row['courses'])) {
      foreach($req_row['courses'] as $req_crs_id => $req_crs_row) {
        $this->output[$req_id]['courses'][$req_crs_id] = $req_crs_row;
      }
      }
      foreach($this->course_history as $course_id => $row) {
        foreach($row as $row_id => $row_data) {
        if (!isset($row_data['used']) AND $course_id != 'elective') {
          $this->req_grp_row($req_id, $row_id, $row, 'Y');
        }
        }
      }
    } else {
      if (isset($req_row['courses'])) {
      foreach($req_row['courses'] as $req_crs_id => $req_crs_row) {
        $this->req_grp_row($req_id, $req_crs_id, $req_crs_row);
      }
      }
      foreach($this->course_history as $course_id => $row) {
        foreach($row as $row_id => $row_data) {
        if (!isset($row_data['used']) AND $course_id != 'elective' AND $req_id == $row_data['DEGREE_REQ_GRP_ID']) {
          $this->req_grp_row($req_id, $row_id, $row, 'Y');
        }
        }
      }
    }
    
    $this->total_degree_needed += $this->output[$req_id]['CREDITS_REQUIRED'];
    $this->total_degree_completed += $this->req_grp_totals[$req_id];
    
    $this->output[$req_id]['credits_earned'] = sprintf('%0.2f', round($this->req_grp_totals[$req_id], 2, PHP_ROUND_HALF_UP));
    $this->output[$req_id]['credits_remain'] = sprintf('%0.2f', round($this->output[$req_id]['CREDITS_REQUIRED'] - $this->req_grp_totals[$req_id], 2, PHP_ROUND_HALF_UP));
    
  }
  
  private function req_grp_row($req_id, $row_id, $row, $elective = null) {
    
    if (!isset($this->req_grp_totals[$req_id])) $this->req_grp_totals[$req_id] = 0;
    
      // elective
      if ($elective) {
        foreach($row as $ch_index => $ch) {
          if ($ch['COURSE_ID']) {
          if (isset($ch['STUDENT_CLASS_ID'])) {
            $ch['status'] = 'In Prog';
            $ch['display_credits'] = $ch['CREDITS'];
          } else {
            $ch['status'] = 'Comp';
            $ch['display_credits'] = $ch['CREDITS_EARNED'];
          }
          $this->output[$req_id]['courses'][] = $ch;
        
          $this->course_history[$ch['COURSE_ID']][0]['used'] = 'Y';
          $this->req_grp_totals[$req_id] += isset($ch['CREDITS_EARNED']) ? $ch['CREDITS_EARNED'] : 0;
          }
        }
        
      } elseif (isset($this->course_history[$row['COURSE_ID']]) AND count($this->course_history) > 0) {
        
        foreach($this->course_history[$row['COURSE_ID']] as $ch_index => $ch) {
          
          if ((!isset($this->course_history[$row['COURSE_ID']][$ch_index]['used']) OR 
              $this->course_history[$row['COURSE_ID']][$ch_index]['used'] != 'Y') AND 
              (!isset($ch['DEGREE_REQ_GRP_ID']) OR (isset($ch['DEGREE_REQ_GRP_ID']) AND ($ch['DEGREE_REQ_GRP_ID'] == '' OR $ch['DEGREE_REQ_GRP_ID'] == $req_id))))
         {
           
           if (isset($ch['STUDENT_CLASS_ID'])) {
             $ch['status'] = 'In Prog';
             $ch['display_credits'] = $ch['CREDITS'];
           } elseif (isset($row['CREDITS']) AND $ch['CREDITS_ATTEMPTED'] > $ch['CREDITS_EARNED']) {
             $ch['status'] = 'Remain';
             $ch['display_credits'] = $ch['CREDITS_EARNED'];
             $this->req_grp_totals[$req_id] += $ch['CREDITS_EARNED'];
          } else {
             $ch['status'] = 'Comp';
             $ch['display_credits'] = $ch['CREDITS_EARNED'];
             $this->req_grp_totals[$req_id] += $ch['CREDITS_EARNED'];
           }
           $this->output[$req_id]['courses'][$row_id] = $ch;
           
        $this->course_history[$row['COURSE_ID']][$ch_index]['used'] = 'Y';
        
        }
        
        }
        
      } elseif (isset($row['equivs']) AND $equiv_course = array_map(array($this, 'check_if_equiv_exists'), $row['equivs']) AND $equiv_course[0] != '') {

        
        foreach($equiv_course[0] as $ch_index => $ch) {
        
        if (!isset($this->course_history[$row['COURSE_ID']][$ch_index]['used']) OR $this->course_history[$row['COURSE_ID']][$ch_index]['used'] != 'Y') {
          
          $this->output[$req_id]['courses'][$row_id] = $ch;
          $this->req_grp_totals[$req_id] += $ch['CREDITS_EARNED'];
          $this->output[$req_id]['courses'][$row_id]['display_credits'] = $ch['CREDITS_EARNED'];
          $this->course_history[$ch['COURSE_ID']][$ch_index]['used'] = 'Y';
        }
        }
        
      } elseif (isset($row['CREDITS_EARNED']) AND $row['CREDITS_EARNED'] > 0) {
        
        foreach($this->course_history[$ch_course['COURSE_ID']] as $ch_index => $ch) {
        
        if (!isset($this->course_history[$row['COURSE_ID']][$ch_index]['used']) OR $this->course_history[$row['COURSE_ID']][$ch_index]['used'] != 'Y') {
        
          $this->output[$req_id]['courses'][$row_id] = $ch;
          $this->output[$req_id]['courses'][$row_id]['status'] = 'Comp';
          $this->output[$req_id]['courses'][$row_id]['display_credits'] = $ch['CREDITS_EARNED'];
          $this->course_history[$ch_course['COURSE_ID']][$ch_index]['used'] = 'Y';
          $this->req_grp_totals[$req_id] += $row['CREDITS_EARNED'];
        }
        }
        
      // if nothing
      } else {
      
        if ($row['SHOW_AS_OPTION'] == '1') {
         $this->output[$req_id]['courses'][$row_id] = $row;
         if ($row['REQUIRED']) {
           $this->output[$req_id]['courses'][$row_id]['status'] = 'Remain';
         }
         $this->output[$req_id]['courses'][$row_id]['display_credits'] = $row['CREDITS'];
       
         }
      }
  }
  
  private function getCourseHistoryForStudent($student_id, $level, $student_status_id) {
    
    $course_history = array();
    $course_history_result = $this->db->db_select('STUD_STUDENT_COURSE_HISTORY', 'ch')
      ->fields('ch', array('COURSE_ID', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'MARK', 'MARK_SCALE_ID', 'TERM', 'COURSE_NUMBER', 'COURSE_TITLE', 'DEGREE_REQ_GRP_ID'))
      ->expression("CONCAT(LEFT(TERM, 2),'-', RIGHT(TERM, 2))", 'TERM_ABBREVIATION')
      ->condition('STUDENT_ID', $student_id)
      ->condition('LEVEL', $level)
      ->condition('MARK', 'W', '!=')
      ->execute();
    while ($course_history_row = $course_history_result->fetch()) {
      if ($course_history_row['COURSE_ID'] != '')
        $course_history[$course_history_row['COURSE_ID']][] = $course_history_row;
      else
        $course_history['elective'][] = $course_history_row;
    }

    $current_schedule = array();
    $current_schedule_result = $this->db->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'DEGREE_REQ_GRP_ID', 'COURSE_ID' => 'class_COURSE_ID'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = class.SECTION_ID')
      ->join('STUD_COURSE', 'course' , 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_NUMBER', 'COURSE_TITLE', 'CREDITS', 'COURSE_ID'))
      ->leftJoin('STUD_COURSE', 'class_course' , 'class_course.COURSE_ID = class.COURSE_ID')
      ->fields('class_course', array('COURSE_NUMBER' => 'class_COURSE_NUMBER', 'COURSE_TITLE' => 'class_COURSE_TITLE', 'CREDITS' => 'class_CREDITS'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'stucrshis', 'stucrshis.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->condition('status.STUDENT_ID', $student_id)
      ->condition('DROPPED', 0)
      ->condition('stucrshis.COURSE_HISTORY_ID', null)
      ->execute();
    while ($current_schedule_row = $current_schedule_result->fetch()) {
      
      if ($current_schedule_row['class_COURSE_ID']) {
        $current_schedule_row['COURSE_ID'] = $current_schedule_row['class_COURSE_ID'];
        $current_schedule_row['COURSE_NUMBER'] = $current_schedule_row['class_COURSE_NUMBER'];
        $current_schedule_row['COURSE_TITLE'] = $current_schedule_row['class_COURSE_TITLE'];
        $current_schedule_row['CREDITS'] = $current_schedule_row['CREDITS'];
        $current_schedule_row['TERM_ABBREVIATION'] = $current_schedule_row['TERM_ABBREVIATION'];
      }
      
      if (!isset($course_history[$current_schedule_row['COURSE_ID']]))
        $course_history[$current_schedule_row['COURSE_ID']][] = $current_schedule_row;
    }
    return $course_history;
  }
  
  private function check_if_equiv_exists($equiv) {
    if (isset($this->course_history[$equiv])) {
      return $this->course_history[$equiv];
    }
  }
  
  private function requirements($degree_id = null, $major_id = null, $minor_id = null, $concentration_id = null) {
    
    $requirements = array();
    
    $db_or = $this->db->db_or();
    if ($degree_id) {
      $db_or = $db_or->condition('reqgrp.DEGREE_ID', $degree_id);
    }
    if ($major_id) {
      $db_or = $db_or->condition('reqgrp.MAJOR_ID', $major_id);
    }
    if ($minor_id) {
      $db_or = $db_or->condition('reqgrp.MINOR_ID', $minor_id);
    }
    if ($concentration_id) {
      $db_or = $db_or->condition('reqgrp.CONCENTRATION_ID', $concentration_id);
    }
    
    $requirements_result = $this->db->db_select('STUD_DEGREE_REQ_GRP', 'reqgrp')
      ->fields('reqgrp', array('DEGREE_ID', 'MAJOR_ID', 'MINOR_ID', 'CONCENTRATION_ID', 'DEGREE_REQ_GRP_ID', 'GROUP_NAME', 'ELECTIVE', 'CREDITS_REQUIRED'))
      ->leftJoin('STUD_DEGREE_REQ_GRP_CRS', 'reqgrpcrs', 'reqgrp.DEGREE_REQ_GRP_ID = reqgrpcrs.DEGREE_REQ_GRP_ID')
      ->fields('reqgrpcrs', array('REQUIRED', 'SHOW_AS_OPTION', 'DEGREE_REQ_GRP_CRS_ID'))
      ->leftJoin('STUD_COURSE', 'crs', 'crs.COURSE_ID = reqgrpcrs.COURSE_ID')
      ->fields('crs', array('COURSE_ID', 'COURSE_TITLE', 'COURSE_NUMBER', 'CREDITS'))
      ->leftJoin('STUD_DEGREE_REQ_GRP_CRS_EQUV', 'reqgrpcrsequv', 'reqgrpcrs.DEGREE_REQ_GRP_CRS_ID = reqgrpcrsequv.DEGREE_REQ_GRP_CRS_ID')
      ->fields('reqgrpcrsequv', array('COURSE_ID' => 'crsequiv_COURSE_ID'))
      ->condition($db_or)
      ->orderBy('reqgrp.DEGREE_ID', 'ASC')
      ->orderBy('reqgrp.MAJOR_ID', 'ASC')
      ->orderBy('reqgrp.MINOR_ID', 'ASC')
      ->orderBy('reqgrp.CONCENTRATION_ID', 'ASC')
      ->orderBy('reqgrp.GROUP_NAME', 'ASC')
      ->orderBy('reqgrpcrs.REQUIRED', 'DESC')
      ->orderBy('crs.COURSE_NUMBER', 'ASC')
      ->execute();
    while ($requirements_row = $requirements_result->fetch()) {
      if ($requirements_row['DEGREE_ID'] != '') {
        $requirement_type = 'degree'; $type_id = $requirements_row['DEGREE_ID'];
      } elseif ($requirements_row['MAJOR_ID'] != '') {
        $requirement_type = 'major'; $type_id = $requirements_row['MAJOR_ID'];
      } elseif ($requirements_row['MINOR_ID'] != '') {
        $requirement_type = 'minor'; $type_id = $requirements_row['MINOR_ID'];  
      } elseif ($requirements_row['CONCENTRATION_ID'] != '') {
        $requirement_type = 'concentration'; $type_id = $requirements_row['CONCENTRATION_ID'];  
      }
      
      $requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['GROUP_NAME'] = $requirements_row['GROUP_NAME'];
      $requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['ELECTIVE'] = $requirements_row['ELECTIVE'];
      $requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['CREDITS_REQUIRED'] = $requirements_row['CREDITS_REQUIRED'];

      if ($requirements_row['DEGREE_REQ_GRP_CRS_ID'] AND !isset($requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']])) {  

      $requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']] = 
        array('COURSE_TITLE' => $requirements_row['COURSE_TITLE'], 'COURSE_NUMBER' => $requirements_row['COURSE_NUMBER'], 'SHOW_AS_OPTION' => $requirements_row['SHOW_AS_OPTION'], 'REQUIRED' => $requirements_row['REQUIRED'], 'CREDITS' => $requirements_row['CREDITS'], 'COURSE_ID' => $requirements_row['COURSE_ID'], 'CREDITS_REQUIRED' => $requirements_row['CREDITS_REQUIRED']);
      }
      
      if ($requirements_row['crsequiv_COURSE_ID']) {
        if (!isset($requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']]['equivs']))
          $requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']]['equivs'] = array();
        array_push($requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']]['equivs'],  $requirements_row['crsequiv_COURSE_ID']);
      }
      
    }
    
    return $requirements;
  }
  
  public function getDegrees() {
    return $this->degrees;
  }
  
  public function getMajors() {
    return $this->majors;
  }
  
  public function getMinors() {
    return $this->minors;
  }
  
  public function getConcentrations() {
    return $this->concentrations;
  }
  
  public function getTotalDegreeNeeded() {
    return sprintf('%0.2f', round($this->total_degree_needed, 2, PHP_ROUND_HALF_UP));
  }
  
  public function getTotalDegreeCompleted() {
    return sprintf('%0.2f', round($this->total_degree_completed, 2, PHP_ROUND_HALF_UP));
  }
  
  public function getTotalDegreeRemaining() {
    return sprintf('%0.2f', round($this->total_degree_needed - $this->total_degree_completed, 2, PHP_ROUND_HALF_UP));
  }
  
}