<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class SISDegreeAuditReportController extends ReportController {
  
  public $pdf;
  
  public function indexAction() {
    $this->authorize();
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'STUDENT' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('STUDENT');
    return $this->render('KulaHEdCourseHistoryBundle:DegreeAuditReport:reports_degreeaudit.html.twig');
  }
  
  public function generateAction() {  
    $this->authorize();
    
    $this->pdf = new \Kula\Bundle\HEd\CourseHistoryBundle\Controller\DegreeAuditReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;
    
    // Load requirements
    $this->requirements();
    
    // Get students
    $result = $this->db()->select('STUD_STUDENT', 'stu')
      ->fields('stu', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'cons', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'), 'cons.CONSTITUENT_ID = stu.STUDENT_ID')
      ->join('STUD_STUDENT_STATUS', 'status', array('LEVEL', 'STUDENT_STATUS_ID'), 'status.STUDENT_ID = stu.STUDENT_ID')
      ->join('CORE_LOOKUP_VALUES', 'grade_values', array('DESCRIPTION' => 'GRADE'), 'grade_values.CODE = status.GRADE AND grade_values.LOOKUP_ID = 20')
      ->join('STUD_STUDENT_DEGREES', 'studeg', array('STUDENT_DEGREE_ID', 'EXPECTED_GRADUATION_DATE'), 'studeg.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->join('STUD_DEGREE', 'deg', array('DEGREE_ID', 'DEGREE_NAME'), 'deg.DEGREE_ID = studeg.DEGREE_ID')
      ->left_join('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'stuconcentration', null, 'stuconcentration.STUDENT_DEGREE_ID = studeg.STUDENT_DEGREE_ID')
      ->left_join('STUD_DEGREE_CONCENTRATION', 'degconcentration', array('CONCENTRATION_NAME'), 'stuconcentration.CONCENTRATION_ID = degconcentration.CONCENTRATION_ID')
      ->left_join('CORE_TERM', 'term', array('TERM_ABBREVIATION' => 'expected_completion_term'), 'term.TERM_ID = studeg.EXPECTED_COMPLETION_TERM_ID');
    
    if ($this->request->request->get('sort') == 'degree_name') {
      $result = $result->order_by('DEGREE_NAME', 'ASC', 'deg');
    } elseif ($this->request->request->get('sort') == 'degree_concentration_name') {
      $result = $result->order_by('DEGREE_NAME', 'ASC', 'deg');
      $result = $result->order_by('CONCENTRATION_NAME', 'ASC', 'degconcentration');
    }
    
    $result = $result
      ->order_by('LAST_NAME', 'ASC', 'cons')
      ->order_by('FIRST_NAME', 'ASC', 'cons');
      
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0) {
      $result = $result->predicate('status.ORGANIZATION_TERM_ID', $org_term_ids);
    }
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $result = $result->predicate('stu.STUDENT_ID', $record_id);
    
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
    $majors_result = $this->db()->select('STUD_STUDENT_DEGREES_MAJORS', 'studmajor')
      ->fields('studmajor', array('MAJOR_ID'))
      ->join('STUD_DEGREE_MAJOR', 'major', array('MAJOR_NAME'), 'studmajor.MAJOR_ID = major.MAJOR_ID')
      ->predicate('studmajor.STUDENT_DEGREE_ID', $row['STUDENT_DEGREE_ID'])
      ->order_by('MAJOR_NAME', 'ASC', 'major')
      ->execute();
    while ($majors_row = $majors_result->fetch()) {
      $row['majors'][] = $majors_row['MAJOR_NAME'];
      $row['major_ids'][] = $majors_row['MAJOR_ID'];
    }
    
    // Get Minors
    $row['minors'] = array();
    $minors_result = $this->db()->select('STUD_STUDENT_DEGREES_MINORS', 'studminor')
      ->fields('studminor', array('MINOR_ID'))
      ->join('STUD_DEGREE_MINOR', 'minor', array('MINOR_NAME'), 'studminor.MINOR_ID = minor.MINOR_ID')
      ->predicate('studminor.STUDENT_DEGREE_ID', $row['STUDENT_DEGREE_ID'])
      ->order_by('MINOR_NAME', 'ASC', 'minor')
      ->execute();
    while ($minors_row = $minors_result->fetch()) {
      $row['minors'][] = $minors_row['MINOR_NAME'];
      $row['minor_ids'][] = $minors_row['MINOR_ID'];
    }
    
    // Get Concentrations
    $row['concentrations'] = array();
    $concentrations_result = $this->db()->select('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'studconcentration')
      ->fields('studconcentration', array('CONCENTRATION_ID'))
      ->join('STUD_DEGREE_CONCENTRATION', 'concentration', array('CONCENTRATION_NAME'), 'studconcentration.CONCENTRATION_ID = concentration.CONCENTRATION_ID')
      ->predicate('studconcentration.STUDENT_DEGREE_ID', $row['STUDENT_DEGREE_ID'])
      ->order_by('CONCENTRATION_NAME', 'ASC', 'concentration')
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
      if ($req_row['ELECTIVE'] == 'Y') {
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
    }
    $this->pdf->req_grp_footer_row($req_id, $req_row);  
  }
  
  private function getCourseHistoryForStudent($student_id, $level, $student_status_id) {
    
    $course_history = array();
    $course_history_result = $this->db()->select('STUD_STUDENT_COURSE_HISTORY', 'ch')
      ->fields('ch', array('COURSE_ID', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'MARK', 'TERM', 'COURSE_NUMBER', 'COURSE_TITLE', 'DEGREE_REQ_GRP_ID'))
      ->expressions(array("CONCAT(LEFT(TERM, 2),'-', RIGHT(TERM, 2))" => 'TERM_ABBREVIATION'))
      ->predicate('STUDENT_ID', $student_id)
      ->predicate('LEVEL', $level)
      ->predicate('MARK', 'W', '!=')
      ->execute();
    while ($course_history_row = $course_history_result->fetch()) {
      if ($course_history_row['COURSE_ID'] != '')
        $course_history[$course_history_row['COURSE_ID']][] = $course_history_row;
      else
        $course_history['elective'][] = $course_history_row;
    }

    $query_conditions = new \Kula\Component\Database\Query\Predicate('OR');
    $query_conditions = $query_conditions->predicate('DROPPED', null);
    $query_conditions = $query_conditions->predicate('DROPPED', 'N');
    
    $current_schedule = array();
    $current_schedule_result = $this->db()->select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_CLASS_ID', 'DEGREE_REQ_GRP_ID'))
      ->join('STUD_STUDENT_STATUS', 'status', array(), 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->join('STUD_SECTION', 'section', array(), 'section.SECTION_ID = class.SECTION_ID')
      ->join('STUD_COURSE', 'course', array('COURSE_NUMBER', 'COURSE_TITLE', 'CREDITS', 'COURSE_ID'), 'course.COURSE_ID = section.COURSE_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
      ->left_join('STUD_STUDENT_COURSE_HISTORY', 'stucrshis', array(), 'stucrshis.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->predicate('status.STUDENT_ID', $student_id)
      ->predicate($query_conditions)
      ->predicate('stucrshis.COURSE_HISTORY_ID', null)
      ->execute();
    while ($current_schedule_row = $current_schedule_result->fetch()) {
      if (!isset($course_history[$current_schedule_row['COURSE_ID']]))
        $course_history[$current_schedule_row['COURSE_ID']][] = $current_schedule_row;
    }

    return $course_history;
  }
  
  private function requirements() {
    
    $requirements_result = $this->db()->select('STUD_DEGREE_REQ_GRP', 'reqgrp')
      ->fields('reqgrp', array('DEGREE_ID', 'MAJOR_ID', 'MINOR_ID', 'CONCENTRATION_ID', 'DEGREE_REQ_GRP_ID', 'GROUP_NAME', 'ELECTIVE', 'CREDITS_REQUIRED'))
      ->left_join('STUD_DEGREE_REQ_GRP_CRS', 'reqgrpcrs', array('REQUIRED', 'SHOW_AS_OPTION', 'DEGREE_REQ_GRP_CRS_ID'), 'reqgrp.DEGREE_REQ_GRP_ID = reqgrpcrs.DEGREE_REQ_GRP_ID')
      ->left_join('STUD_COURSE', 'crs', array('COURSE_ID', 'COURSE_TITLE', 'COURSE_NUMBER', 'CREDITS'), 'crs.COURSE_ID = reqgrpcrs.COURSE_ID')
      ->left_join('STUD_DEGREE_REQ_GRP_CRS_EQUV', 'reqgrpcrsequv', array('COURSE_ID' => 'crsequiv_COURSE_ID'), 'reqgrpcrs.DEGREE_REQ_GRP_CRS_ID = reqgrpcrsequv.DEGREE_REQ_GRP_CRS_ID')
      ->order_by('DEGREE_ID', 'ASC', 'reqgrp')
      ->order_by('MAJOR_ID', 'ASC', 'reqgrp')
      ->order_by('MINOR_ID', 'ASC', 'reqgrp')
      ->order_by('CONCENTRATION_ID', 'ASC', 'reqgrp')
      ->order_by('GROUP_NAME', 'ASC', 'reqgrp')
      ->order_by('REQUIRED', 'DESC', 'reqgrpcrs')
      ->order_by('COURSE_NUMBER', 'ASC', 'crs')
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