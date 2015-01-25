<?php

namespace Kula\Core\Bundle\ConstituentBundle\Field;

use Kula\Core\Component\Field\Field;

class SocialSecurityNumber extends Field {
  
  public function calculate($data) {
    
    return $this->container->get('kula.core.db')->db_select('CONS_CONSTITUENT')
      ->expressions(array("AES_DECRYPT(SOCIAL_SECURITY_NUMBER, '".$GLOBALS['ssn_key']."')" => 'encrypted_ssn'))
      ->condition('CONSTITUENT_ID', $data)
      ->execute()->fetch()['encrypted_ssn'];
    
  }
  
  public function save($data, $id = null) {

    $mysqli = new \mysqli($GLOBALS['databases'][0]['host'], $GLOBALS['databases'][0]['username'], $GLOBALS['databases'][0]['password'], $GLOBALS['databases'][0]['dbname']);

    if ($id AND $data != '') {
      \Kula\Component\Database\DB::connect('write')->parentQuery("UPDATE CONS_CONSTITUENT SET SOCIAL_SECURITY_NUMBER = AES_ENCRYPT('".$mysqli->real_escape_string($data)."', '".$GLOBALS['ssn_key']."')
          WHERE CONSTITUENT_ID = '".$id."'");
    } else {
      \Kula\Component\Database\DB::connect('write')->parentQuery("UPDATE CONS_CONSTITUENT SET SOCIAL_SECURITY_NUMBER = NULL WHERE CONSTITUENT_ID = '".$id."'");
    }
    return 'remove_field';
  }
  
  public function expression() {
    
    return "AES_DECRYPT(SOCIAL_SECURITY_NUMBER, '".$GLOBALS['ssn_key']."')";
    
  }
  
}