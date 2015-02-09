<?php

namespace Kula\Core\Bundle\FrameworkBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FocusController extends Controller {

  public function set_focusAction() {
    return new Response('set focus');
  }
}