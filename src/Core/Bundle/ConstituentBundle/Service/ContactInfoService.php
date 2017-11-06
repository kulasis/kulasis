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
    $addressID = $this->poster->newPoster()->add('Core.Constituent.Address', $id, $addressInfo)->process(array('VERIFY_PERMISSIONS' => false))->getID();

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
    $phoneID = $this->poster->newPoster()->add('Core.Constituent.Phone', $id, $addressInfo)->process(array('VERIFY_PERMISSIONS' => false))->getID();

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
    $emailID = $this->poster->newPoster()->add('Core.Constituent.EmailAddress', $id, $addressInfo)->process(array('VERIFY_PERMISSIONS' => false))->getID();

    // set as primary for type
    $this->db->db_update('CONS_CONSTITUENT')
      ->fields(array('PRIMARY_EMAIL_ID' => $emailID))
      ->condition('CONSTITUENT_ID', $constituentID)
      ->execute();

    return $emailID;
  }

  public function syncCurrentEmail($sync_source_id, $sync_destination_id) {

    // Get active email address info from source
    $source_email = $this->db->db_select('CONS_EMAIL_ADDRESS', 'email')
      ->fields('email', array('EMAIL_ADDRESS', 'EMAIL_ADDRESS_TYPE', 'EFFECTIVE_DATE'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = email.CONSTITUENT_ID AND cons.PRIMARY_EMAIL_ID = email.EMAIL_ADDRESS_ID')
      ->condition('email.ACTIVE', 1)
      ->condition('email.UNDELIVERABLE', 0)
      ->condition('cons.CONSTITUENT_ID', $sync_source_id)
      ->execute()->fetch();

    // Get active email address info from destination
    $destination_email = $this->db->db_select('CONS_EMAIL_ADDRESS', 'email')
      ->fields('email', array('EMAIL_ADDRESS', 'EMAIL_ADDRESS_TYPE', 'EFFECTIVE_DATE'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = email.CONSTITUENT_ID AND cons.PRIMARY_EMAIL_ID = email.EMAIL_ADDRESS_ID')
      ->condition('email.ACTIVE', 1)
      ->condition('email.UNDELIVERABLE', 0)
      ->condition('cons.CONSTITUENT_ID', $sync_destination_id)
      ->execute()->fetch();

    // if not the same, insert the addresses into the destination.
    if ($source_email['EMAIL_ADDRESS'] != $destination_email['EMAIL_ADDRESS']) {
      $addressInfo = array(
        'Core.Constituent.EmailAddress.Type' => $source_email['EMAIL_ADDRESS_TYPE'],
        'Core.Constituent.EmailAddress.EmailAddress' => $source_email['EMAIL_ADDRESS'],
        'Core.Constituent.EmailAddress.ConstituentID' => $sync_destination_id,
        'Core.Constituent.EmailAddress.EffectiveDate' => $source_email['EFFECTIVE_DATE']
      );

      $this->addEmail($sync_source_id, $addressInfo);
    } // end if on if the email addresses are different

  }

  public function syncCurrentPhone($sync_source_id, $sync_destination_id) {

    // Get active phone number info from source
    $source_phone = $this->db->db_select('CONS_PHONE', 'phone')
      ->fields('phone', array('PHONE_NUMBER', 'PHONE_EXTENSION', 'ALLOW_TEXTING', 'PHONE_COUNTRY', 'PHONE_TYPE', 'EFFECTIVE_DATE'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = phone.CONSTITUENT_ID AND cons.PRIMARY_PHONE_ID = phone.PHONE_NUMBER_ID')
      ->condition('phone.ACTIVE', 1)
      ->condition('phone.DISCONNECTED', 0)
      ->condition('cons.CONSTITUENT_ID', $sync_source_id)
      ->execute()->fetch();

    // Get active phone number info from destination
    $destination_phone = $this->db->db_select('CONS_PHONE', 'phone')
      ->fields('phone', array('PHONE_NUMBER', 'PHONE_EXTENSION', 'ALLOW_TEXTING', 'PHONE_COUNTRY', 'PHONE_TYPE', 'EFFECTIVE_DATE'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = phone.CONSTITUENT_ID AND cons.PRIMARY_PHONE_ID = phone.PHONE_NUMBER_ID')
      ->condition('phone.ACTIVE', 1)
      ->condition('phone.DISCONNECTED', 0)
      ->condition('cons.CONSTITUENT_ID', $sync_destination_id)
      ->execute()->fetch();

    // if not the same, insert the addresses into the destination.
    if ($source_phone['PHONE_NUMBER'] != $destination_phone['PHONE_NUMBER']) {
      $addressInfo = array(
        'Core.Constituent.Phone.Type' => $source_phone['PHONE_TYPE'],
        'Core.Constituent.Phone.Number' => $source_phone['PHONE_NUMBER'],
        'Core.Constituent.Phone.Extension' => $source_phone['PHONE_EXTENSION'],
        'Core.Constituent.Phone.AllowTexting' => $source_phone['ALLOW_TEXTING'],
        'Core.Constituent.Phone.Country' => $source_phone['PHONE_COUNTRY'],
        'Core.Constituent.Phone.ConstituentID' => $sync_destination_id,
        'Core.Constituent.Phone.EffectiveDate' => $source_phone['EFFECTIVE_DATE']
      );

      $this->addPhone($sync_source_id, $addressInfo);
    } // end if on if the email addresses are different

  }

  public function syncCurrentAddresses($sync_source_id, $sync_destination_id) {

    // Get active addresses info from source
    $source_addresses_result = $this->db->db_select('CONS_ADDRESS', 'addr')
      ->fields('addr', array('ADDRESS_TYPE', 'EFFECTIVE_DATE', 'COUNTRY', 'ADMINISTRATIVE_AREA', 'LOCALITY', 'POSTAL_CODE', 'THOROUGHFARE'))
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = addr.CONSTITUENT_ID AND (RESIDENCE_ADDRESS_ID = addr.ADDRESS_ID OR MAILING_ADDRESS_ID = addr.ADDRESS_ID OR WORK_ADDRESS_ID = addr.ADDRESS_ID)')
      ->fields('cons', array('RESIDENCE_ADDRESS_ID', 'MAILING_ADDRESS_ID', 'WORK_ADDRESS_ID'))
      ->condition('addr.ACTIVE', 1)
      ->condition('addr.UNDELIVERABLE', 0)
      ->condition('cons.CONSTITUENT_ID', $sync_source_id)
      ->execute();

    while ($source_address_row = $source_addresses_result->fetch()) {

      // create condition part
      $condition = '';
      if ($source_address_row['RESIDENCE_ADDRESS_ID']) {
        $condition = 'cons.RESIDENCE_ADDRESS_ID IS NOT NULL';
      } elseif ($source_address_row['MAILING_ADDRESS_ID']) {
        $condition = 'cons.MAILING_ADDRESS_ID IS NOT NULL';
      } elseif ($source_address_row['WORK_ADDRESS_ID']) {
        $condition = 'cons.WORK_ADDRESS_ID IS NOT NULL';
      }

      // Get active phone addresses info from destination
      $destination_address = $this->db->db_select('CONS_ADDRESS', 'addr')
        ->fields('addr', array('ADDRESS_TYPE', 'EFFECTIVE_DATE', 'COUNTRY', 'ADMINISTRATIVE_AREA', 'LOCALITY', 'POSTAL_CODE', 'THOROUGHFARE'))
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = addr.CONSTITUENT_ID AND '.$condition)
        ->condition('addr.CONSTITUENT_ID', $sync_destination_id)
        ->execute()->fetch();

      // if not the same, insert the addresses into the destination.
      if ($source_address_row['THOROUGHFARE'] != $destination_address['THOROUGHFARE'] OR $source_address_row['LOCALITY'] != $destination_address['LOCALITY'] OR $source_address_row['ADMINISTRATIVE_AREA'] != $destination_address['ADMINISTRATIVE_AREA'] OR $source_address_row['COUNTRY'] != $destination_address['COUNTRY']) {
        $addressInfo = array(
          'Core.Constituent.Address.Type' => $source_address_row['ADDRESS_TYPE'],
          'Core.Constituent.Address.Thoroughfare' => $source_address_row['THOROUGHFARE'],
          'Core.Constituent.Address.Locality' => $source_address_row['LOCALITY'],
          'Core.Constituent.Address.AdministrativeArea' => $source_address_row['ADMINISTRATIVE_AREA'],
          'Core.Constituent.Address.PostalCode' => $source_address_row['POSTAL_CODE'],
          'Core.Constituent.Address.Country' => $source_address_row['COUNTRY'],
          'Core.Constituent.Address.ConstituentID' => $sync_destination_id,
          'Core.Constituent.Address.EffectiveDate' => $source_address_row['EFFECTIVE_DATE']
        );

        $this->addAddress($sync_source_id, $addressInfo);
      } // end if on if the email addresses are different

    } // end loop through addresses

  }

  public function synContactInfoForDependents($constituent_id) {

    // Get dependents for constituent
    $dependents_result = $this->db->db_select('CONS_RELATIONSHIP', 'rel')
      ->fields('rel', array('CONSTITUENT_ID'))
      ->condition('rel.RELATED_CONSTITUENT_ID', $constituent_id)
      ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = rel.CONSTITUENT_ID')
      ->fields('cons', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER'))
      ->execute();

    // Update contact addresses
    while ($dependent = $dependents_result->fetch()) {

      $this->syncCurrentEmail($constituent_id, $dependent['CONSTITUENT_ID']);
      $this->syncCurrentPhone($constituent_id, $dependent['CONSTITUENT_ID']);
      $this->syncCurrentAddresses($constituent_id, $dependent['CONSTITUENT_ID']);

    } // end while on updating dependents

  }

}