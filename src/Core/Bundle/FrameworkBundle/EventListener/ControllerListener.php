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
  
  public static function getSubscribedEvents()
  {
      return array(
          KernelEvents::CONTROLLER => 'updateFocus',
      );
  }
  
  public function updateFocus(FilterControllerEvent $event) {
    $this->event = $event;

    if ($this->session->get('initial_role') > 0 AND $this->request->get('_route') != 'logout') {
      
      // Set focus for user token
      $this->focus->setOrganizationTermFocus($this->getFromRequest('focus_org'), $this->getFromRequest('focus_term'), $this->getFromRequest('role_token'));
      
      if ($this->session->get('portal') == 'teacher') {
        
          // if administrator allow changing teacher focus
          if ($this->session->get('administrator') == '1') {
            if ($this->getFromRequest('focus_teacher')) {
              $this->focus->setTeacherOrganizationTermFocus($this->getFromRequest('focus_teacher'), $this->getFromRequest('role_token'));
              $this->focus->setSectionFocus(null, $this->getFromRequest('role_token'));
              $event->setController(array(new \Kula\Core\Bundle\FrameworkBundle\Controller\FocusController, 'set_focusAction'));
            } elseif (($this->getFromRequest('focus_org') OR $this->getFromRequest('focus_term')) AND $this->request->get('_route') != 'focus_usergroup_change') {
              $this->focus->setTeacherOrganizationTermFocus(null, $this->getFromRequest('role_token'));
              $this->focus->setSectionFocus(null, $this->getFromRequest('role_token'));
              $event->setController(array(new \Kula\Core\Bundle\FrameworkBundle\Controller\FocusController, 'set_focusAction'));
            }
            // if focus_org or focus_term changed
          } elseif ($this->getFromRequest('focus_section') !== null  OR ($this->request->get('_route') != 'focus_usergroup_change' AND ($this->getFromRequest('focus_org') OR $this->getFromRequest('focus_term')))) {
            $this->focus->setTeacherOrganizationTermFocus();
            $this->focus->setSectionFocus(null);
            $event->setController(array(new \Kula\Core\Bundle\FrameworkBundle\Controller\FocusController, 'set_focusAction'));
          } // end if administrator

          if ($this->getFromRequest('focus_section')) {
            $this->focus->setSectionFocus($this->getFromRequest('focus_section'), $this->getFromRequest('role_token'));
            $event->setController(array(new \Kula\Core\Bundle\FrameworkBundle\Controller\FocusController, 'set_focusAction'));
          } 
      
          if ($this->focus->getSectionID() == null) {
            $this->focus->setTeacherOrganizationTermFocus();
            $this->focus->setSectionFocus(null);
          }
        
      }
      
    }
    
    return $event;
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