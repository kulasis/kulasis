<?php

namespace Kula\Core\Bundle\FrameworkBundle\Service;

class Session {
  
  private $db;
  private $session;
  private $request;
  
  public function __construct(\Symfony\Component\HttpFoundation\Session\Session $session,
                              \Kula\Core\Component\DB\DB $db, 
                              $request, $organization) {
      $this->db = $db;
      $this->session = $session;
      $this->request = $request;
      $this->organization = $organization;
  }
  
  /**
   *  This method loads the passed in user ID into the session.  If a session is already established for a user,
   *  then another user is added to the end of the array.
   *  @param int User ID of user to load
   */
  public function loadUser($user_id) {
    
    // Load user's first role
    $role = $this->loadRole($user_id);
    
    // if initial role isn't set, set to key
    if ($this->session->get('initial_role') == null AND $role != null) {
      $this->session->set('initial_role', $role);
    }
    
    if ($role) {
      return true;
    }
    
  }
  
  public function loadRole($user_id, $role_id = null) {
    
    // Load Role
    $role_info = $this->db->db_select('CORE_USER_ROLES', 'roles')
      ->fields('roles', array('ROLE_ID', 'ORGANIZATION_ID', 'TERM_ID', 'ADMINISTRATOR', 'LAST_ORGANIZATION_ID', 'LAST_TERM_ID'))
      ->join('CORE_USERGROUP', 'usergroups', 'usergroups.USERGROUP_ID = roles.USERGROUP_ID')
      ->fields('usergroups', array('USERGROUP_ID','USERGROUP_NAME', 'PORTAL'))
      ->join('CORE_USER', 'user', 'roles.USER_ID = user.USER_ID')
      ->fields('user', array('USERNAME', 'USER_ID'))
      ->join('CONS_CONSTITUENT', 'constituent', 'user.USER_ID = constituent.CONSTITUENT_ID')
      ->fields('constituent', array('FIRST_NAME', 'LAST_NAME'))
      ->condition('roles.USER_ID', $user_id)
      ->condition('roles.ACTIVE', 1);
    if ($role_id) {
      $role_info = $role_info->condition('roles.ROLE_ID', $role_id);
    }
    $role_info = $role_info->orderBy('DEFAULT_ROLE', 'DESC');
    
    $role_info = $role_info->execute()->fetch();
      
    $role = array(
        'username' => $role_info['USERNAME'],
        'first_name' => $role_info['FIRST_NAME'],
        'last_name' => $role_info['LAST_NAME'],
        'name' => $role_info['FIRST_NAME'].' '.$role_info['LAST_NAME'],
        'user_id' => $role_info['USER_ID'],
        'role_id' => $role_info['ROLE_ID'],
        'organization_id' => $role_info['ORGANIZATION_ID'],
        'term_id' => $role_info['TERM_ID'],
        'usergroup_id' => $role_info['USERGROUP_ID'],
        'usergroup_name' => $role_info['USERGROUP_NAME'],
        'portal' => $role_info['PORTAL'],
        'administrator' => $role_info['ADMINISTRATOR'],
        'last_organization_id' => $role_info['LAST_ORGANIZATION_ID'],
        'last_term_id' => $role_info['LAST_TERM_ID']
      );
    
    $role['focus'] = array(
      'organization_id' => ($role_info['LAST_ORGANIZATION_ID'] != '') ? $role_info['LAST_ORGANIZATION_ID'] : $role_info['ORGANIZATION_ID'],
    );
    
    $role['focus']['term_id'] = null;
    
    if ($role_info['LAST_TERM_ID'] != '') {
      $role['focus']['term_id'] = $role_info['LAST_TERM_ID'];
    } else {
      
      if (!$role['focus']['term_id'] AND $role_info['USERGROUP_NAME'] == 'Instructor') {
        $role['focus']['term_id'] = $this->firstTermForTeacherRole($role['role_id'], $role['focus']['organization_id']);
      } elseif (!$role['focus']['term_id'] AND $role_info['USERGROUP_NAME'] == 'Student') {
        $role['focus']['term_id'] = $this->firstTermForStudentRole($role['role_id'], $role['focus']['organization_id']);
      } else {
        // Load latest term
        $latest_term = $this->currentTermForOrganization($role['focus']['organization_id']);
        $role['focus']['term_id'] = $latest_term['TERM_ID'];
      } 
    }
    
    // Get target
    $role['focus']['target'] = $this->organization->getTarget($role['focus']['organization_id']);
    
    // Log session and get session ID and token
    $role['session_id'] = $this->logOpenedSession($role['user_id'], $role['role_id'], $role['focus']['organization_id'], $role['focus']['term_id']);
    
    // Generate random string
    $role_key = time() . \Kula\Core\Component\Utility\RandomGenerator::string(5);
    
    // Append to session
    $session_roles = $this->session->get('roles');
    $session_roles[$role_key] = $role;
    $this->session->set('roles', $session_roles);
    
    return $role_key;
  }
  
  public function changeRole($new_role_id, $role_token = null) {
    
    if ($role_token === null) {
      $role_token = $this->session->get('initial_role');
    }

    $current_role = $this->session->get('roles')[$role_token];
    
    // Close session
    $this->logClosedSession($current_role['session_id']);
    
    // Load new role
    $new_role = $this->loadRole($current_role['user_id'], $new_role_id);
    
    // Remove old role
    $this->session->set('initial_role', $new_role);
    // Get roles first
    $roles = $this->session->get('roles');
    unset($roles[$role_token]);
    $this->session->set('roles', $roles);

  }
  
  public function get($key, $role_token = null) {
    
    $roles = $this->session->get('roles');
    
    if ($role_token === null) {
      $role_token = $this->session->get('initial_role');
    }

    if (isset($roles[$role_token][$key])) {
      return $roles[$role_token][$key];
    } else {
      return $this->session->get($key);
    }
  }
  
  public function getFocus($key, $role_token = null) {
    $focus = $this->get('focus', $role_token);
    if (isset($focus[$key])) {
      return $focus[$key];
    }
  }
  
  public function setFocus($key, $value, $role_token = null) {
    
    if ($role_token === null) {
      $role_token = $this->session->get('initial_role');
    }
    $roles = $this->session->get('roles');
    $roles[$role_token]['focus'][$key] = $value;
    $this->session->set('roles', $roles);
  }
  
  public function set($key, $value) {
    $this->session->set($key, $value);
  }

  public function invalidate() {
    $this->session->invalidate();
  }
  
  private function logOpenedSession($user_id, $role_id, $organization_id, $term_id = null) {
    $session_data = array(
      'USER_ID' => $user_id,
      'ROLE_ID' => $role_id ? $role_id : null,
      'ORGANIZATION_ID' => $organization_id ? $organization_id : null,
      'TERM_ID' => $term_id,
      'IN_TIME' => date('Y-m-d H:i:s'),
      'IP_ADDRESS' => isset($this->request->getCurrentRequest()->server) ? $this->request->getCurrentRequest()->server->get('REMOTE_ADDR') : null,
    );
    $session_id = $this->db->db_insert('LOG_SESSION')->fields($session_data)->execute();
    return $session_id;
  }
  
  private function logClosedSession($session_id) {
    try {
      return $this->db->db_update('LOG_SESSION')->fields(array('OUT_TIME' => date('Y-m-d H:i:s')))->condition('SESSION_ID', $session_id)->execute();
    } catch (\Exception $e) {
      return false;
    }
  }
  
  private function currentTermForOrganization($organization_ids) {
    $term_results = $this->db->db_select('CORE_TERM', 'terms')
      ->fields('terms', array('TERM_ID', 'TERM_ABBREVIATION', 'TERM_NAME', 'FINANCIAL_AID_YEAR'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.TERM_ID = terms.TERM_ID')
      ->condition('orgterm.ORGANIZATION_ID', $organization_ids)
      ->condition('END_DATE', date('Y-m-d', strtotime('-7 days')), '>=')
      ->orderBy('START_DATE', 'ASC')
      ->orderBy('END_DATE', 'ASC')
      ->execute()
      ->fetch();
    return $term_results;
  }
  
  private function termInEnrollmentHistory($role_id, $term_id, $organization_id) {
    $first_term_results = $this->db->db_select('CORE_USER_ROLES', 'role')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = role.USER_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->fields('orgterms', array('TERM_ID'))
      ->condition('role.ROLE_ID', $role_id)
      ->condition('orgterms.ORGANIZATION_ID', $organization_id)
      ->condition('orgterms.TERM_ID', $term_id)
      ->execute()->fetch();
    
    if ($first_term_results['TERM_ID'] != '')
      return true;
    else
      return false;
  }
  
  private function firstTermForTeacherRole($role_id, $organization_id) {
    $first_term_results = $this->db->db_select('CORE_USER_ROLES', 'role')
      ->join('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterms', 'stafforgterms.STAFF_ID = role.USER_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stafforgterms.ORGANIZATION_TERM_ID')
      ->fields('orgterms', array('TERM_ID'))
      ->condition('role.ROLE_ID', $role_id)
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      //->condition('term.END_DATE', date('Y-m-d', strtotime('-7 days')), '>=')
      ->condition('orgterms.ORGANIZATION_ID', $organization_id)
      ->orderBy('START_DATE', 'DESC')
      ->execute()->fetch();
    return $first_term_results['TERM_ID'];
  }
  
  private function firstTermForStudentRole($role_id, $organization_id) {
    $first_term_results = $this->db->db_select('CORE_USER_ROLES', 'role')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = role.USER_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->fields('orgterms', array('TERM_ID'))
      ->condition('role.ROLE_ID', $role_id)
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->condition('term.END_DATE', date('Y-m-d', strtotime('-7 days')), '>=')
      ->condition('orgterms.ORGANIZATION_ID', $organization_id)
      ->execute()->fetch();
    return $first_term_results['TERM_ID'];
  }
  
}