<?php

namespace Kula\Core\Bundle\FrameworkBundle\Service;

class Focus {
  
  private $db;
  private $poster_factory;
  private $session;
  
  private $organization;
  
  private $organization_term_ids;
  
  public function __construct($session,
                              \Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\Poster $poster,
                              $organization,
                              $term) {
      $this->db = $db;
      $this->poster_factory = $poster;
      $this->session = $session;
      $this->organization = $organization;
      $this->term = $term;
  }
  
  public function setOrganizationTermFocus($organization_id = null, $term_id = null, $role_token = null) {
    
    if ($role_token === null) {
      $role_token = $this->session->get('initial_role');
    }

    if ($organization_id OR $term_id) {
    
      $items_to_update = array();
      
      if ($organization_id AND $organization_id != 'undefined') { 
        $this->session->setFocus('organization_id', $organization_id);
        $items_to_update['LAST_ORGANIZATION_ID'] = $organization_id;
      }
      if ($term_id AND $term_id != 'undefined') {
        $this->session->setFocus('term_id', $term_id);
        $items_to_update['LAST_TERM_ID'] = $term_id;
      }
    
      if ($term_id != 'ALL' AND count($items_to_update) > 0) {
        $this->db->db_update('CORE_USER_ROLES')
          ->fields($items_to_update)
          ->condition('ROLE_ID', $this->session->get('role_id'))
          ->execute();
      }       
    } 
    
    if (!$organization_id AND !$term_id AND $this->session->get('portal') != 'sis') {
      $this->session->setFocus('organization_id', $this->getSchoolIDs());
    }
  }

  public function setSectionFocus($section_id = null, $role_token = null) {
    
    if ($role_token === null) {
      $role_token = $this->session->get('initial_role');
    }
    
    // Get focus session info for role
    $staff_organization_term_id = $this->session->getFocus('staff_organization_term_id');
    
    if (!$section_id) {
      $section = $this->db->db_select('STUD_SECTION', 'section')
        ->fields('section', array('SECTION_ID'))
        ->join('STUD_COURSE', 'course', 'section.COURSE_ID = course.COURSE_ID')
        ->fields('course', array('COURSE_TITLE', 'COURSE_NUMBER'))
        ->condition('STAFF_ORGANIZATION_TERM_ID', $staff_organization_term_id)
        ->orderBy('SECTION_NUMBER', 'ASC')
        ->range(0, 1)
        ->execute()->fetch();
      
      $section_id = $section['SECTION_ID'];
    } 

    $this->session->setFocus('Teacher.HEd.Section', $section_id);
  }
  
  public function getSectionID() {
    $session_focus = $this->session->get('focus');
    if (isset($session_focus['Teacher.HEd.Section']))
      return $session_focus['Teacher.HEd.Section'];
    else
      return false;
  }
  
  public function setTeacherOrganizationTermFocus($teacher_id = null, $role_token = null) {

    if ($teacher_id) {
      $this->session->setFocus('staff_organization_term_id', $teacher_id, $role_token);
    } elseif ($this->session->get('administrator') == '1') {
      if ($teacher_id) {
        $this->session->setFocus('staff_organization_term_id', $teacher_id, $role_token);
      } elseif ($currentStaffOrgTerm = $this->session->getFocus('staff_organization_term_id', $role_token) !== null) {
        
        // get staff org term in new term
        $newTeacher = $this->db->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
        ->fields('stafforgterm', array('STAFF_ORGANIZATION_TERM_ID'))
        ->condition('stafforgterm.ORGANIZATION_TERM_ID', $this->getOrganizationTermIDs())
        ->join('STUD_STAFF_ORGANIZATION_TERMS', 'oldstafforgterm', 'oldstafforgterm.STAFF_ID = stafforgterm.STAFF_ID')
        ->condition('oldstafforgterm.STAFF_ORGANIZATION_TERM_ID', $currentStaffOrgTerm)
        ->execute()->fetch();
        
        if ($newTeacher['STAFF_ORGANIZATION_TERM_ID']) {
          $this->session->setFocus('staff_organization_term_id', $newTeacher['STAFF_ORGANIZATION_TERM_ID'], $role_token);
        } else {
          // Set to first teacher in list
          $firstTeacher = $this->db->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
          ->fields('stafforgterm', array('STAFF_ORGANIZATION_TERM_ID'))
          ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stafforgterm.STAFF_ID')
          ->condition('stafforgterm.ORGANIZATION_TERM_ID', $this->getOrganizationTermIDs())
          ->orderBy('cons.LAST_NAME')
          ->orderBy('cons.FIRST_NAME')
          ->range(0, 1)
          ->execute()->fetch();
          $this->session->setFocus('staff_organization_term_id', $firstTeacher['STAFF_ORGANIZATION_TERM_ID'], $role_token);
        }
        
      } else {
        // Set to first teacher in list
        $firstTeacher = $this->db->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
        ->fields('stafforgterm', array('STAFF_ORGANIZATION_TERM_ID'))
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stafforgterm.STAFF_ID')
        ->condition('stafforgterm.ORGANIZATION_TERM_ID', $this->getOrganizationTermIDs())
        ->orderBy('cons.LAST_NAME')
        ->orderBy('cons.FIRST_NAME')
        ->range(0, 1)
        ->execute()->fetch();
        $this->session->setFocus('staff_organization_term_id', $firstTeacher['STAFF_ORGANIZATION_TERM_ID'], $role_token);
      }
      
    } else {
      // get staff organization term id for currently set organization and term
      $staff_organization_term_id = $this->db->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm')
        ->fields('stafforgterm', array('STAFF_ORGANIZATION_TERM_ID'))
        ->condition('stafforgterm.ORGANIZATION_TERM_ID', $this->getOrganizationTermIDs())
        ->condition('stafforgterm.STAFF_ID', $this->session->get('user_id'));
      $staff_organization_term_id = $staff_organization_term_id->execute()->fetch();

      if ($staff_organization_term_id['STAFF_ORGANIZATION_TERM_ID']) {
        $this->session->setFocus('staff_organization_term_id', $staff_organization_term_id['STAFF_ORGANIZATION_TERM_ID'], $teacher_id, $role_token);
      }
      
    }
    
  }

  public function getTeacherOrganizationTermID() {
    $session_focus = $this->session->get('focus');
    if (isset($session_focus['staff_organization_term_id']))
      return $session_focus['staff_organization_term_id'];
    else
      return false;
  }
  
  public function getSchoolIDs() {
    if ($this->session->get('portal') == 'sis') {
      return $this->organization->getSchools($this->getOrganizationID());
    } else {
      return $this->organization->getSchools($this->getOrganizationID())[0];
    }
  }
  
  public function getOrganizationTermIDs() {
    return $this->organization->getOrganizationTerms($this->getOrganizationID(), $this->getTermID());
  }
  
  public function getOrganizationTermID() {
    return $this->organization->getOrganizationTerms($this->getOrganizationID(), $this->getTermID())[0];
  }
  
  public function getOrganizationID() {
    if ($this->session->getFocus('organization_id'))
      return $this->session->getFocus('organization_id');
    else
      return $this->session->get('organization_id');
  }
  
  public function getTermID() {
    $session_focus = $this->session->get('focus');
    
    if (isset($session_focus['term_id']) AND $session_focus['term_id'] != 'ALL') {
      return $session_focus['term_id'];  
    } elseif (isset($session_focus['term_id']) AND $session_focus['term_id'] == 'ALL') {
      return $this->term->getAllTermIDs();
    } elseif ($this->session->get('term_id')) {  
      return $this->session->get('term_id');
    }
  }
  
  public function getTermIDForMenu() {
    $session_focus = $this->session->get('focus');
    
    if (isset($session_focus['term_id']) AND $session_focus['term_id'] != 'ALL') {
      return $session_focus['term_id'];  
    } elseif (isset($session_focus['term_id']) AND $session_focus['term_id'] == 'ALL') {
      return '';
    } elseif ($this->session->get('term_id')) {  
      return $this->session->get('term_id');
    }
  }
  
}