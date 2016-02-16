<?php

namespace Kula\Core\Bundle\FrameworkBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FocusController extends Controller {

  public function set_focusAction() {
    return new Response('set focus');
  }
  
  public function focusAction() {
    
    $this->focus->setOrganizationTermFocus($this->request->get('focus_organization'), $this->request->get('focus_term'), $this->request->get('role_token'));
    
    $first_route = $this->get('kula.core.navigation')->getFirstRoute();
    if ($first_route) {
      return $this->redirect($this->get('router')->generate($first_route));
    } else {
      $this->get('kula.login')->logout();
      $this->addFlash('error', 'Unable to determine first route.  You have been logged out.');
      return $this->redirect('/login');
    }
  }
}