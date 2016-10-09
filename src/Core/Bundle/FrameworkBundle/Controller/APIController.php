<?php

/**
 * @author Makoa Jacobsen <makoa@makoajacobsen.com>
 * @copyright Copyright (c) 2014, Oregon College of Art & Craft
 * @license MIT
 *
 * @package Kula SIS
 * @subpackage Core
 *
 * Base controller that includes all Kula SIS\Core functionality. May be extended
 * further to include additional functionality.
 */

namespace Kula\Core\Bundle\FrameworkBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class APIController extends BaseController {

  public function authorize($tables = null) {
    // Check if API key authroized
    
    // Get current request
    $request = $this->getRequest();

    $auth_api_service = $this->get('kula.login.auth.api');
    
    $auth_header = $this->getRequest()->headers->get('Authorization');
    $token = substr($auth_header, strpos($auth_header, 'Bearer ') + 7, strlen($auth_header));

    if ($auth_api_service->verifyApplicationToken(
        $token, 
        gethostbyaddr($this->getRequest()->getClientIp()), 
        $this->getRequest()->getClientIp()
        )
    ) {
      return true;
    } else {
      throw new UnauthorizedHttpException('Invalid API Key, Host, and IP combination. IP: '.$this->getRequest()->getClientIp().' Host: '.gethostbyaddr($this->getRequest()->getClientIp()));
    }
      
  }

  public function authorizeUser($tables = null) {

    // Get current request
    $request = $this->getRequest();

    $auth_api_service = $this->get('kula.login.auth.api');
    
    $auth_header = $this->getRequest()->headers->get('Authorization');
    $token = substr($auth_header, strpos($auth_header, 'Bearer ') + 7, strlen($auth_header));

    if ($user = $auth_api_service->verifyLoggedInUser($token)) {
      return $user;
    } else {
      throw new UnauthorizedHttpException('Invalid User.');
    }

  }

}