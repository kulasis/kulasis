<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class SISStudentScheduleBillReportController extends ReportController {
  
  public function indexAction() {
    $this->authorize();
    $this->formAction('sis_HEd_student_schedule_reports_studentschedulebill_generate');
    if ($this->request->query->get('record_type') == 'SIS.HEd.Student.Status' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('SIS.HEd.Student.Status');
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    return $this->render('KulaHEdSchedulingBundle:SISStudentScheduleReport:reports_studentschedule.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $this->pdf = new \Kula\HEd\Bundle\SchedulingBundle\Report\StudentScheduleBillReport("P");
    $this->pdf->SetFillColor(245,245,245);
    $this->pdf->row_count = 0;
    
    // Get Balances
    $this->student_balances_for_orgterm = array();
    
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    $record_type = $this->request->request->get('record_type');
    
    // Get current term start date
    $focus_term_info = $this->db()->db_select('CORE_TERM', 'term')
      ->fields('term', array('START_DATE'))
      ->condition('term.TERM_ID', $this->focus->getTermID())
      ->execute()->fetch();
    
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0) {
    
      $or_query_conditions = $this->db()->db_or();
      $or_query_conditions = $or_query_conditions->condition('term.TERM_ID', null);
      $or_query_conditions = $or_query_conditions->condition('term.START_DATE', $focus_term_info['START_DATE'], '<');
    
    $terms_with_balances_result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('CONSTITUENT_ID'))
      ->expression('SUM(AMOUNT)', 'total_amount')
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->condition($or_query_conditions)
      ->groupBy('CONSTITUENT_ID')
      ->orderBy('CONSTITUENT_ID');
    //echo $terms_with_balances_result->sql();
    //var_dump($terms_with_balances_result->arguments());
    //die();
    // Add on selected record
    if (isset($record_id) AND $record_id != '' AND $record_type == 'SIS.HEd.Student')
      $terms_with_balances_result = $terms_with_balances_result->condition('transactions.CONSTITUENT_ID', $record_id);
    $terms_with_balances_result = $terms_with_balances_result->execute();
    while ($balance_row = $terms_with_balances_result->fetch()) {
      $this->student_balances_for_orgterm[$balance_row['CONSTITUENT_ID']][] = $balance_row;
    }
    } 
    
    $meetings = array();
    // Get meeting data
    $meeting_result = $this->db()->db_select('STUD_SECTION', 'section')
      ->distinct()
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
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->fields('student', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER'))
      ->leftJoin('CONS_ADDRESS', 'res_address', 'res_address.ADDRESS_ID = stucon.RESIDENCE_ADDRESS_ID')
      ->fields('res_address', array('THOROUGHFARE' => 'res_ADDRESS', 'LOCALITY' => 'res_CITY', 'ADMINISTRATIVE_AREA' => 'res_STATE', 'POSTAL_CODE' => 'res_ZIPCODE'))
      ->leftJoin('CONS_ADDRESS', 'mail_address', 'mail_address.ADDRESS_ID = stucon.MAILING_ADDRESS_ID')
      ->fields('mail_address', array('THOROUGHFARE' => 'mail_ADDRESS', 'LOCALITY' => 'mail_CITY', 'ADMINISTRATIVE_AREA' => 'mail_STATE', 'POSTAL_CODE' => 'mail_ZIPCODE'))
      ->leftJoin('CONS_PHONE', 'phone', 'phone.PHONE_NUMBER_ID = stucon.PRIMARY_PHONE_ID')
      ->fields('phone', array('PHONE_NUMBER'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = class.SECTION_ID')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'START_DATE', 'END_DATE', 'NO_CLASS_DATES', 'SUPPLIES_REQUIRED', 'SUPPLIES_OPTIONAL', 'SUPPLIES_PRICE'))
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
      ->orderBy('section.START_DATE', 'ASC')
      ->orderBy('SECTION_NUMBER', 'ASC')
      ->orderBy('SECTION_ID', 'ASC');
    //echo $result->sql();
    //var_dump($result->arguments());
    //die();
     $result = $result->execute();
    
    $last_student_status_id = 0;
    $last_id = 0;
    $credit_total = 0;
    $this->pdf->balance = 0;
    $this->pdf->supply_list = array();
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
        
        $this->pdf->billing_header();
        if (isset($this->student_balances_for_orgterm[$last_id]))
          $this->pdf->billing_previous_balances($this->student_balances_for_orgterm[$last_id]);
        $this->getTransactionsForStudent($last_id);
        $this->pdf->billing_total_balance();
        $this->pdf->supply_list_row();
        
        
        $this->pdf->setData($row);
        $this->pdf->row_count = 1;
        $credit_total = 0;
        $this->pdf->StartPageGroup();
        $this->pdf->AddPage();
        $this->pdf->first_header();
        $this->pdf->balance = 0;
        $this->pdf->supply_list = array();
      }
      
      $this->pdf->table_row($row);
      $credit_total += $row['CREDITS_ATTEMPTED'];
      if ($row['SUPPLIES_REQUIRED'] != '' OR $row['SUPPLIES_OPTIONAL'] != '') {
        $this->pdf->supply_list[] = array(
          'SECTION_NUMBER' => $row['SECTION_NUMBER'],
          'COURSE_TITLE' => $row['COURSE_TITLE'],
          'SUPPLIES_REQUIRED' => $row['SUPPLIES_REQUIRED'],
          'SUPPLIES_OPTIONAL' => $row['SUPPLIES_OPTIONAL'],
          'SUPPLIES_PRICE' => $row['SUPPLIES_PRICE']
        );
      }
      $last_student_status_id = $row['STUDENT_STATUS_ID'];
      $last_id = $row['STUDENT_ID'];
      $this->pdf->row_count++;
      $this->pdf->row_page_count++;
      $this->pdf->row_total_count++;
    }
    
    $this->pdf->billing_header();
    if (isset($this->student_balances_for_orgterm[$last_id]))
      $this->pdf->billing_previous_balances($this->student_balances_for_orgterm[$last_id]);
    $this->getTransactionsForStudent($last_id);
    $this->pdf->billing_total_balance();
    $this->pdf->supply_list_row();
    
    // Closing line
    return $this->pdfResponse($this->pdf->Output('','S'));
  
  }
  
  public function getTransactionsForStudent($student_id) {
    $result = $this->db()->db_select('BILL_CONSTITUENT_TRANSACTIONS', 'transactions')
      ->fields('transactions', array('TRANSACTION_DATE', 'TRANSACTION_DESCRIPTION', 'AMOUNT', 'POSTED'))
      ->leftJoin('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = transactions.ORGANIZATION_TERM_ID')
      ->leftJoin('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ID', 'TERM_ABBREVIATION', 'START_DATE', 'END_DATE'))
      ->condition('transactions.CONSTITUENT_ID', $student_id)
      ->condition('transactions.SHOW_ON_STATEMENT', 1);
    
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $result = $result->condition('transactions.ORGANIZATION_TERM_ID', $org_term_ids);
    $result = $result
      ->orderBy('term.START_DATE', 'ASC')
      ->orderBy('transactions.TRANSACTION_DATE', 'ASC')
      ->execute();
    while ($row = $result->fetch()) {
      
      $this->pdf->billing_table_row($row);
      
    }
  }
}