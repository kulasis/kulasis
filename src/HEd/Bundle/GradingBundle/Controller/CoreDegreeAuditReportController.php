<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreDegreeAuditReportController extends ReportController {
  
  public $pdf;
  
  public function indexAction() {
    $this->authorize();

    $lookup_service = $this->get('kula.core.lookup');
    $levels = $lookup_service->getLookupMenu('HEd.Student.Enrollment.Level', 'D');
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'Core.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Student');
    return $this->render('KulaHEdGradingBundle:CoreDegreeAuditReport:reports_degreeaudit.html.twig', array('levels' => $levels));
  }
  
  public function generateAction() {  
    $this->authorize();
    
    $this->pdf = new \Kula\HEd\Bundle\GradingBundle\Report\DegreeAuditReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;
    
    // Load service
    $this->service = $this->get('kula.HEd.grading.degreeaudit');
    
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
    
    $non = $this->request->request->get('non');
    if (isset($non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level']) AND $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level'] != '') {
      $result = $result->condition('status.LEVEL', $non['HEd.Student.CourseHistory']['HEd.Student.CourseHistory.Level']);
    }
    
    $result = $result->execute();
    while ($students_row = $result->fetch()) {
      // Add Page
      $this->page($students_row);
    }
    
    // Closing line
    return $this->pdfResponse($this->pdf->Output('','S'));
  }
  
  public function page($row) {
    
    // Get degree audit
    $degree_audit = $this->service->getDegreeAuditForStudentStatus($row['STUDENT_STATUS_ID']);
    $row['degrees'] = (count($this->service->getDegrees()) > 0) ? implode(', ', $this->service->getDegrees()) : '';
    $row['areas'] = $this->service->getAreas();
    $this->pdf->total_degree_needed = $this->service->getTotalDegreeNeeded();
    $this->pdf->total_degree_completed = $this->service->getTotalDegreeCompleted();
    $this->pdf->total_degree_remaining = $this->service->getTotalDegreeRemaining();
    
    $this->pdf->setData($row);
    $this->pdf->row_count = 1;
    $this->pdf->pageNum = 1;
    $this->pdf->pageTotal = 1;
    $this->pdf->StartPageGroup();
    $this->pdf->AddPage();
    
    // loop through requirement groups
    foreach($degree_audit as $reqgrp_id => $reqgrp) {
      
      // Check how far from bottom
      $current_y = $this->pdf->GetY();
      if (270 - $current_y < 30) {
        $this->pdf->Ln(270 - $current_y);
      }
      
      $this->pdf->req_grp_header_row($reqgrp_id, $reqgrp);
      
      // loop through requirement row
      foreach($reqgrp['courses'] as $reqgrpcrs_id => $reqgrpcrs) { 
        
        $this->pdf->req_grp_row($reqgrpcrs_id, $reqgrpcrs);
        
      } // end foreach on audit line
      
      $this->pdf->req_grp_footer_row($reqgrp_id, $reqgrp);
    } // end foreach on requirement groups

    // Loop through each section in requirements
    $this->pdf->degree_footer_row();
  }
  
}