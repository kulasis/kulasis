<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreFinalGradesReportController extends ReportController {
  
  private $pdf;
  
  public function indexAction() {
    $this->authorize();
    if ($this->request->query->get('record_type') == 'Core.HEd.Student.Status' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Student.Status');
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    return $this->render('KulaHEdGradingBundle:CoreFinalGradesReport:reports_finalgradesreport.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $this->pdf = new \Kula\HEd\Bundle\GradingBundle\Report\FinalGradesReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;
    
    // Get Data and Load
    $result = $this->db()->db_select('STUD_STUDENT', 'student')
      ->fields('student', array('STUDENT_ID'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_ID = student.STUDENT_ID')
      ->fields('status', array('STUDENT_STATUS_ID', 'LEVEL' => 'status_LEVEL'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'grvalue', "grvalue.CODE = status.GRADE AND grvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Grade')")
      ->fields('grvalue', array('DESCRIPTION' => 'GRADE'))
      ->leftJoin('STUD_STUDENT_DEGREES', 'studdegrees', 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->leftJoin('STUD_DEGREE', 'degree', 'studdegrees.DEGREE_ID = degree.DEGREE_ID')
      ->fields('degree', array('DEGREE_NAME'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'advisororgterm', 'advisororgterm.STAFF_ORGANIZATION_TERM_ID = status.ADVISOR_ID')
      ->leftJoin('STUD_STAFF', 'advisor', 'advisor.STAFF_ID = advisororgterm.STAFF_ID')
      ->fields('advisor', array('ABBREVIATED_NAME' => 'advisor_abbreviated_name'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->leftJoin('CONS_ADDRESS', 'home_address', 'home_address.CONSTITUENT_ID = stucon.CONSTITUENT_ID AND home_address.UNDELIVERABLE = \'0\' AND home_address.SEND_GRADES = \'1\'')
      ->fields('home_address', array('THOROUGHFARE' => 'home_ADDRESS', 'LOCALITY' => 'home_CITY', 'ADMINISTRATIVE_AREA' => 'home_STATE', 'POSTAL_CODE' => 'home_ZIPCODE'))
      ->leftJoin('CONS_PHONE', 'phone', 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
      ->fields('phone', array('PHONE_NUMBER'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ;
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $result = $result->condition('status.ORGANIZATION_TERM_ID', $org_term_ids);
    
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $result = $result->condition('status.STUDENT_STATUS_ID', $record_id);

    $result = $result
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC');

    $result = $result
      ->execute();
    
    $last_student_status_id = 0;
    $last_student_id = 0;
    $totals = array();
    $levels_for_cum = array();
    while ($row = $result->fetch()) {
      
        $row['address']['address'] = $row['home_ADDRESS'];
        $row['address']['city'] = $row['home_CITY'];
        $row['address']['state'] = $row['home_STATE'];
        $row['address']['zipcode'] = $row['home_ZIPCODE']; 

      $this->createGradeReport($row['STUDENT_STATUS_ID'], $row['STUDENT_ID'], $row);
      
    }
    
    
    // Closing line
    return $this->pdfResponse($this->pdf->Output('','S'));
  
  }
  
  public function createGradeReport($student_status_id, $student_id, $data) {
    
    if (!isset($levels_for_cum) OR (isset($levels_for_cum) AND count($levels_for_cum) == 0))
      $levels_for_cum[] = $data['status_LEVEL'];
    $this->pdf->setData($data);
    $this->pdf->row_count = 1;
    
    $totals = array();
    $levels_for_cum = array();
    
    $this->pdf->StartPageGroup();
    $this->pdf->AddPage();
    
    $grades = $this->getGrades($student_status_id);
    
    foreach($grades as $row) {
      $this->pdf->table_row($row);
    
      if (!isset($totals[$row['LEVEL']]['TERM'])) {
        $totals[$row['LEVEL']]['TERM'] = array(
          'ATT' => 0, 'ERN' => 0, 'HRS' => 0, 'PTS' => 0
        );
      }
      
      $totals[$row['LEVEL']]['TERM']['ATT'] += $row['CREDITS_ATTEMPTED'];
      $totals[$row['LEVEL']]['TERM']['ERN'] += $row['CREDITS_EARNED'];
      if ($row['GPA_VALUE'] != '')
        $totals[$row['LEVEL']]['TERM']['HRS'] += $row['CREDITS_ATTEMPTED'];
      $totals[$row['LEVEL']]['TERM']['PTS'] += $row['QUALITY_POINTS'];
      
      $levels_for_cum[] = $row['LEVEL'];
      $this->pdf->row_count++;
      $this->pdf->row_page_count++;
      $this->pdf->row_total_count++;
    }

    $this->getCumulativeTotals($student_id, $levels_for_cum, $totals);
    $this->pdf->gpa_table_row($totals);
  }
  
  public function getGrades($student_status_id) {

    // Get Data and Load
    $result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class')
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID', 'LEVEL' => 'status_LEVEL'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'grvalue', "grvalue.CODE = status.GRADE AND grvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Grade')")
      ->fields('grvalue', array('DESCRIPTION' => 'GRADE'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'stucrshis', 'stucrshis.STUDENT_CLASS_ID = class.STUDENT_CLASS_ID')
      ->fields('stucrshis', array('LEVEL', 'MARK', 'GPA_VALUE', 'QUALITY_POINTS', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'COURSE_NUMBER', 'COURSE_TITLE', 'COMMENTS'))
      ->leftJoin('STUD_STUDENT_DEGREES', 'studdegrees', 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->leftJoin('STUD_DEGREE', 'degree', 'studdegrees.DEGREE_ID = degree.DEGREE_ID')
      ->fields('degree', array('DEGREE_NAME'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'advisororgterm', 'advisororgterm.STAFF_ORGANIZATION_TERM_ID = status.ADVISOR_ID')
      ->leftJoin('STUD_STAFF', 'advisor', 'advisor.STAFF_ID = advisororgterm.STAFF_ID')
      ->fields('advisor', array('ABBREVIATED_NAME' => 'advisor_abbreviated_name'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = class.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_TITLE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->condition('class.DROPPED', 0)
      ->condition('status.STUDENT_STATUS_ID', $student_status_id)
      ;
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $result = $result->condition('status.ORGANIZATION_TERM_ID', $org_term_ids);
    
    $result = $result->orderBy('term.START_DATE', 'ASC')
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->orderBy('section.SECTION_ID', 'ASC');

    $result = $result
      ->execute();
    
    return $result->fetchAll();
  }
  
  public function getCumulativeTotals($student_id, $levels_for_cum, &$totals) {
    
    $cum_result = $this->db()->db_select('STUD_STUDENT_COURSE_HISTORY', 'crshis')
      ->fields('crshis', array('NON_ORGANIZATION_ID', 'LEVEL', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED', 'GPA_VALUE', 'QUALITY_POINTS', 'CREDITS_ATTEMPTED', 'CREDITS_EARNED'))
      ->condition('STUDENT_ID', $student_id);
    if (count($levels_for_cum)) {
      $cum_result = $cum_result->condition('LEVEL', $levels_for_cum);
    }
    $cum_result = $cum_result->execute();
  
    //$totals['']['CUM'] = array('ATT' => 0, 'ERN' => 0, 'HRS' => 0, 'PTS' => 0);
    
    while ($cum_row = $cum_result->fetch()) {
      
      if (!isset($totals[$cum_row['LEVEL']]['CUM'])) {
        $totals[$cum_row['LEVEL']]['CUM'] = array('ATT' => 0, 'ERN' => 0, 'HRS' => 0, 'PTS' => 0);
      }
      
      $totals[$cum_row['LEVEL']]['CUM']['ATT'] += $cum_row['CREDITS_ATTEMPTED'];
      $totals[$cum_row['LEVEL']]['CUM']['ERN'] += $cum_row['CREDITS_EARNED'];
      if ($cum_row['GPA_VALUE'] != '' AND !$cum_row['NON_ORGANIZATION_ID'])
        $totals[$cum_row['LEVEL']]['CUM']['HRS'] += $cum_row['CREDITS_ATTEMPTED'];
      $totals[$cum_row['LEVEL']]['CUM']['PTS'] += $cum_row['QUALITY_POINTS'];
      
    }
  }
}