<?php

namespace Kula\Core\Bundle\SystemBundle\Service;

class UserService {
  
  public function __construct($db, $poster) {
    $this->db = $db;
    $this->poster = $poster;
  }
  
  public function createUser($userInfo) {

    // Post data
    $userPoster = $this->poster->newPoster();
    $userPoster->add('Core.User', 'new', $userInfo);
    $userPoster->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false));
    // Get user ID
    return $userPoster->getPosterRecord('Core.User', 'new')->getField('Core.User.ID');

  }

  public function updateUser($userInfo) {



  }
  
}