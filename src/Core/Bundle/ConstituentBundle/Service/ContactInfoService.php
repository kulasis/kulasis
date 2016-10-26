<?php

namespace Kula\Core\Bundle\ConstituentBundle\Service;

class ContactInfoService {
  
  public function __construct($db, $poster) {
    $this->db = $db;
    $this->poster = $poster;
  }
  
  public function addAddress($id, $addressInfo) {

    $addressType = $addressInfo['Core.Constituent.Address.Type'];
    $constituentID = $addressInfo['Core.Constituent.Address.ConstituentID'];

    // end date all existing
    $this->db->db_update('CONS_ADDRESS')
      ->fields(array('ACTIVE' => 0))
      ->condition('ADDRESS_TYPE', $addressType)
      ->condition('CONSTITUENT_ID', $constituentID)
      ->execute();

    // add new address
    $addressID = $this->poster->newPoster()->add('Core.Constituent.Address', $id, $addressInfo)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getID();

    if ($addressType == 'R') $constituent_addrs['RESIDENCE_ADDRESS_ID'] = $addressID;
    if ($addressType == 'M') $constituent_addrs['MAILING_ADDRESS_ID'] = $addressID;
    if ($addressType == 'W') $constituent_addrs['WORK_ADDRESS_ID'] = $addressID;

    // set as primary for type
    $this->db->db_update('CONS_CONSTITUENT')
      ->fields($constituent_addrs)
      ->condition('CONSTITUENT_ID', $constituentID)
      ->execute();

    return $addressID;
  }

  public function addPhone($id, $addressInfo) {

    $addressType = $addressInfo['Core.Constituent.Phone.Type'];
    $constituentID = $addressInfo['Core.Constituent.Phone.ConstituentID'];

    // end date all existing
    $this->db->db_update('CONS_PHONE')
      ->fields(array('ACTIVE' => 0))
      ->condition('PHONE_TYPE', $addressType)
      ->condition('CONSTITUENT_ID', $constituentID)
      ->execute();

    // add new address
    $phoneID = $this->poster->newPoster()->add('Core.Constituent.Phone', $id, $addressInfo)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getID();

    // set as primary for type
    $this->db->db_update('CONS_CONSTITUENT')
      ->fields(array('PRIMARY_PHONE_ID' => $phoneID))
      ->condition('CONSTITUENT_ID', $constituentID)
      ->execute();

    return $phoneID;
  }
  
  public function addEmail($id, $addressInfo) {

    $addressType = $addressInfo['Core.Constituent.EmailAddress.Type'];
    $constituentID = $addressInfo['Core.Constituent.EmailAddress.ConstituentID'];

    // end date all existing
    $this->db->db_update('CONS_EMAIL_ADDRESS')
      ->fields(array('ACTIVE' => 0))
      ->condition('EMAIL_ADDRESS_TYPE', $addressType)
      ->condition('CONSTITUENT_ID', $constituentID)
      ->execute();

    // add new address
    $emailID = $this->poster->newPoster()->add('Core.Constituent.EmailAddress', $id, $addressInfo)->process(array('VERIFY_PERMISSIONS' => false, 'AUDIT_LOG' => false))->getID();

    // set as primary for type
    $this->db->db_update('CONS_CONSTITUENT')
      ->fields(array('PRIMARY_EMAIL_ID' => $emailID))
      ->condition('CONSTITUENT_ID', $constituentID)
      ->execute();

    return $emailID;
  }

}