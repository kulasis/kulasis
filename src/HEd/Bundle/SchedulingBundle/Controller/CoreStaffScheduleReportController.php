<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreStaffScheduleReportController extends ReportController {
  
  public function indexAction() {
    $this->authorize();
    if (($this->request->query->get('record_type') == 'Core.HEd.Staff' || $this->request->query->get('record_type') == 'Core.HEd.Staff.SchoolTerm') AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Staff.SchoolTerm');
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    return $this->render('KulaHEdSchedulingBundle:CoreStaffScheduleReport:reports_staffschedule.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $pdf = new \Kula\HEd\Bundle\SchedulingBundle\Report\StaffScheduleReport("P");
    $pdf->SetFillColor(245,245,245);
    $pdf->row_count = 0;
    
    // Get student addresses
    
    $meetings = array();
    // Get meeting data
    $meeting_result = $this->db()->db_select('STUD_SECTION', 'section')
      ->distinct(true)
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER'))
      ->join('STUD_SECTION_MEETINGS', 'meetings', 'meetings.SECTION_ID = section.SECTION_ID')
      ->fields('meetings', array('MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN', 'START_TIME', 'END_TIME'))
      ->join('STUD_STUDENT_CLASSES', 'class', 'class.SECTION_ID = section.SECTION_ID')
      ->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = meetings.ROOM_ID')
      ->fields('rooms', array('ROOM_NUMBER'));
    
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $meeting_result = $meeting_result->condition('section.ORGANIZATION_TERM_ID', $org_term_ids);
    $record_id = $this->request->request->get('record_id');    
    if (isset($record_id) AND $record_id != '')
      $meeting_result = $meeting_result->condition('section.STAFF_ORGANIZATION_TERM_ID', $record_id);
    $meeting_result = $meeting_result
      ->orderBy('SECTION_ID');
    $meeting_result = $meeting_result->execute();
    $i = 0;
    $section_id = 0;
    while ($meeting_row = $meeting_result->fetch()) {
      if ($section_id != $meeting_row['SECTION_ID']) $i = 0;
      $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] = '';
      if ($meeting_row['MON'] == '1') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'M';
      if ($meeting_row['TUE'] == '1') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'T';
      if ($meeting_row['WED'] == '1') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'W';
      if ($meeting_row['THU'] == '1') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'R';
      if ($meeting_row['FRI'] == '1') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'F';
      if ($meeting_row['SAT'] == '1') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'S';
      if ($meeting_row['SUN'] == '1') $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['meets'] .= 'U';
      $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['START_TIME'] = date('g:i A', strtotime($meeting_row['START_TIME']));
      $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['END_TIME'] = date('g:i A', strtotime($meeting_row['END_TIME']));
      $meetings[$meeting_row['SECTION_ID']]['meetings'][$i]['ROOM'] = $meeting_row['ROOM_NUMBER'];
      $i++;
      $section_id = $meeting_row['SECTION_ID'];
    }
    
    // Get Data and Load
    $result = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'CREDITS', 'STAFF_ORGANIZATION_TERM_ID'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_TITLE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
      ->join('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ;
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $result = $result->condition('stafforgterm.ORGANIZATION_TERM_ID', $org_term_ids);
    
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $result = $result->condition('section.STAFF_ORGANIZATION_TERM_ID', $record_id);

    $result = $result
      ->orderBy('staff.ABBREVIATED_NAME', 'ASC')
      ->orderBy('term.START_DATE', 'ASC')
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->orderBy('SECTION_ID', 'ASC')
      ->execute();
    
    $last_student_status_id = 0;
    $credit_total = 0;
    while ($row = $result->fetch()) {
      
      if (isset($meetings[$row['SECTION_ID']]))  {
        $row = array_merge($row, $meetings[$row['SECTION_ID']]);
      }
      if ($last_student_status_id != $row['STAFF_ORGANIZATION_TERM_ID']) {
        $pdf->setData($row);
        $pdf->row_count = 1;
        $credit_total = 0;
        $pdf->StartPageGroup();
        $pdf->AddPage();
      }
      
      $pdf->table_row($row);
      $credit_total += $row['CREDITS'];
      $last_student_status_id = $row['STAFF_ORGANIZATION_TERM_ID'];
      $pdf->row_count++;
      $pdf->row_page_count++;
      $pdf->row_total_count++;
    }
    
    // Closing line
    return $this->pdfResponse($pdf->Output('','S'));
  
  }
}