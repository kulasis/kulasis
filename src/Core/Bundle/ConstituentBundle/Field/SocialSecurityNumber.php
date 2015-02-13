<?php

namespace Kula\Core\Bundle\ConstituentBundle\Field;

use Kula\Core\Component\Field\Field;

class SocialSecurityNumber extends Field {
  
  public function calculate($data) {
    return $this->container->get('kula.core.db')->db_select('CONS_CONSTITUENT')
      ->expression("AES_DECRYPT(SOCIAL_SECURITY_NUMBER, :ssn_key)", 'encrypted_ssn', array('ssn_key' => $this->container->getParameter('ssn_key')))
      ->condition('CONSTITUENT_ID', $data)
      ->execute()->fetch()['encrypted_ssn'];
    
  }
  
  public function save($data, $id = null) {

    if ($id AND $data != '') {
      $this->container->get('kula.core.db')->db_update('CONS_CONSTITUENT')->expression('SOCIAL_SECURITY_NUMBER', 'AES_ENCRYPT(:data, :key)', array('data' => $data, 'key' => $this->container->getParameter('ssn_key')))->condition('CONSTITUENT_ID', $id)->execute();
    } else {
      $this->container->get('kula.core.db')->db_update('CONS_CONSTITUENT')->fields(array('SOCIAL_SECURITY_NUMBER' => null))->condition('CONSTITUENT_ID', $id)->execute();
    }
    return self::REMOVE_FIELD;
  }
  
  public function removeFromAuditLog() {
    return true;
  }
  
}