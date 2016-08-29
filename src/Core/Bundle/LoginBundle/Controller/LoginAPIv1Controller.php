<?php

namespace Kula\Core\Bundle\LoginBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

class LoginAPIv1Controller extends APIController {

  public function userAuthenticateAction() {
    $this->authorize();

    // Get post info
    /*
      username => username to authenticate (required)
      password => password (optional)
    
      returns User ID
    */

    $username = $this->getRequest()->request->get('username');
    $password = $this->getRequest()->request->get('password');

    $or_predicate = $this->db()->db_or();
    $or_predicate = $or_predicate->condition('USERNAME', $username);
    $or_predicate = $or_predicate->condition('USERNAME', $username.'@ocac.edu');

    $user = $this->db()->db_select('CORE_USER', 'user')
      ->fields('user', array('USER_ID', 'USERNAME', 'PASSWORD'))
      ->condition($or_predicate)
      ->execute()->fetch();

    if ($user['USER_ID'] > 0) {
      // check for password, if one
      if ($password != '') {

        if ($this->get('kula.login.auth.local')->verifyPassword($password, $user['PASSWORD'])) {
          return $this->JSONResponse(array('id' => $user['USER_ID']));
        } else {
          return $this->JSONResponse(array());
        }

      } else {
        return $this->JSONResponse(array('id' => $user['USER_ID']));
      }

    } else {
      return $this->JSONResponse(array());
    }

    
  }
  

}