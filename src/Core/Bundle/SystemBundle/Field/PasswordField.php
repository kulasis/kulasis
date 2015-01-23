<?php

namespace Kula\Core\Bundle\SystemBundle\Field;

use Kula\Core\Component\Field\Field;

class PasswordField extends Field {
  
  public function save($data) {
    
    if ($data != '') {
      $auth_obj = $this->container->get('kula.login.auth.local');
      $password_hash = $auth_obj->createHashForPassword($data);
      return $password_hash;
    }
    
    return self::REMOVE_FIELD;
  }
  
}