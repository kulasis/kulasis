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
    $ip = $this->getRequest()->getClientIp();
    $host = gethostbyaddr($this->getRequest()->getClientIp());

    if ($auth_api_service->verifyApplicationToken(
        $token, 
        $host, 
        $ip)
    ) {
      return true;
    } else {
      throw new UnauthorizedHttpException('Invalid API Key, Host, and IP combination. IP: '.$ip.' Host: '.$host);
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

  public function authorizeConstituent($user_id) {

    $user = $this->authorizeUser();

    $authorizedConstituents = array($user);

    // get all related constituents
    $related_constituent = $this->db()->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('CONSTITUENT_ID'))
      ->condition('rel.RELATED_CONSTITUENT_ID', $user)
      ->condition('rel.CONSTITUENT_ID', $user_id)
      ->execute()->fetch();
    if ($related_constituent['CONSTITUENT_ID'] != '' OR $user == $user_id) {
      return true;
    } else {
      throw new UnauthorizedHttpException('Invalid related user.');
    }

  }

}