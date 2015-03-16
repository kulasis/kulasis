<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class SISDegreeAuditReportController extends ReportController {
  
  public $pdf;
  
  public function indexAction() {
    $this->authorize();
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'SIS.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('SIS.HEd.Student');
    return $this->render('KulaHEdGradingBundle:SISDegreeAuditReport:reports_degreeaudit.html.twig');
  }
  
  public function generateAction() {  
    $this->authorize();
    
    $this->pdf = new \Kula\HEd\Bundle\GradingBundle\Report\DegreeAuditReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;
    
    // Load requirements
    $this->requirements();
    
    // Get students
    $result = $this->db()->db_select('STUD_STUDENT', 'stu')
      ->fields('stu', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stu.STUDENT_ID')
      ->fields('cons', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_ID = stu.STUDENT_ID')
      ->fields('status', array('LEVEL', 'STUDENT_STATUS_ID'))
      ->join('CORE_LOOKUP_VALUES', 'grade_values', "grade_values.CODE = status.GRADE AND grade_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Grade')")
      ->fields('grade_values', array('DESCRIPTION' => 'GRADE'))
      ->join('STUD_STUDENT_DEGREES', 'studeg', 'studeg.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->fields('studeg', array('STUDENT_DEGREE_ID', 'EXPECTED_GRADUATION_DATE'))
      ->join('STUD_DEGREE', 'deg', 'deg.DEGREE_ID = studeg.DEGREE_ID')
      ->fields('deg', array('DEGREE_ID', 'DEGREE_NAME'))
      ->leftJoin('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentration', 'stuconcentration.STUDENT_DEGREE_ID = studeg.STUDENT_DEGREE_ID')
      ->leftJoin('STUD_DEGREE_CONCENTRATION', 'degconcentration', 'stuconcentration.CONCENTRATION_ID = degconcentration.CONCENTRATION_ID')
      ->fields('degconcentration', array('CONCENTRATION_NAME'))
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = studeg.EXPECTED_COMPLETION_TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION' => 'expected_completion_term'));
    
    if ($this->request->request->get('sort') == 'degree_name') {
      $result = $result->orderBy('deg.DEGREE_NAME', 'ASC');
    } elseif ($this->request->request->get('sort') == 'degree_concentration_name') {
      $result = $result->orderBy('deg.DEGREE_NAME', 'ASC');
      $result = $result->orderBy('degconcentration.CONCENTRATION_NAME', 'ASC');
    }
    
    $result = $result
      ->orderBy('cons.LAST_NAME', 'ASC')
      ->orderBy('cons.FIRST_NAME', 'ASC');
      
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->condition('status.ORGANIZATION_TERM_ID', $org_term_ids);
    }
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $result = $result->condition('stu.STUDENT_ID', $record_id);
    
    $result = $result->execute();
    while ($students_row = $result->fetch()) {
      // Add Page
      $this->page($students_row);
    }
    
    // Closing line
    return $this->pdfResponse($this->pdf->Output('','S'));
  }
  
  public function page($row) {
    
    // Get Majors
    $row['majors'] = array();
    $majors_result = $this->db()->db_select('STUD_STUDENT_DEGREES_MAJORS', 'studmajor')
      ->fields('studmajor', array('MAJOR_ID'))
      ->join('STUD_DEGREE_MAJOR', 'major', 'studmajor.MAJOR_ID = major.MAJOR_ID')
      ->fields('major', array('MAJOR_NAME'))
      ->condition('studmajor.STUDENT_DEGREE_ID', $row['STUDENT_DEGREE_ID'])
      ->orderBy('major.MAJOR_NAME', 'ASC')
      ->execute();
    while ($majors_row = $majors_result->fetch()) {
      $row['majors'][] = $majors_row['MAJOR_NAME'];
      $row['major_ids'][] = $majors_row['MAJOR_ID'];
    }
    
    // Get Minors
    $row['minors'] = array();
    $minors_result = $this->db()->db_select('STUD_STUDENT_DEGREES_MINORS', 'studminor')
      ->fields('studminor', array('MINOR_ID'))
      ->join('STUD_DEGREE_MINOR', 'minor', 'studminor.MINOR_ID = minor.MINOR_ID')
      ->fields('minor', array('MINOR_NAME'))
      ->condition('studminor.STUDENT_DEGREE_ID', $row['STUDENT_DEGREE_ID'])
      ->orderBy('minor.MINOR_NAME', 'ASC')
      ->execute();
    while ($minors_row = $minors_result->fetch()) {
      $row['minors'][] = $minors_row['MINOR_NAME'];
      $row['minor_ids'][] = $minors_row['MINOR_ID'];
    }
    
    // Get Concentrations
    $row['concentrations'] = array();
    $concentrations_result = $this->db()->db_select('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'studconcentration')
      ->fields('studconcentration', array('CONCENTRATION_ID'))
      ->join('STUD_DEGREE_CONCENTRATION', 'concentration', 'studconcentration.CONCENTRATION_ID = concentration.CONCENTRATION_ID')
      ->fields('concentration', array('CONCENTRATION_NAME'))
      ->condition('studconcentration.STUDENT_DEGREE_ID', $row['STUDENT_DEGREE_ID'])
      ->orderBy('concentration.CONCENTRATION_NAME', 'ASC')
      ->execute();
    while ($concentrations_row = $concentrations_result->fetch()) {
      $row['concentrations'][] = $concentrations_row['CONCENTRATION_NAME'];
      $row['concentration_ids'][] = $concentrations_row['CONCENTRATION_ID'];
    }
    
    $this->pdf->setData($row);
    $this->pdf->row_count = 1;
    $this->pdf->pageNum = 1;
    $this->pdf->pageTotal = 1;
    $this->pdf->course_history_data = null; $this->pdf->req_grp_totals = null; $this->pdf->total_degree_needed = null; $this->pdf->total_degree_completed = null;
    $this->pdf->StartPageGroup();
    $this->pdf->AddPage();
    
    $this->pdf->course_history_data = $this->getCourseHistoryForStudent($row['STUDENT_ID'], $row['LEVEL'], $row['STUDENT_STATUS_ID']);
    
    // Get Degree Requirements but not requirement marked as elective
    $elective_req_id = null;
    if (isset($this->pdf->requirements['degree'][$row['DEGREE_ID']])) {
    foreach($this->pdf->requirements['degree'][$row['DEGREE_ID']] as $req_id => $req_row) {
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
    foreach($this->pdf->requirements['minor'][$minor_id] as $req_id => $req_row) {
      $this->outputRequirementSet($req_id, $req_row);
    }
    }
    }
    
    // Get Concentration Requirements
    if (isset($row['concentration_ids'])) {
    foreach($row['concentration_ids'] as $concentration_id) {
    foreach($this->pdf->requirements['concentration'][$concentration_id] as $req_id => $req_row) {
      $this->outputRequirementSet($req_id, $req_row);
    }
    }
    }
    
    // Output elective
    // Must be on end to look at everything not already applied
    if (isset($this->pdf->requirements['degree'][$row['DEGREE_ID']][$elective_req_id]))
    $this->outputRequirementSet($elective_req_id, $this->pdf->requirements['degree'][$row['DEGREE_ID']][$elective_req_id], 'Y');

    // Loop through each section in requirements
    $this->pdf->degree_footer_row();
  }
  
  private function outputRequirementSet($req_id, $req_row, $elective = null) {
    // Check how far from bottom
    $current_y = $this->pdf->GetY();
    if (270 - $current_y < 30) {
      $this->pdf->Ln(270 - $current_y);
    }
    
    // Output title
    $this->pdf->req_grp_header_row($req_id, $req_row);
    // Loop through courses
    if ($elective ) { // AND isset($this->pdf->course_history_data['elective'])
      if (isset($req_row['courses'])) {
      foreach($req_row['courses'] as $req_crs_id => $req_crs_row) {
        $this->pdf->req_grp_row($req_id, $req_crs_row);
      }
      }
      foreach($this->pdf->course_history_data as $course_id => $row) {
        foreach($row as $row_id => $row_data) {
        if (!isset($row_data['used']) AND $course_id != 'elective') {
          $this->pdf->req_grp_row($req_id, $row, 'Y');
        }
        }
      }
    } else {
      if (isset($req_row['courses'])) {
      foreach($req_row['courses'] as $req_crs_id => $req_crs_row) {
        $this->pdf->req_grp_row($req_id, $req_crs_row);
      }
      }
      foreach($this->pdf->course_history_data as $course_id => $row) {
        foreach($row as $row_id => $row_data) {
        if (!isset($row_data['used']) AND $course_id != 'elective' AND $req_id == $row_data['DEGREE_REQ_GRP_ID']) {
          $this->pdf->req_grp_row($req_id, $row, 'Y');
        }
        }
      }
    }
    $this->pdf->req_grp_footer_row($req_id, $req_row);  
  }
  
  private function getCourseHistoryForStudent($student_id, $level, $student_status_id) {
    
    $course_history = array();
    $course_history_result = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY', 'ch')
      ->fields('ch', array('COURSE_ID', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'MARK', 'TERM', 'COURSE_NUMBER', 'COURSE_TITLE', 'DEGREE_REQ_GRP_ID'))
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
    $current_schedule_result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
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
      }
      
      if (!isset($course_history[$current_schedule_row['COURSE_ID']]))
        $course_history[$current_schedule_row['COURSE_ID']][] = $current_schedule_row;
    }

    return $course_history;
  }
  
  private function requirements() {
    
    $requirements_result = $this->db()->db_select('STUD_DEGREE_REQ_GRP', 'reqgrp')
      ->fields('reqgrp', array('DEGREE_ID', 'MAJOR_ID', 'MINOR_ID', 'CONCENTRATION_ID', 'DEGREE_REQ_GRP_ID', 'GROUP_NAME', 'ELECTIVE', 'CREDITS_REQUIRED'))
      ->leftJoin('STUD_DEGREE_REQ_GRP_CRS', 'reqgrpcrs', 'reqgrp.DEGREE_REQ_GRP_ID = reqgrpcrs.DEGREE_REQ_GRP_ID')
      ->fields('reqgrpcrs', array('REQUIRED', 'SHOW_AS_OPTION', 'DEGREE_REQ_GRP_CRS_ID'))
      ->leftJoin('STUD_COURSE', 'crs', 'crs.COURSE_ID = reqgrpcrs.COURSE_ID')
      ->fields('crs', array('COURSE_ID', 'COURSE_TITLE', 'COURSE_NUMBER', 'CREDITS'))
      ->leftJoin('STUD_DEGREE_REQ_GRP_CRS_EQUV', 'reqgrpcrsequv', 'reqgrpcrs.DEGREE_REQ_GRP_CRS_ID = reqgrpcrsequv.DEGREE_REQ_GRP_CRS_ID')
      ->fields('reqgrpcrsequv', array('COURSE_ID' => 'crsequiv_COURSE_ID'))
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
      
      $this->pdf->requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['GROUP_NAME'] = $requirements_row['GROUP_NAME'];
      $this->pdf->requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['ELECTIVE'] = $requirements_row['ELECTIVE'];
      $this->pdf->requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['CREDITS_REQUIRED'] = $requirements_row['CREDITS_REQUIRED'];

      if ($requirements_row['DEGREE_REQ_GRP_CRS_ID'] AND !isset($this->pdf->requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']])) {  

      $this->pdf->requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']] = 
        array('COURSE_TITLE' => $requirements_row['COURSE_TITLE'], 'COURSE_NUMBER' => $requirements_row['COURSE_NUMBER'], 'SHOW_AS_OPTION' => $requirements_row['SHOW_AS_OPTION'], 'REQUIRED' => $requirements_row['REQUIRED'], 'CREDITS' => $requirements_row['CREDITS'], 'COURSE_ID' => $requirements_row['COURSE_ID'], 'CREDITS_REQUIRED' => $requirements_row['CREDITS_REQUIRED']);
      }
      
      if ($requirements_row['crsequiv_COURSE_ID']) {
        if (!isset($this->pdf->requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']]['equivs']))
          $this->pdf->requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']]['equivs'] = array();
        array_push($this->pdf->requirements[$requirement_type][$type_id][$requirements_row['DEGREE_REQ_GRP_ID']]['courses'][$requirements_row['DEGREE_REQ_GRP_CRS_ID']]['equivs'],  $requirements_row['crsequiv_COURSE_ID']);
      }
      
    }
  }
}