<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class APIv1CourseController extends APIController {

	public function coursesAction($org, $term) {
    $this->authorize();

    $last = $this->request->get('last');
    
    $data = array(); $i = 0; $j = 0; $last_section_id = null;
    
    $result = $this->db()->db_select('STUD_SECTION', 'sec')
      ->fields('sec', array('SECTION_ID', 'SECTION_NUMBER', 'START_DATE', 'END_DATE', 'SECTION_NAME', 'CAPACITY', 'ENROLLED_TOTAL', 'NO_CLASS_DATES'))
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
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->condition('sec.STATUS', null);
    if ($last) $result = $result->condition($this->db()->db_or()
      ->condition('sec.CREATED_TIMESTAMP', date('Y-m-d', $last), '>=')
      ->condition('sec.UPDATED_TIMESTAMP', date('Y-m-d', $last), '>=')); 
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
      
      if ($row['SECTION_MEETING_ID']) {
        $data[$i]['meetings'][$j]['START_TIME'] = $row['START_TIME'];
        $data[$i]['meetings'][$j]['END_TIME'] = $row['END_TIME'];
        
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
      ->fields('sec', array('SECTION_ID', 'SECTION_NUMBER', 'START_DATE', 'END_DATE', 'SECTION_NAME', 'CAPACITY', 'ENROLLED_TOTAL', 'NO_CLASS_DATES'))
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
      ->condition('org.ORGANIZATION_ABBREVIATION', $org)
      ->condition('term.TERM_ABBREVIATION', $term)
      ->condition($condition_or)
      ->condition('sec.STATUS', null);
    $row = $result->execute()->fetch();
    
    if ($row['SECTION_NAME'] == '') $row['SECTION_NAME'] = $row['COURSE_TITLE'];

    return $this->JSONResponse($row);

  }

}