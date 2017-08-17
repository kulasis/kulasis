<?php

namespace Kula\Core\Component\Twig;

class Focus {

  private static $usergroups;
  private static $organization_menu;
  private static $terms;
  private static $schools;
  
  private static $organization_ids;
  
  public static function usergroups($db, $user_id) {
    $results = $db->db_select('CORE_USER_ROLES', 'roles')
      ->fields('roles', array('ROLE_ID'))
      ->join('CORE_USERGROUP', 'usergroups', 'usergroups.USERGROUP_ID = roles.USERGROUP_ID')
      ->fields('usergroups', array('USERGROUP_ID','USERGROUP_NAME'))
      ->leftJoin('CORE_ORGANIZATION', 'organization', 'roles.ORGANIZATION_ID = organization.ORGANIZATION_ID')
      ->fields('organization', array('ORGANIZATION_ABBREVIATION'))
      ->condition('roles.USER_ID', $user_id)
      ->condition('roles.ACTIVE', 1)
      ->execute();
    while ($row = $results->fetch()) {
      self::$usergroups[$row['ROLE_ID']] = $row['USERGROUP_NAME'];
      if ($row['ORGANIZATION_ABBREVIATION']) {
        self::$usergroups[$row['ROLE_ID']] .= ' - '.$row['ORGANIZATION_ABBREVIATION'];
      }
    }
    
    return self::$usergroups;  
  }
  
  public static function terms($organization, $term, $organizationID, $portal, $administrator = 'N', $user_id = null, $db = null, $focus = null) {
    $terms = array();
    if ($portal == 'core') {
      $terms['ALL'] = array('id' => 'ALL', 'startdate' => '2100-01-01', 'abbreviation' => 'All');
      
      $termsFromOrganization = $organization->getTermsForOrganization($organizationID);
      if ($termsFromOrganization) {
        foreach($termsFromOrganization as $key) {
          $terms[$key] = array('id' => $key, 'startdate' => $term->getStartDate($key), 'abbreviation' => $term->getTermAbbreviation($key));
        }
      }
      
      usort($terms, function($a, $b) {
          return $a['startdate'] > $b['startdate'];
      });
    
      foreach($terms as $key => $term) {
        $return[$term['id']] = $term['abbreviation'];
      }
    
      return $return;
    }
    
    if ($portal == 'teacher') {
      
      $term_results = $db->db_select('CORE_TERM', 'terms')
        ->distinct()
        ->fields('terms', array('TERM_ID', 'TERM_ABBREVIATION', 'TERM_NAME', 'START_DATE', 'END_DATE'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.TERM_ID = terms.TERM_ID')
        ->condition('orgterm.ORGANIZATION_ID', $focus->getOrganizationID());
      if ($administrator == '0') {
        $term_results = $term_results->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', 'stafforgterms.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID');
        $term_results = $term_results->condition('stafforgterms.STAFF_ID', $user_id);  
      }
    
       $term_results = $term_results
        ->orderBy('START_DATE')
        ->orderBy('END_DATE')
        ->execute();
      while ($term_row = $term_results->fetch())
        $terms[$term_row['TERM_ID']] = $term_row['TERM_ABBREVIATION'];
      
      return $terms;
    }
    
    if ($portal == 'student') {
      
      $term_results = $db->db_select('CORE_TERM', 'terms')
        ->distinct()
        ->fields('terms', array('TERM_ID', 'TERM_ABBREVIATION', 'TERM_NAME'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.TERM_ID = terms.TERM_ID')
        ->condition('orgterm.ORGANIZATION_ID', $focus->getOrganizationID());
      if ($administrator == '0') {
        $term_results = $term_results->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID');
        $term_results = $term_results->condition('stustatus.STUDENT_ID', $user_id);  
      }
    
       $term_results = $term_results
        ->orderBy('START_DATE')
        ->orderBy('END_DATE')
        ->execute();
      while ($term_row = $term_results->fetch())
        $terms[$term_row['TERM_ID']] = $term_row['TERM_ABBREVIATION'];
      
      return $terms;
    }
    
  }
  
  public static function getTeachers($db, $organization_id, $term_id) {
    $instructors = array();
    if ($organization_id OR $term_id) {
    $instructors_result = $db->db_select('STUD_STAFF', 'staff')
      ->distinct()
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ID = staff.STAFF_ID')
      ->fields('stafforgterm', array('STAFF_ID'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stafforgterm.ORGANIZATION_TERM_ID')
      ->condition('orgterms.ORGANIZATION_ID', $organization_id)
      ->condition('orgterms.TERM_ID', $term_id)
      ->orderBy('ABBREVIATED_NAME')
      ->execute();
    while ($instructors_row = $instructors_result->fetch()) {
      $instructors[$instructors_row['STAFF_ID']] = $instructors_row['ABBREVIATED_NAME'];
    }
    }
    return $instructors;
  }
  
  public static function getSchoolsMenu($organization, $school_organization_ids) {
    $schools_menu = array();
    foreach($school_organization_ids as $organization_id) {
      $schools_menu[$organization_id] = $organization->getName($organization_id);
    }
    return $schools_menu;
  }
  
  public static function getSectionMenu($db, $staff_organization_term_id, $organization_term_id = null, $department = null, $department_head = false) {

    $section_menu = array();
    $sections = $db->db_select('STUD_SECTION', 'section')
      ->distinct()
      ->fields('section', array('SECTION_NUMBER', 'SECTION_ID'))
      ->join('STUD_COURSE', 'course', 'section.COURSE_ID = course.COURSE_ID')
      ->fields('course', array('COURSE_TITLE', 'COURSE_NUMBER'))
      ->leftJoin('STUD_SECTION_STAFF', 'secstaff', 'secstaff.SECTION_ID = section.SECTION_ID')
      ->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', 
          'stafforgterms.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID OR 
           stafforgterms.STAFF_ORGANIZATION_TERM_ID = secstaff.STAFF_ORGANIZATION_TERM_ID')
      ->join('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterms.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'));

      $conditions_or = $db->db_or();

      if ($organization_term_id AND $department AND $department_head) {

        $dept_conditions_and = $db->db_and()
          ->condition('course.DEPARTMENT', $department)
          ->condition('section.ORGANIZATION_TERM_ID', $organization_term_id);

        $conditions_or = $conditions_or
          ->condition('section.STAFF_ORGANIZATION_TERM_ID', $staff_organization_term_id)
          ->condition('secstaff.STAFF_ORGANIZATION_TERM_ID', $staff_organization_term_id)
          ->condition($dept_conditions_and);

      } else {
        $conditions_or = $conditions_or->condition('section.STAFF_ORGANIZATION_TERM_ID', $staff_organization_term_id);
        $conditions_or = $conditions_or->condition('secstaff.STAFF_ORGANIZATION_TERM_ID', $staff_organization_term_id);
      }

    $sections = $sections->condition($conditions_or);
    $sections = $sections->orderBy('SECTION_NUMBER', 'ASC')
      ->execute();
    while ($sections_row = $sections->fetch()) {
      $section_menu[$sections_row['SECTION_ID']] = $sections_row['SECTION_NUMBER'].' | '.$sections_row['COURSE_NUMBER'].' | '.$sections_row['COURSE_TITLE'].' | '.$sections_row['ABBREVIATED_NAME'];
    }
    return $section_menu;
  }
  
  public static function getOrganizationMenu($cache, $organization, $topOrganizationID) {
    self::createMenuForOrganization($cache, $topOrganizationID);
    return self::$organization_menu;
  }
  
  public static function getStudents($db, $organization_id, $term_id) {
    $students = array();

    if ($organization_id AND $term_id) {
    $students_result = $db->db_select('STUD_STUDENT', 'stu')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = stu.STUDENT_ID')
      ->fields('stustatus', array('STUDENT_ID', 'LEVEL'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stu.STUDENT_ID')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->condition('orgterms.ORGANIZATION_ID', $organization_id)
      ->condition('orgterms.TERM_ID', $term_id)
      ->orderBy('LAST_NAME')
      ->orderBy('FIRST_NAME')
      ->execute();
    while ($students_row = $students_result->fetch()) {
      $students[$students_row['STUDENT_ID']] = $students_row['LAST_NAME'].', '.$students_row['FIRST_NAME'].' | '.$students_row['GENDER'].' | '.$students_row['LEVEL'] .' | '.$students_row['PERMANENT_NUMBER'].' ';
    }
    }
    return $students;
  }

  public static function getAdvisingStudentsMenu($db, $staff_organization_term_id, $organization_term_id) {
    $students = array();

    if ($staff_organization_term_id) {
      
      $staff_id = $db->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
        ->fields('stafforgterm', array('STAFF_ID'))
        ->condition('stafforgterm.STAFF_ORGANIZATION_TERM_ID', $staff_organization_term_id)
        ->execute()->fetch()['STAFF_ID'];
      
      $staff_organization_term_ids = $db->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
        ->fields('stafforgterm', array('STAFF_ORGANIZATION_TERM_ID'))
        ->condition('stafforgterm.STAFF_ID', $staff_id)
        ->execute()->fetchAll();
            
    $students_result = $db->db_select('STUD_STUDENT', 'stu')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = stu.STUDENT_ID')
      ->fields('stustatus', array('STUDENT_STATUS_ID', 'LEVEL', 'STATUS'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stu.STUDENT_ID')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'))
      ->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = stustatus.ADVISOR_ID')
      ->join('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID')
      ->condition('stustatus.ORGANIZATION_TERM_ID', $organization_term_id)
      ->condition('stustatus.ADVISOR_ID', $staff_organization_term_ids)
      ->orderBy('LAST_NAME')
      ->orderBy('FIRST_NAME')
      ->execute();
    while ($students_row = $students_result->fetch()) {
      $students[$students_row['STUDENT_STATUS_ID']] = 
      ($students_row['STATUS'] != '') ?
        '( '.$students_row['LAST_NAME'].', '.$students_row['FIRST_NAME'].' | '.$students_row['GENDER'].' | '.$students_row['LEVEL'] .' | '.$students_row['PERMANENT_NUMBER'].' )'
      : 
        $students_row['LAST_NAME'].', '.$students_row['FIRST_NAME'].' | '.$students_row['GENDER'].' | '.$students_row['LEVEL'] .' | '.$students_row['PERMANENT_NUMBER'].' ';
      
    }
    }
    return $students;
  }
  
  private static function createMenuForOrganization($cache, $organization, $dashes = null) {
    
    $org = $cache->get('organization.'.$organization);
    
    // top of menu
    self::$organization_menu[$org['ORGANIZATION_ID']] = $dashes . $org['ORGANIZATION_NAME'];
    
    // look for any children
    if (isset($org['children'])) {
      foreach($org['children'] as $child) {
        self::createMenuForOrganization($cache, $child, $dashes.'-- ');
      }
      
      
    }
    
  }
  
  
}
