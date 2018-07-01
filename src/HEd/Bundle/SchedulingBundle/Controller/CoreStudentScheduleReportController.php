<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreStudentScheduleReportController extends ReportController {
  
  public function indexAction() {
    $this->authorize();
    $this->formAction('Core_HEd_Scheduling_Reports_StudentSchedule_Generate');
    if ($this->request->query->get('record_type') == 'Core.HEd.Student.Status' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.HEd.Student.Status');
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    return $this->render('KulaHEdSchedulingBundle:CoreStudentScheduleReport:reports_studentschedule.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $pdf = new \Kula\HEd\Bundle\SchedulingBundle\Report\StudentScheduleReport("P");
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
      $meeting_result = $meeting_result->condition('class.STUDENT_STATUS_ID', $record_id);
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
    $result = $this->db()->db_select('STUD_STUDENT_CLASSES', 'class')
      ->fields('class', array('CREDITS_ATTEMPTED'))
      ->leftJoin('STUD_MARK_SCALE', 'markscale', 'markscale.MARK_SCALE_ID = class.MARK_SCALE_ID')
      ->fields('markscale', array('MARK_SCALE_NAME'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'grvalue', "grvalue.CODE = status.GRADE AND grvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Student.Enrollment.Grade')")
      ->fields('grvalue', array('DESCRIPTION' => 'GRADE'))
      ->leftJoin('STUD_STUDENT_DEGREES', 'studdegrees', 'studdegrees.STUDENT_DEGREE_ID = status.SEEKING_DEGREE_1_ID')
      ->leftJoin('STUD_DEGREE', 'degree', 'studdegrees.DEGREE_ID = degree.DEGREE_ID')
      ->fields('degree', array('DEGREE_NAME'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'advisororgterm', 'advisororgterm.STAFF_ORGANIZATION_TERM_ID = status.ADVISOR_ID')
      ->leftJoin('STUD_STAFF', 'advisor', 'advisor.STAFF_ID = advisororgterm.STAFF_ID')
      ->fields('advisor', array('ABBREVIATED_NAME' => 'advisor_ABBREVIATED_NAME'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'PREFERRED_NAME'))
      ->leftJoin('CONS_ADDRESS', 'res_address', 'res_address.ADDRESS_ID = stucon.RESIDENCE_ADDRESS_ID')
      ->fields('res_address', array('THOROUGHFARE' => 'res_ADDRESS', 'LOCALITY' => 'res_CITY', 'ADMINISTRATIVE_AREA' => 'res_STATE', 'POSTAL_CODE' => 'res_ZIPCODE'))
      ->leftJoin('CONS_ADDRESS', 'mail_address', 'mail_address.ADDRESS_ID = stucon.MAILING_ADDRESS_ID')
      ->fields('mail_address', array('THOROUGHFARE' => 'mail_ADDRESS', 'LOCALITY' => 'mail_CITY', 'ADMINISTRATIVE_AREA' => 'mail_STATE', 'POSTAL_CODE' => 'mail_ZIPCODE'))
      ->leftJoin('CONS_PHONE', 'phone', 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
      ->fields('phone', array('PHONE_NUMBER'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = class.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_TITLE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = status.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION', 'TERM_NAME'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->condition('class.DROPPED', '0')
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
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->orderBy('status.STUDENT_STATUS_ID', 'ASC')
      ->orderBy('term.START_DATE', 'ASC')
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->orderBy('SECTION_ID', 'ASC');
    //echo $result->sql();
    //var_dump($result->arguments());
    //die();
     $result = $result->execute();
    
    $last_student_status_id = 0;
    $credit_total = 0;
    while ($row = $result->fetch()) {
      
      if ($row['mail_ADDRESS'] != '') {
        $row['address']['address'] = $row['mail_ADDRESS'];
        $row['address']['city'] = $row['mail_CITY'];
        $row['address']['state'] = $row['mail_STATE'];
        $row['address']['zipcode'] = $row['mail_ZIPCODE']; 
      } else {
        $row['address']['address'] = $row['res_ADDRESS'];
        $row['address']['city'] = $row['res_CITY'];
        $row['address']['state'] = $row['res_STATE'];
        $row['address']['zipcode'] = $row['res_ZIPCODE'];
      }
      
      if (isset($meetings[$row['SECTION_ID']]))  {
        $row = array_merge($row, $meetings[$row['SECTION_ID']]);
      }
      if ($last_student_status_id != $row['STUDENT_STATUS_ID']) {
        if ($last_student_status_id != 0) {
          $pdf->credit_row($credit_total);
        }
        $pdf->setData($row);
        $pdf->row_count = 1;
        $credit_total = 0;
        $pdf->StartPageGroup();
        $pdf->AddPage();
      }
      
      $pdf->table_row($row);
      $credit_total += $row['CREDITS_ATTEMPTED'];
      $last_student_status_id = $row['STUDENT_STATUS_ID'];
      $pdf->row_count++;
      $pdf->row_page_count++;
      $pdf->row_total_count++;
    }
    

    if ($last_student_status_id != 0) {    
      $pdf->credit_row($credit_total);
    }
      
    // Closing line
    return $this->pdfResponse($pdf->Output('','S'));
  
  }
}