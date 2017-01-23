<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class APIv1CourseController extends APIController {

  public function coursesAction($org, $term) {
    $this->authorize();

    $last = $this->request->query->get('last');

    $limit = $this->request->query->get('limit');
    $offset = ($this->request->query->get('offset') != '') ? $this->request->query->get('offset') : 0;

    $data = array(); $i = 0; $j = 0; $last_section_id = null;
    
    $result = $this->db()->db_select('STUD_SECTION', 'sec')
      ->fields('sec', array('SECTION_ID', 'SECTION_NUMBER', 'START_DATE', 'END_DATE', 'SECTION_NAME', 'CAPACITY', 'ENROLLED_TOTAL', 'NO_CLASS_DATES', 'ALLOW_REGISTRATION', 'OPEN_REGISTRATION', 'CLOSE_REGISTRATION', 'SUPPLIES_REQUIRED', 'SUPPLIES_OPTIONAL', 'SUPPLIES_PRICE', 'NO_CLASS_DATES', 'PARENT_ENROLL'))
      ->join('STUD_COURSE', 'crs', 'crs.COURSE_ID = sec.COURSE_ID')
      ->fields('crs', array('COURSE_TITLE', 'COURSE_NUMBER', 'COURSE_DESCRIPTION', 'PREREQUISITE_DESCRIPTION'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', 'stafforgterms.STAFF_ORGANIZATION_TERM_ID = sec.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterms.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME' => 'INSTRUCTOR_ABBREVIATED_NAME'))
      ->leftJoin('STUD_SECTION_MEETINGS', 'mtg', 'mtg.SECTION_ID = sec.SECTION_ID')
      ->fields('mtg', array('SECTION_MEETING_ID', 'START_DATE' => 'mtg_START_DATE', 'END_DATE' => 'mtg_END_DATE', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN', 'START_TIME', 'END_TIME'))
      ->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = mtg.ROOM_ID')
      ->fields('rooms', array('ROOM_NUMBER'))
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->condition('sec.STATUS', null);
    if ($last) $result = $result->condition($this->db()->db_or()
      ->condition('sec.CREATED_TIMESTAMP', date('Y-m-d', $last), '>=')
      ->condition('sec.UPDATED_TIMESTAMP', date('Y-m-d', $last), '>=')); 
    if ($limit) { $result = $result->range($offset, $limit); }
    $result = $result->execute();
    while ($row = $result->fetch()) {
      
      $data[$i]['SECTION_ID'] = $row['SECTION_ID'];
      $data[$i]['SECTION_NUMBER'] = $row['SECTION_NUMBER'];
      if ($row['SECTION_NAME']) $data[$i]['SECTION_NAME'] = $row['SECTION_NAME']; else $data[$i]['SECTION_NAME'] = $row['COURSE_TITLE'];
      $data[$i]['COURSE_NUMBER'] = $row['COURSE_NUMBER'];
      $data[$i]['COURSE_TITLE'] = $row['COURSE_TITLE'];
      $data[$i]['COURSE_DESCRIPTION'] = $row['COURSE_DESCRIPTION'];
      $data[$i]['PREREQUISITE_DESCRIPTION'] = $row['PREREQUISITE_DESCRIPTION'];
      $data[$i]['ORGANIZATION_ABBREVIATION'] = $row['ORGANIZATION_ABBREVIATION'];
      $data[$i]['TERM_ABBREVIATION'] = $row['TERM_ABBREVIATION'];
      $data[$i]['INSTRUCTOR_ABBREVIATED_NAME'] = $row['INSTRUCTOR_ABBREVIATED_NAME'];
      $data[$i]['NO_CLASS_DATES'] = $row['NO_CLASS_DATES'];
      $data[$i]['CAPACITY'] = $row['CAPACITY'];
      $data[$i]['ENROLLED'] = $row['ENROLLED_TOTAL'];
      $data[$i]['OPEN'] = $row['CAPACITY'] - $row['ENROLLED_TOTAL'];
      $data[$i]['START_DATE'] = $row['START_DATE'];
      $data[$i]['END_DATE'] = $row['END_DATE'];
      $data[$i]['ALLOW_REGISTRATION'] = $row['ALLOW_REGISTRATION'];
      $data[$i]['OPEN_REGISTRATION'] = $row['OPEN_REGISTRATION'];
      $data[$i]['CLOSE_REGISTRATION'] = $row['CLOSE_REGISTRATION'];
      $data[$i]['PARENT_ENROLL'] = $row['PARENT_ENROLL'];
      $data[$i]['SUPPLIES_REQUIRED'] = $row['SUPPLIES_REQUIRED'];
      $data[$i]['SUPPLIES_OPTIONAL'] = $row['SUPPLIES_OPTIONAL'];
      $data[$i]['SUPPLIES_PRICE'] = $row['SUPPLIES_PRICE'];
      $data[$i]['NO_CLASS_DATES'] = $row['NO_CLASS_DATES'];
      
      if ($row['SECTION_MEETING_ID']) {
        $data[$i]['meetings'][$j]['START_TIME'] = $row['START_TIME'];
        $data[$i]['meetings'][$j]['END_TIME'] = $row['END_TIME'];
        $data[$i]['meetings'][$j]['ROOM_NUMBER'] = $row['ROOM_NUMBER'];
        
        $data[$i]['meetings'][$j]['DAYS'] = array();
        if ($row['MON']) $data[$i]['meetings'][$j]['DAYS'][] = 'Mon';
        if ($row['TUE']) $data[$i]['meetings'][$j]['DAYS'][] = 'Tue';
        if ($row['WED']) $data[$i]['meetings'][$j]['DAYS'][] = 'Wed';
        if ($row['THU']) $data[$i]['meetings'][$j]['DAYS'][] = 'Thu';
        if ($row['FRI']) $data[$i]['meetings'][$j]['DAYS'][] = 'Fri';
        if ($row['SAT']) $data[$i]['meetings'][$j]['DAYS'][] = 'Sat';
        if ($row['SUN']) $data[$i]['meetings'][$j]['DAYS'][] = 'Sun';
        if (count($data[$i]['meetings'][$j]['DAYS'])) 
          $data[$i]['meetings'][$j]['DAYS'] = implode(' ', $data[$i]['meetings'][$j]['DAYS']);
        else 
          $data[$i]['meetings'][$j]['DAYS'] = null;
          
        if ($row['mtg_START_DATE']) 
          $data[$i]['meetings'][$j]['START_DATE'] = $row['mtg_START_DATE']; 
        else 
          $data[$i]['meetings'][$j]['START_DATE'] = $row['START_DATE'];
        if ($row['mtg_END_DATE']) 
          $data[$i]['meetings'][$j]['END_DATE'] = $row['mtg_END_DATE']; 
        else 
          $data[$i]['meetings'][$j]['END_DATE'] = $row['END_DATE'];
      }
     
    // Get fees
    $f = 0;
    $data[$i]['fees_total'] = 0;
    $fees_result = $this->db()->db_select('BILL_SECTION_FEE', 'secfee')
      ->fields('secfee', array('AMOUNT'))
      ->join('BILL_CODE', 'code', 'code.CODE_ID = secfee.CODE_ID')
      ->fields('code', array('CODE', 'CODE_DESCRIPTION'))
      ->condition('secfee.SECTION_ID', $row['SECTION_ID'])
      ->execute();
    while ($fees_row = $fees_result->fetch()) {
      $data[$i]['fees'][$f]['CODE'] = $fees_row['CODE'];
      $data[$i]['fees'][$f]['AMOUNT'] = $fees_row['AMOUNT'];
      $data[$i]['fees_total'] += $fees_row['AMOUNT'];
    $f++;
    } // end loop on fees

    // Get discounts
    $discount_or = $this->db()->db_or();
    $discount_or = $discount_or->condition('secdis.END_DATE', date('Y-m-d'), '>=');
    $discount_or = $discount_or->isNull('secdis.END_DATE');

    $d = 0;
    $discounts_result = $this->db()->db_select('BILL_SECTION_FEE_DISCOUNT', 'secdis')
      ->fields('secdis', array('AMOUNT'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'value', "value.CODE = secdis.DISCOUNT AND value.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Billing.Fee.Discount')")
      ->fields('value', array('DESCRIPTION' => 'discount'))
      ->condition('secdis.SECTION_ID', $row['SECTION_ID'])
      ->condition($discount_or)
      ->orderBy('DESCRIPTION', 'ASC', 'value')
      ->execute();
    while ($discounts_row = $discounts_result->fetch()) {
      $data[$i]['discounts'][$d]['DISCOUNT'] = $discounts_row['discount'];
      $data[$i]['discounts'][$d]['AMOUNT'] = $discounts_row['AMOUNT'];
    $d++;
    } // end loop on fees

    // Get related sections
    $related_sections_result = $this->db()->db_select('STUD_SECTION_SECTIONS', 'secs')
      ->fields('secs', array('OPTIONAL'))
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = secs.RELATED_SECTION_ID')
      ->fields('sec')
      ->join('STUD_COURSE', 'crs', 'crs.COURSE_ID = sec.COURSE_ID')
      ->fields('crs', array('COURSE_TITLE'))
      ->condition('secs.SECTION_ID', $row['SECTION_ID'])
      ->execute();
    $sec = 0;
    while ($related_section_row = $related_sections_result->fetch()) {

      $data[$i]['related'][$sec]['ID'] = $related_section_row['SECTION_ID'];
      $data[$i]['related'][$sec]['SECTION_NUMBER'] = $related_section_row['SECTION_NUMBER'];
      if ($related_section_row['SECTION_NAME'] == '') 
        $data[$i]['related'][$sec]['SECTION_NAME'] = $related_section_row['SECTION_NAME'];
      else
        $data[$i]['related'][$sec]['SECTION_NAME'] = $related_section_row['COURSE_TITLE'];
      $data[$i]['related'][$sec]['OPTIONAL'] = $related_section_row['OPTIONAL'];

    $sec++;
    }

    if ($row['SECTION_ID'] != $last_section_id) { $i++; $j = 0; $last_section_id = $row['SECTION_ID']; } else { $j++; }
    }
    
    return $this->JSONResponse($data);
  }  

  public function courseAction($org, $term, $section_id) {

    $row = array();

    $condition_or = $this->db()->db_or()
      ->condition('sec.SECTION_ID', $section_id)
      ->condition('sec.SECTION_NUMBER', $section_id);

    $result = $this->db()->db_select('STUD_SECTION', 'sec')
      ->fields('sec', array('SECTION_ID', 'SECTION_NUMBER', 'START_DATE', 'END_DATE', 'SECTION_NAME', 'CAPACITY', 'ENROLLED_TOTAL', 'ALLOW_REGISTRATION', 'OPEN_REGISTRATION', 'CLOSE_REGISTRATION', 'NO_CLASS_DATES', 'SUPPLIES_REQUIRED', 'SUPPLIES_OPTIONAL', 'SUPPLIES_PRICE', 'NO_CLASS_DATES', 'PARENT_ENROLL'))
      ->join('STUD_COURSE', 'crs', 'crs.COURSE_ID = sec.COURSE_ID')
      ->fields('crs', array('COURSE_TITLE', 'COURSE_NUMBER', 'COURSE_DESCRIPTION', 'PREREQUISITE_DESCRIPTION'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = sec.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_ABBREVIATION'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', 'stafforgterms.STAFF_ORGANIZATION_TERM_ID = sec.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterms.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME' => 'INSTRUCTOR_ABBREVIATED_NAME'))
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->condition($condition_or)
      ->condition('sec.STATUS', null);
    $row = $result->execute()->fetch();
    
    if ($row['SECTION_NAME'] == '') $row['SECTION_NAME'] = $row['COURSE_TITLE'];

    // Get Meetings
    $meetings = $this->db()->db_select('STUD_SECTION_MEETINGS', 'mtg')
      ->fields('mtg', array('SECTION_MEETING_ID', 'START_DATE' => 'mtg_START_DATE', 'END_DATE' => 'mtg_END_DATE', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN', 'START_TIME', 'END_TIME'))
      ->leftJoin('STUD_ROOM', 'rooms', 'rooms.ROOM_ID = mtg.ROOM_ID')
      ->fields('rooms', array('ROOM_NUMBER'))
      ->condition('mtg.SECTION_ID', $row['SECTION_ID'])
      ->execute();
    $j = 0;
    while ($meeting = $meetings->fetch()) {

      $row['meetings'][$j]['START_TIME'] = $meeting['START_TIME'];
      $row['meetings'][$j]['END_TIME'] = $meeting['END_TIME'];
      $row['meetings'][$j]['ROOM_NUMBER'] = $meeting['ROOM_NUMBER'];
      
      $row['meetings'][$j]['DAYS'] = array();
      if ($meeting['MON']) $row['meetings'][$j]['DAYS'][] = 'Mon';
      if ($meeting['TUE']) $row['meetings'][$j]['DAYS'][] = 'Tue';
      if ($meeting['WED']) $row['meetings'][$j]['DAYS'][] = 'Wed';
      if ($meeting['THU']) $row['meetings'][$j]['DAYS'][] = 'Thu';
      if ($meeting['FRI']) $row['meetings'][$j]['DAYS'][] = 'Fri';
      if ($meeting['SAT']) $row['meetings'][$j]['DAYS'][] = 'Sat';
      if ($meeting['SUN']) $row['meetings'][$j]['DAYS'][] = 'Sun';
      if (count($row['meetings'][$j]['DAYS'])) 
        $meeting['meetings'][$j]['DAYS'] = implode(' ', $row['meetings'][$j]['DAYS']);
      else 
        $meeting['meetings'][$j]['DAYS'] = null;
        
      if ($meeting['mtg_START_DATE']) 
        $row['meetings'][$j]['START_DATE'] = $meeting['mtg_START_DATE']; 
      else 
        $row['meetings'][$j]['START_DATE'] = $meeting['START_DATE'];
      if ($meeting['mtg_END_DATE']) 
        $row['meetings'][$j]['END_DATE'] = $meeting['mtg_END_DATE']; 
      else 
        $row['meetings'][$j]['END_DATE'] = $meeting['END_DATE'];
      $j++;
    }

    // Get fees
    $f = 0;
    $row['fees_total'] = 0;
    $fees_result = $this->db()->db_select('BILL_SECTION_FEE', 'secfee')
      ->fields('secfee', array('AMOUNT'))
      ->join('BILL_CODE', 'code', 'code.CODE_ID = secfee.CODE_ID')
      ->fields('code', array('CODE', 'CODE_DESCRIPTION'))
      ->condition('secfee.SECTION_ID', $row['SECTION_ID'])
      ->execute();
    while ($fees_row = $fees_result->fetch()) {
      $row['fees'][$f]['CODE'] = $fees_row['CODE'];
      $row['fees'][$f]['AMOUNT'] = $fees_row['AMOUNT'];
      $row['fees_total'] += $fees_row['AMOUNT'];
    $f++;
    } // end loop on fees

        // Get discounts
    $discount_or = $this->db()->db_or();
    $discount_or = $discount_or->condition('secdis.END_DATE', date('Y-m-d'), '>=');
    $discount_or = $discount_or->isNull('secdis.END_DATE');

    $d = 0;
    $discounts_result = $this->db()->db_select('BILL_SECTION_FEE_DISCOUNT', 'secdis')
      ->fields('secdis', array('AMOUNT', 'SECTION_FEE_DISCOUNT_ID'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'value', "value.CODE = secdis.DISCOUNT AND value.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'HEd.Billing.Fee.Discount')")
      ->fields('value', array('DESCRIPTION' => 'discount'))
      ->condition('secdis.SECTION_ID', $row['SECTION_ID'])
      ->condition($discount_or)
      ->orderBy('DESCRIPTION', 'ASC', 'value')
      ->execute();
    while ($discounts_row = $discounts_result->fetch()) {
      $row['discounts'][$d]['ID'] = $discounts_row['SECTION_FEE_DISCOUNT_ID'];
      $row['discounts'][$d]['DISCOUNT'] = $discounts_row['discount'];
      $row['discounts'][$d]['AMOUNT'] = $discounts_row['AMOUNT'];
    $d++;
    } // end loop on fees

    // Get related sections
    $related_sections_result = $this->db()->db_select('STUD_SECTION_SECTIONS', 'secs')
      ->fields('secs', array('OPTIONAL'))
      ->join('STUD_SECTION', 'sec', 'sec.SECTION_ID = secs.RELATED_SECTION_ID')
      ->fields('sec')
      ->join('STUD_COURSE', 'crs', 'crs.COURSE_ID = sec.COURSE_ID')
      ->fields('crs', array('COURSE_TITLE'))
      ->condition('secs.SECTION_ID', $section_id)
      ->execute();
    $sec = 0;
    while ($related_section_row = $related_sections_result->fetch()) {

      $row['related'][$sec]['ID'] = $related_section_row['SECTION_ID'];
      $row['related'][$sec]['SECTION_NUMBER'] = $related_section_row['SECTION_NUMBER'];
      if ($related_section_row['SECTION_NAME'] == '') 
        $row['related'][$sec]['SECTION_NAME'] = $related_section_row['SECTION_NAME'];
      else
        $row['related'][$sec]['SECTION_NAME'] = $related_section_row['COURSE_TITLE'];
      $row['related'][$sec]['OPTIONAL'] = $related_section_row['OPTIONAL'];

    $sec++;
    }

    return $this->JSONResponse($row);

  }

}