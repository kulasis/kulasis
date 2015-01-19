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
      ->execute();
    while ($row = $results->fetch()) {
      self::$usergroups[$row['ROLE_ID']] = $row['USERGROUP_NAME'];
      if ($row['ORGANIZATION_ABBREVIATION']) {
        self::$usergroups[$row['ROLE_ID']] .= ' - '.$row['ORGANIZATION_ABBREVIATION'];
      }
    }
    
    return self::$usergroups;  
  }
  
  public static function terms($portal, $administrator = 'N', $user_id = null) {
    if ($portal == 'sis') {
      self::$terms['ALL'] = 'All';
    }
    $term_results = \Kula\Component\Database\DB::connect('read')->select('CORE_TERM', 'terms')
      ->distinct()
      ->fields('terms', array('TERM_ID', 'TERM_ABBREVIATION', 'TERM_NAME'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', null, 'orgterm.TERM_ID = terms.TERM_ID');
    if ($portal == 'sis') {
      $term_results = $term_results->predicate('orgterm.ORGANIZATION_ID', self::$organization_ids);
    }
    if ($portal == 'teacher' AND $administrator == 'N') {
      $term_results = $term_results->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', null, 'stafforgterms.ORGANIZATION_TERM_ID = orgterm.ORGANIZATION_TERM_ID');
      $term_results = $term_results->predicate('stafforgterms.STAFF_ID', $user_id);  
    }
     $term_results = $term_results
      ->order_by('START_DATE')
      ->order_by('END_DATE')
      ->execute();
    while ($term_row = $term_results->fetch())
      self::$terms[$term_row['TERM_ID']] = $term_row['TERM_ABBREVIATION'];
    
    return self::$terms;
  }
  
  public static function getTeachers($organization_term_id) {
    $instructors = array();
    
    if ($organization_term_id) {
    $instructors_result = \Kula\Component\Database\DB::connect('read')->select('STUD_STAFF', 'staff')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', array('STAFF_ORGANIZATION_TERM_ID'), 'stafforgterm.STAFF_ID = staff.STAFF_ID')
      ->predicate('stafforgterm.ORGANIZATION_TERM_ID', $organization_term_id)
      ->order_by('ABBREVIATED_NAME')
      ->execute();
    while ($instructors_row = $instructors_result->fetch()) {
      $instructors[$instructors_row['STAFF_ORGANIZATION_TERM_ID']] = $instructors_row['ABBREVIATED_NAME'];
    }
    }
    return $instructors;
  }
  
  public static function getSchoolsMenu($school_organization_ids) {
    $schools_menu = array();
    $schools = \Kula\Component\Database\DB::connect('read')->select('CORE_ORGANIZATION')
      ->fields(null, array('ORGANIZATION_ID', 'ORGANIZATION_NAME', 'ORGANIZATION_ABBREVIATION'))
      ->predicate('ORGANIZATION_ID', $school_organization_ids)
      ->predicate('SCHOOL', 'Y')
      ->order_by('ORGANIZATION_NAME', 'ASC')
      ->execute();
    while ($schools_row = $schools->fetch()) {
      $schools_menu[$schools_row['ORGANIZATION_ID']] = $schools_row['ORGANIZATION_NAME'];
    }
    return $schools_menu;
  }
  
  public static function getSectionMenu($staff_organization_term_id) {
    $section_menu = array();
    $sections = \Kula\Component\Database\DB::connect('read')->select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_NUMBER', 'SECTION_ID'))
      ->join('STUD_COURSE', 'course', array('COURSE_TITLE', 'COURSE_NUMBER'), 'section.COURSE_ID = course.COURSE_ID')
      ->predicate('STAFF_ORGANIZATION_TERM_ID', $staff_organization_term_id)
      ->order_by('SECTION_NUMBER', 'ASC')
      ->execute();
    while ($sections_row = $sections->fetch()) {
      $section_menu[$sections_row['SECTION_ID']] = $sections_row['SECTION_NUMBER'].' | '.$sections_row['COURSE_NUMBER'].' | '.$sections_row['COURSE_TITLE'];
    }
    return $section_menu;
  }
  
  public static function getOrganizationMenu($top_organization_id) {
    
    $organization_obj = new \Kula\Component\Focus\Organization;
    $organization_obj->setOrganization($top_organization_id);
    $organizations = $organization_obj->getAllOrganization();
    
    self::createMenuForOrganization($top_organization_id, $organizations);
    
    return self::$organization_menu;
  }
  
  private static function createMenuForOrganization($parent_organization_id, $organization_array, $dashes = null) {
    
    $children_organization = null;
    
    // top of menu
    self::$organization_menu[$organization_array[$parent_organization_id]['ORGANIZATION_ID']] = $dashes . $organization_array[$parent_organization_id]['ORGANIZATION_NAME'];
    self::$organization_ids[] = $parent_organization_id;
    
    // look for any children
    foreach($organization_array as $org_id => $org_row) {
      foreach($org_row as $org_row_key => $org_row_value) {
        if ($org_row_key == 'PARENT_ORGANIZATION_ID' && $org_row_value == $parent_organization_id) {
          self::createMenuForOrganization($org_id, $organization_array, $dashes . '-- ');
        }
      }
    }
  }
  
  
}
