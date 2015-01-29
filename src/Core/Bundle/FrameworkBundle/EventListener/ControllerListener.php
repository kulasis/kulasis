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