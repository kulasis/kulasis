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
        $this->session->setFocus('target', $this->organization->getTarget($organization_id));
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
    
    if (!$organization_id AND !$term_id AND $this->session->get('portal') != 'core') {
      $this->session->setFocus('organization_id', $this->getSchoolIDs()[0]);
      //$this->session->setFocus('term_id', $this->getTermID());
    }
  }

  public function setSectionFocus($section_id = null, $role_token = null) {

    if ($role_token === null) {
      $role_token = $this->session->get('initial_role');
    }

    // Get focus session info for role
    
    $staff_organization_term_id = $this->session->getFocus('Teacher.Staff.OrgTerm');

    if (!$section_id AND $staff_organization_term_id != '') {
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
  
  public function setAdvisorStudentFocus($student_status_id = null, $role_token = null) {
    
    if ($role_token === null) {
      $role_token = $this->session->get('initial_role');
    }
    
    // Get focus session info for role
    $staff_organization_term_id = $this->session->getFocus('Teacher.Staff.OrgTerm');
    
    if (!$student_status_id) {
      $students_result = $this->db->db_select('STUD_STUDENT', 'stu')
        ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = stu.STUDENT_ID')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'LEVEL'))
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stu.STUDENT_ID')
        ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'GENDER'))
        ->condition('stustatus.ADVISOR_ID', $staff_organization_term_id)
        ->orderBy('LAST_NAME')
        ->orderBy('FIRST_NAME')
        ->range(0, 1)
        ->execute()->fetch();
      
      $student_status_id = $students_result['STUDENT_STATUS_ID'];
    } 

    $this->session->setFocus('Teacher.HEd.Advisor.Student', $student_status_id);
  }

  public function getFocus($key) {
    return $this->session->getFocus($key);
  }
  
  public function getSectionID() {
    $session_focus = $this->session->get('focus');
    if (isset($session_focus['Teacher.HEd.Section']))
      return $session_focus['Teacher.HEd.Section'];
    else
      return false;
  }

  public function getTeacherOrganizationTermID($admin = false) {
    $session_focus = $this->session->get('focus');
    if (isset($session_focus['Teacher.Staff.OrgTerm']))
      return $session_focus['Teacher.Staff.OrgTerm'];
    else
      return false;
  
  }
  
  public function getTeacherStaffOrganizationTermID() {
    return $this->getTeacherStaffOrganizationTermID();
  }

  public function getTeacherOrganizationDepartment() {
    $session_focus = $this->session->get('focus');
    if (isset($session_focus['Teacher.Staff.Department']))
      return $session_focus['Teacher.Staff.Department'];
    else
      return false;
  }

  public function getTeacherOrganizationDepartmentHead() {
    $session_focus = $this->session->get('focus');
    if (isset($session_focus['Teacher.Staff.Department.Head']))
      return $session_focus['Teacher.Staff.Department.Head'];
    else
      return false;
  }
  
  public function setTeacherStaffFocusFromStaff($organization_id, $term_id, $staff_id = null, $role_token = null) {

    if ($this->session->get('administrator') == '1') {
      // check for student status given organization, term, and student
      $student = $this->db->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms')
        ->fields('stafforgterms', array('STAFF_ORGANIZATION_TERM_ID', 'STAFF_ID'))
        ->join('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterms.STAFF_ID')
        ->fields('staff', array('DEPARTMENT', 'DEPARTMENT_2', 'DEPARTMENT_3', 'DEPARTMENT_4', 'DEPARTMENT_HEAD'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stafforgterms.ORGANIZATION_TERM_ID')
        ->condition('orgterms.ORGANIZATION_ID', $organization_id)
        ->condition('orgterms.TERM_ID', $term_id)
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stafforgterms.STAFF_ID')
        ->fields('cons', array('LAST_NAME', 'FIRST_NAME'));
      if ($staff_id) {
        $student = $student->condition('stafforgterms.STAFF_ID', $staff_id);
      } 
      $student = $student->orderBy('cons.LAST_NAME')->orderBy('cons.FIRST_NAME');
      if (!$staff_id) {
        $student = $student->range(0, 1);
      }
      $student = $student->execute()->fetch();

      // Only if student status returns set new focused student
      $this->setOrganizationTermFocus($organization_id, $term_id, $role_token);
      $this->session->setFocus('Teacher.Staff', $student['STAFF_ID'], $role_token);
      $this->setTeacherOrganizationTermFocus($student['STAFF_ORGANIZATION_TERM_ID'], $role_token);
      $this->session->setFocus('Teacher.Staff.Department', array($student['DEPARTMENT'], $student['DEPARTMENT_2'], $student['DEPARTMENT_3'], $student['DEPARTMENT_4']), $role_token);
      $this->session->setFocus('Teacher.Staff.Department.Head', $student['DEPARTMENT_HEAD'], $role_token);
    } else {
      $this->setOrganizationTermFocus($organization_id, $term_id, $role_token);
      $this->setTeacherOrganizationTermFocus();
    }
    
    return $student['STAFF_ID'];
    
  }
  
  public function setTeacherOrganizationTermFocus($staff_orgterm_id = null, $role_token = null) {
    if ($staff_orgterm_id) {
      $this->session->setFocus('Teacher.Staff.OrgTerm', $staff_orgterm_id, $role_token);
    } elseif ($this->session->get('administrator') != 1) {
      //echo $this->session->getFocus('organization_id').' '.$this->session->getFocus('term_id');
      $staff_orgterm = $this->db->db_select('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms')
        ->fields('stafforgterms', array('STAFF_ORGANIZATION_TERM_ID', 'STAFF_ID'))
        ->join('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterms.STAFF_ID')
        ->fields('staff', array('DEPARTMENT', 'DEPARTMENT_2', 'DEPARTMENT_3', 'DEPARTMENT_4', 'DEPARTMENT_HEAD'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stafforgterms.ORGANIZATION_TERM_ID')
        ->condition('orgterms.ORGANIZATION_ID', $this->session->getFocus('organization_id'))
        ->condition('orgterms.TERM_ID', $this->session->getFocus('term_id'))
        ->condition('stafforgterms.STAFF_ID', $this->session->get('user_id'))
        ->execute()->fetch();
      $this->session->setFocus('Teacher.Staff.OrgTerm', $staff_orgterm['STAFF_ORGANIZATION_TERM_ID'], $role_token);
      $this->session->setFocus('Teacher.Staff', $staff_orgterm['STAFF_ID'], $role_token);
      $this->session->setFocus('Teacher.Staff.Department', array($staff_orgterm['DEPARTMENT'], $staff_orgterm['DEPARTMENT_2'], $staff_orgterm['DEPARTMENT_3'], $staff_orgterm['DEPARTMENT_4']), $role_token);
      $this->session->setFocus('Teacher.Staff.Department.Head', $staff_orgterm['DEPARTMENT_HEAD'], $role_token);
    }
    
   // $this->setSectionFocus();
  }
  
  public function setStudentStatusFocusFromStudent($organization_id, $term_id, $student_id = null, $role_token = null) {

    if ($this->session->get('administrator') == '1') {
      
      // check for student status given organization, term, and student
      $student = $this->db->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'STUDENT_ID'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
        ->condition('orgterms.ORGANIZATION_ID', $organization_id)
        ->condition('orgterms.TERM_ID', $term_id)
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stustatus.STUDENT_ID');
      if ($student_id) {
        $student = $student->condition('stustatus.STUDENT_ID', $student_id);
      } 
      $student = $student->orderBy('cons.LAST_NAME')->orderBy('cons.FIRST_NAME');
      if (!$student_id) {
        $student = $student->range(0, 1);
      }
      $student = $student->execute()->fetch();
      
      // Only if student status returns set new focused student
      //if ($student['STUDENT_STATUS_ID']) {
      $this->setOrganizationTermFocus($organization_id, $term_id, $role_token);
      $this->setStudentStatusFocus($student['STUDENT_STATUS_ID'], $student['STUDENT_ID'], $role_token);
      // }
    } else {
      $this->setOrganizationTermFocus($organization_id, $term_id, $role_token);
      $this->setStudentStatusFocus();
    }
    
  }
  
  public function setStudentStatusFocus($student_status_id = null, $student_id = null, $role_token = null) {
    
    if ($student_status_id) {
      $this->session->setFocus('Student.HEd.Student.Status', $student_status_id, $role_token);
      if ($student_id) $this->session->setFocus('Student.HEd.Student', $student_id, $role_token);
    } elseif ($this->session->get('administrator') == '1') {
      if ($student_status_id) {
        $this->session->setFocus('Student.HEd.Student.Status', $student_status_id, $role_token);
        if ($student_id) $this->session->setFocus('Student.HEd.Student', $student_id, $role_token);
      } elseif ($this->session->getFocus('Student.HEd.Student.Status', $role_token) !== null) {

        // get student in new term
        $newStudent = $this->db->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'STUDENT_ID'))
        ->condition('stustatus.ORGANIZATION_TERM_ID', $this->getOrganizationTermIDs())
        ->join('STUD_STUDENT_STATUS', 'oldstudentstatus', 'oldstudentstatus.STUDENT_ID = stustatus.STUDENT_ID')
        ->condition('oldstudentstatus.STUDENT_STATUS_ID', $this->session->getFocus('Student.HEd.Student.Status', $role_token))
        ->execute()->fetch();

        if ($newStudent['STUDENT_STATUS_ID']) {
          $this->session->setFocus('Student.HEd.Student.Status', $newStudent['STUDENT_STATUS_ID'], $role_token);
        if ($student_id) $this->session->setFocus('Student.HEd.Student', $newStudent['STUDENT_ID'], $role_token);
        } else {
          // Set to first student in list
          $firstStudent = $this->db->db_select('STUD_STUDENT_STATUS', 'stustatus')
          ->fields('stustatus', array('STUDENT_STATUS_ID', 'STUDENT_ID'))
          ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stustatus.STUDENT_ID')
          ->condition('stustatus.ORGANIZATION_TERM_ID', $this->getOrganizationTermIDs())
          ->orderBy('cons.LAST_NAME')
          ->orderBy('cons.FIRST_NAME')
          ->range(0, 1)
          ->execute()->fetch();
          $this->session->setFocus('Student.HEd.Student.Status', $firstStudent['STUDENT_STATUS_ID'], $role_token);
          $this->session->setFocus('Student.HEd.Student', $firstStudent['STUDENT_ID'], $role_token);
        }
        
      } else {
        // Set to first teacher in list
        $firstStudent = $this->db->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'STUDENT_ID'))
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stustatus.STUDENT_ID')
        ->condition('stustatus.ORGANIZATION_TERM_ID', $this->getOrganizationTermIDs())
        ->orderBy('cons.LAST_NAME')
        ->orderBy('cons.FIRST_NAME')
        ->range(0, 1)
        ->execute()->fetch();
        $this->session->setFocus('Student.HEd.Student.Status', $firstStudent['STUDENT_STATUS_ID'], $role_token);
        $this->session->setFocus('Student.HEd.Student', $firstStudent['STUDENT_ID'], $role_token);
      }
      
    } else {
      // get student status id for currently set organization and term
      $student_status_id = $this->db->db_select('STUD_STUDENT_STATUS', 'stustatus')
        ->fields('stustatus', array('STUDENT_STATUS_ID', 'STUDENT_ID'))
        ->condition('stustatus.ORGANIZATION_TERM_ID', $this->getOrganizationTermIDs())
        ->condition('stustatus.STUDENT_ID', $this->session->get('user_id'));
      $student_status_id = $student_status_id->execute()->fetch();

      if ($student_status_id['STUDENT_STATUS_ID']) {
        $this->session->setFocus('Student.HEd.Student.Status', $student_status_id['STUDENT_STATUS_ID'], $role_token);
        $this->session->setFocus('Student.HEd.Student', $student_status_id['STUDENT_ID'], $role_token);
      }
      
    }
    
  }
  
  public function getStudentStatusID() {
    $session_focus = $this->session->get('focus');
    if (isset($session_focus['Student.HEd.Student.Status']))
      return $session_focus['Student.HEd.Student.Status'];
    else
      return false;
  }
  
  public function getStudentID($admin = false) {
    if ($admin AND $this->session->getFocus('admin_focus_student')) {
      return $this->session->getFocus('admin_focus_student');
    }
  }
  
  public function getStaffID($admin = false) {
    if ($admin AND $this->session->getFocus('admin_focus_teacher')) {
      return $this->session->getFocus('admin_focus_teacher');
    } else {
      return $this->session->getFocus('Teacher.Staff');
    }
  }
  
  public function getSchoolIDs() {
    if ($this->session->get('portal') == 'core') {
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
  
  public function getOrganizationTarget() {
    return $this->organization->getTarget($this->getOrganizationID());
  }
  
  public function getOrganizationID($admin = false) {
    if ($admin AND $this->session->getFocus('admin_focus_organization'))
      return $this->session->getFocus('admin_focus_organization');
    elseif ($this->session->getFocus('organization_id'))
      return $this->session->getFocus('organization_id');
    else
      return $this->session->get('organization_id');
  }
  
  public function getTermID($admin = false) {
    $session_focus = $this->session->get('focus');
    
    if ($admin AND isset($session_focus['admin_focus_term'])) {
      return $session_focus['admin_focus_term'];  
    } elseif (isset($session_focus['term_id']) AND $session_focus['term_id'] != 'ALL') {
      return $session_focus['term_id'];  
    } elseif (isset($session_focus['term_id']) AND $session_focus['term_id'] == 'ALL') {
      return $this->term->getAllTermIDs();
    } elseif ($this->session->get('term_id')) {  
      return $this->session->get('term_id');
    } else {
      return $this->term->getCurrentTermID();
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