<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class SISEnrollmentVerificationReportController extends ReportController {
  
  public function indexAction() {
    $this->authorize();
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    if ($this->request->query->get('record_type') == 'SIS.HEd.Student' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('SIS.HEd.Student');
    return $this->render('KulaHEdStudentBundle:SISEnrollmentVerificationReport:reports_enrollmentverification.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $pdf = new \Kula\HEd\Bundle\StudentBundle\Report\EnrollmentVerificationReport("P");
    $pdf->SetFillColor(245,245,245);
    $pdf->row_count = 0;

    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    $record_type = $this->request->request->get('record_type');
    $credit_totals_array = array();
    
    // Credit Totals for Term
    $credit_totals = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('STUDENT_STATUS_ID'))
      ->expression('SUM(CREDITS_ATTEMPTED)', 'total_credits_attempted')
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->condition($this->db()->db_or()->condition('class.DROPPED', null)->condition('class.DROPPED', 0))
      ->groupBy('STUDENT_STATUS_ID', 'class');
      if (isset($record_id) AND $record_id != '' AND $record_type == 'SIS.HEd.Student')
        $credit_totals = $credit_totals->condition('status.STUDENT_ID', $record_id);
    $credit_totals = $credit_totals->execute();
    while ($credit_total_row = $credit_totals->fetch()) {
      $credit_totals_array[$credit_total_row['STUDENT_STATUS_ID']] = $credit_total_row['total_credits_attempted'];
    }
    
    // Get Data and Load
    $result = $this->db()->db_select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('STUDENT_ID', 'STUDENT_STATUS_ID', 'FTE', 'ENTER_DATE', 'LEAVE_DATE'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION', 'START_DATE', 'END_DATE'))
      ->leftJoin('STUD_STUDENT_DEGREES', 'studdegrees', 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->fields('studdegrees', array('EXPECTED_GRADUATION_DATE'))
      ->leftJoin('STUD_DEGREE', 'degree', 'degree.DEGREE_ID = studdegrees.DEGREE_ID')
      ->fields('degree', array('DEGREE_NAME'))
      ->leftJoin('STUD_SCHOOL_TERM_LEVEL', 'schooltermlevel', 'schooltermlevel.LEVEL = status.LEVEL AND schooltermlevel.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->fields('schooltermlevel', array('MIN_FULL_TIME_HOURS'));
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $result = $result->condition('status.ORGANIZATION_TERM_ID', $org_term_ids);
    
    // Add on selected record
    if (isset($record_id) AND $record_id != '' AND $record_type == 'SIS.HEd.Student')
      $result = $result->condition('status.STUDENT_ID', $record_id);
    
    $result = $result
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->orderBy('student.STUDENT_ID', 'ASC')
      ->orderBy('term.START_DATE', 'DESC')
      ->execute();
    
    $last_student_id = 0;
    $credit_total = 0;
    while ($row = $result->fetch()) {
      if ($last_student_id != $row['STUDENT_ID']) {
        if ($last_student_id !== 0) $pdf->bottom_content();
        $pdf->setData($row);
        $pdf->row_count = 1;
        $pdf->AddPage();
        $pdf->content();
      }
      
      if (isset($credit_totals_array[$row['STUDENT_STATUS_ID']])) {
        $row = array_merge($row, array('credits_attempted' => $credit_totals_array[$row['STUDENT_STATUS_ID']]));
      } else {
        $row = array_merge($row, array('credits_attempted' => ''));  
      }
      
      $pdf->table_row($row);
      $last_student_id = $row['STUDENT_ID'];
      $pdf->row_count++;
      $pdf->row_page_count++;
      $pdf->row_total_count++;
    }
    
    $pdf->bottom_content();
    // Closing line
    return $this->pdfResponse($pdf->Output('','S'));
  
  }
}