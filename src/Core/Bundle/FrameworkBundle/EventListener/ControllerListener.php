<?php

namespace Kula\Core\Bundle\FrameworkBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ControllerListener implements EventSubscriberInterface {

  private $event;
  private $request;
  private $focus;
  private $session;

  public function __construct($request, $focus, $session) {
      $this->request = $request->getCurrentRequest();
      $this->focus = $focus;
      $this->session = $session;
  }
  
  public static function getSubscribedEvents() {
      return array(
          KernelEvents::CONTROLLER => 'updateFocus',
      );
  }
  
  public function updateFocus(FilterControllerEvent $event) {
    
    $this->event = $event;

    if ($this->session->get('initial_role') > 0 AND $this->request->get('_route') != 'logout') {
      
      if ($this->session->get('portal') == 'core') {
      
        // Set focus for user token
        $this->focus->setOrganizationTermFocus($this->getFromRequest('focus_org'), $this->getFromRequest('focus_term'), $this->getFromRequest('role_token'));
      
      }
      
      if ($this->session->get('portal') == 'teacher') {
        // if administrator allow changing teacher focus
        if ($this->session->get('administrator') == '1') {
          $this->processFocusForTeacherAdmin();
        } else {
          $this->processFocusForTeacher();
        }
      }
      
      if ($this->session->get('portal') == 'student') {
        $this->processFocusForStudent();
      }
      
    }
    
    return $event;
  }
  
  private function processFocusForTeacher() {
    
    // If inital login
    if (!$this->session->getFocus('Teacher.HEd.Section')) {
      $this->focus->setTeacherOrganizationTermFocus(null, $this->getFromRequest('role_token'));
      $this->focus->setSectionFocus(null, $this->getFromRequest('role_token'));
      return;
    }
    
    if ($this->getFromRequest('focus_school') AND $this->getFromRequest('focus_term')) {
    if ($this->getFromRequest('focus_school') != $this->session->getFocus('organization_id') OR $this->getFromRequest('focus_term') != $this->session->getFocus('term_id')) {
      $this->focus->setOrganizationTermFocus($this->getFromRequest('focus_school'), $this->getFromRequest('focus_term'), $this->getFromRequest('role_token'));
      $this->focus->setTeacherOrganizationTermFocus(null, $this->getFromRequest('role_token'));
      $this->focus->setSectionFocus(null, $this->getFromRequest('role_token'));
      $this->focus->setAdvisorStudentFocus(null, $this->getFromRequest('role_token'));
      return;
    }
    }

    // If changing term school or term
    if ($this->getFromRequest('focus_section') AND $this->getFromRequest('focus_section') != $this->session->getFocus('Teacher.HEd.Section')) {
      error_log("changed section ".$this->getFromRequest('focus_section')."\r\n", 3, "/var/tmp/test-error.log");
      $this->focus->setSectionFocus($this->getFromRequest('focus_section'), $this->getFromRequest('role_token'));
    }
    
    if ($this->getFromRequest('focus_advisee') AND $this->getFromRequest('focus_advisee') != $this->session->getFocus('Teacher.HEd.Advisor.Student')) {
      error_log("changed advisee ".$this->getFromRequest('focus_advisee')."\r\n", 3, "/var/tmp/test-error.log");
      $this->focus->setAdvisorStudentFocus($this->getFromRequest('focus_advisee'), $this->getFromRequest('role_token'));
    }
       
  }
  
  private function processFocusForTeacherAdmin() {
    
    if ($this->getFromRequest('admin_focus_organization') OR $this->getFromRequest('admin_focus_term') OR $this->getFromRequest('admin_focus_teacher')) {
      $this->session->setFocus('admin_focus_organization', $this->getFromRequest('admin_focus_organization'));
      $this->session->setFocus('admin_focus_term', $this->getFromRequest('admin_focus_term'));
      $this->session->setFocus('admin_focus_teacher', $this->getFromRequest('admin_focus_teacher'));
      
      $this->focus->setTeacherStaffFocusFromStaff($this->session->getFocus('admin_focus_organization'), $this->session->getFocus('admin_focus_term'), $this->session->getFocus('admin_focus_teacher'));
      $this->focus->setSectionFocus(null, $this->getFromRequest('role_token'));
    } elseif (($this->getFromRequest('focus_school') OR $this->getFromRequest('focus_term') AND $this->request->get('_route') != 'focus_usergroup_change')) {
      $this->focus->setTeacherOrganizationTermFocus(null, $this->getFromRequest('role_token'));
      $this->focus->setSectionFocus($this->getFromRequest('focus_section'), $this->getFromRequest('role_token')); 
      
    } else {
      // initial load
      $this->focus->setTeacherStaffFocusFromStaff($this->session->getFocus('organization_id'), $this->session->getFocus('term_id'));
      $this->focus->setSectionFocus(null, $this->getFromRequest('role_token'));
    }
    
   

  }
  
  private function processFocusForStudent() {
    
    // if administrator allow changing teacher focus
    if ($this->session->get('administrator') == '1' AND $this->request->get('_route') != 'focus_usergroup_change') {
      if ($this->getFromRequest('admin_focus_organization') OR $this->getFromRequest('admin_focus_term') OR $this->getFromRequest('admin_focus_student')) {
      $this->session->setFocus('admin_focus_organization', $this->getFromRequest('admin_focus_organization'));
      $this->session->setFocus('admin_focus_term', $this->getFromRequest('admin_focus_term'));
      $this->session->setFocus('admin_focus_student', $this->getFromRequest('admin_focus_student'));

      $this->focus->setStudentStatusFocusFromStudent($this->session->getFocus('admin_focus_organization'), $this->session->getFocus('admin_focus_term'), $this->session->getFocus('admin_focus_student'));
      }
    }
    
    $this->focus->setStudentStatusFocusFromStudent($this->getFromRequest('focus_org'), $this->getFromRequest('focus_term'), null, $this->getFromRequest('role_token'));
  }
  
  private function getFromRequest($key) {
    
    $query = $this->request->query->get($key);
    $request = $this->request->request->get($key);
    
    if (isset($request)) {
      return $request;
    } elseif (isset($query)) {
      return $query;
    } else {
      return null;
    }
  
  }
}