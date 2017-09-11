<?php

namespace Kula\Core\Bundle\LoginBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class APIv1LoginController extends APIController {

  public function authenticateApplicationAction() {
    $app_id = $this->getRequest()->request->get('app_id');
    $app_secret = $this->getRequest()->request->get('app_secret');
    $host = gethostbyaddr($this->getRequest()->getClientIp());
    $ip = $this->getRequest()->getClientIp();

    $login_info = $this->get('kula.login.auth.api')->authenticateApplication($app_id, $app_secret, $host, $ip);

    if ($login_info) {
      return $this->JSONResponse(array('token' => $login_info['token']));
    } else {
      throw new UnauthorizedHttpException('Invalid API Key, Host, and IP combination. IP: '.$ip.' Host: '.$host);
    }
    
  }

  public function authenticateUserAction() {
    $this->authorize();

    // Get post info
    /*
      username => username to authenticate (required)
      password => password (optional)
    
      returns User ID
    */
    $username = $this->getRequest()->request->get('username');
    $password = $this->getRequest()->request->get('password');

    $login_info = $this->get('kula.login.auth.api')->authenticateUser($username, $password);

    if ($login_info) {
      return $this->JSONResponse(array('user_id' => $login_info['user_id'], 'token' => $login_info['token']));
    } else {

      // create email if it exists
      $user = $this->get('kula.login.auth.api')->verifyEmail($username);

      // try again
      if ($login_info = $this->get('kula.login.auth.api')->authenticateUser($username)) {
        return $this->JSONResponse(array('user_id' => $login_info['user_id'], 'token' => $login_info['token']));
      } else {
        throw new NotFoundHttpException('No email address found.');
      }

      throw new UnauthorizedHttpException('Invalid Username/Password.');
    }
    
  }

  public function testAuthenticatedUserAction() {
    $this->authorizeUser();

    return $this->JSONResponse(array('status' => 'yay!'));
  }

}