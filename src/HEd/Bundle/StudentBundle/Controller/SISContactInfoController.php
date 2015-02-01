<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISContactInfoController extends Controller {
  
  public function addressesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    $addresses = array();
    $primary_addresses = array();
    
    if ($this->poster()) {
    $address_result = $this->poster()->getAddedIDs('Core.Constituent.Address');
      // New phone number
      if ($address_result) {
        // Get New Phones Number ID
        $new_ids = array_values($address_result);
        // Query to see which are primary
        $new_addresses = $this->db()->db_select('CONS_ADDRESS', 'addr')
          ->fields('addr', array('ADDRESS_ID', 'ADDRESS_TYPE'))
          ->condition('ADDRESS_ID', $new_ids)
          ->execute();
        $constituent_addrs = array();
        $student_addrs = array();
        while ($new_address = $new_addresses->fetch()) {
          if ($new_address['ADDRESS_TYPE'] == 'R') $constituent_addrs['RESIDENCE_ADDRESS_ID'] = $new_address['ADDRESS_ID'];
          if ($new_address['ADDRESS_TYPE'] == 'M') $constituent_addrs['MAILING_ADDRESS_ID'] = $new_address['ADDRESS_ID'];
          if ($new_address['ADDRESS_TYPE'] == 'W') $constituent_addrs['WORK_ADDRESS_ID'] = $new_address['ADDRESS_ID'];
          if ($new_address['ADDRESS_TYPE'] == 'H') $student_addrs['HOME_ADDRESS_ID'] = $new_address['ADDRESS_ID'];
          if ($new_address['ADDRESS_TYPE'] == 'P') $student_addrs['PARENT_ADDRESS_ID'] = $new_address['ADDRESS_ID'];
          if ($new_address['ADDRESS_TYPE'] == 'B') $student_addrs['BILLING_ADDRESS_ID'] = $new_address['ADDRESS_ID'];
        }
        
        if (count($constituent_addrs) > 0) {
        $this->db()->db_update('CONS_CONSTITUENT')
          ->fields($constituent_addrs)
          ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
          ->execute();
        }
        
        if (count($student_addrs) > 0) {
        $this->db()->db_update('STUD_STUDENT')
          ->fields($student_addrs)
          ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
          ->execute();
        }
      }
    }
    
    $address_types = $this->db()->db_select('CORE_LOOKUP_VALUES', 'values')
      ->fields('values', array('DESCRIPTION' => 'ADDRESS_TYPE_DESCRIPTION', 'CODE' => 'ADDRESS_TYPE_CODE'))
      ->join('CORE_LOOKUP_TABLES', 'tables', 'tables.LOOKUP_TABLE_ID = values.LOOKUP_TABLE_ID')
      ->condition('LOOKUP_TABLE_NAME', 'Constituent.Address.Type')
      ->orderBy('SORT', 'ASC');
    $address_types = $address_types
      ->execute()
      ->fetchAll();
    
    if ($this->record->getSelectedRecordID()) {
      $addresses_result = $this->db()->db_select('CONS_ADDRESS', 'addr')
        ->fields('addr', array('ADDRESS_ID', 'ADDRESS_TYPE', 'EFFECTIVE_DATE', 'RECIPIENT', 'THOROUGHFARE', 'ADMINISTRATIVE_AREA', 'LOCALITY', 'POSTAL_CODE', 'COUNTRY', 'SEND_GRADES', 'SEND_BILL', 'ACTIVE', 'UNDELIVERABLE'))
        ->join('CORE_LOOKUP_VALUES', 'addresstype', 'addresstype.CODE = addr.ADDRESS_TYPE')
        ->join('CORE_LOOKUP_TABLES', 'addresstypetable', 'addresstypetable.LOOKUP_TABLE_ID = addresstype.LOOKUP_TABLE_ID')
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->condition('LOOKUP_TABLE_NAME', 'Constituent.Address.Type')
        ->orderBy('SORT', 'ASC', 'addresstype')
        ->orderBy('EFFECTIVE_DATE', 'DESC')
        ->execute();
      $i = 0;
      $last_address_type = '';
      while ($address_row = $addresses_result->fetch()) {
        if ($last_address_type != $address_row['ADDRESS_TYPE'])
          $i = 0;
        $addresses[$address_row['ADDRESS_TYPE']][$i] = $address_row;
        $i++;
        $last_address_type = $address_row['ADDRESS_TYPE'];
      }
      
      // Get primary values
      $constituent_primary_addresses = $this->db()->db_select('CONS_CONSTITUENT', 'cons')
        ->fields('cons', array('RESIDENCE_ADDRESS_ID', 'MAILING_ADDRESS_ID', 'WORK_ADDRESS_ID'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
      $primary_addresses['Core.Constituent.ResidenceAddressID'] = $constituent_primary_addresses['RESIDENCE_ADDRESS_ID'];
      $primary_addresses['Core.Constituent.MailingAddressID'] = $constituent_primary_addresses['MAILING_ADDRESS_ID'];
      
      $student_primary_addresses = $this->db()->db_select('STUD_STUDENT', 'stu')
        ->fields('stu', array('HOME_ADDRESS_ID', 'BILLING_ADDRESS_ID', 'PARENT_ADDRESS_ID'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
      $primary_addresses['HEd.Student.HomeAddressID'] = $student_primary_addresses['HOME_ADDRESS_ID'];
      $primary_addresses['HEd.Student.ParentAddressID'] = $student_primary_addresses['PARENT_ADDRESS_ID'];
      $primary_addresses['HEd.Student.BillingAddressID'] = $student_primary_addresses['BILLING_ADDRESS_ID'];
      
    }
    return $this->render('KulaHEdStudentBundle:SISContactInfo:addresses.html.twig', array('address_types' => $address_types, 'addresses' => $addresses, 'primary_addresses' => $primary_addresses));
  }
  
  public function detailAction($id, $sub_id) {
    $this->authorize();
    $this->setRecordType('SIS.HEd.Student');
    
    $address = $this->db()->db_select('CONS_ADDRESS', 'CONS_ADDRESS')
      ->fields('CONS_ADDRESS', array('ADDRESS_ID', 'ADDRESS_TYPE', 'THOROUGHFARE', 'ADMINISTRATIVE_AREA', 'LOCALITY', 'POSTAL_CODE', 'COUNTRY', 'NOTES', 'CREATED_USERSTAMP', 'CREATED_TIMESTAMP', 'UPDATED_USERSTAMP', 'UPDATED_TIMESTAMP'))
      ->join('CORE_LOOKUP_VALUES', 'addresstype', 'addresstype.CODE = CONS_ADDRESS.ADDRESS_TYPE')
      ->join('CORE_LOOKUP_TABLES', 'addresstypetable', 'addresstypetable.LOOKUP_TABLE_ID = addresstype.LOOKUP_TABLE_ID')
      ->fields('addresstype', array('DESCRIPTION' => 'addresstype_description'))
      ->leftJoin('CONS_CONSTITUENT', 'created_user', 'created_user.CONSTITUENT_ID = CONS_ADDRESS.CREATED_USERSTAMP')
      ->fields('created_user', array('LAST_NAME' => 'createduser_LAST_NAME', 'FIRST_NAME' => 'createduser_FIRST_NAME'))
      ->leftJoin('CONS_CONSTITUENT', 'updated_user', 'updated_user.CONSTITUENT_ID = CONS_ADDRESS.UPDATED_USERSTAMP')
      ->fields('updated_user', array('LAST_NAME' => 'updateduser_LAST_NAME', 'FIRST_NAME' => 'updateduser_FIRST_NAME'))
      ->condition('CONS_ADDRESS.CONSTITUENT_ID', $this->record->getSelectedRecordID())
      ->condition('ADDRESS_ID', $sub_id)
      ->condition('addresstypetable.LOOKUP_TABLE_NAME', 'Constituent.Address.Type')
      ->execute()->fetch();
    
    return $this->render('KulaHEdStudentBundle:SISContactInfo:addresses_detail.html.twig', array('address' => $address));
  }
  
  public function add_addressAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    if ($this->poster()) {
      $address_result = $this->poster()->getAddedIDs('Core.Constituent.Address');
      if ($address_result) {  
        return $this->forward('sis_HEd_student_information_addresses', array('record_type' => 'SIS.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()), array('record_type' => 'SIS.HEd.Student', 'record_id' => $this->record->getSelectedRecordID()));
      }
    }
    
    return $this->render('KulaHEdStudentBundle:SISContactInfo:addresses_add.html.twig');
  }
  
  public function phonesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    if ($this->poster()) {
    $phone_result = $this->poster()->getAddedIDs('Core.Constituent.Phone');
      // New phone number
      if ($phone_result) {
        // Get New Phones Number ID
        $new_ids = array_values($phone_result);
        // Query to see which are primary
        $new_phones = $this->db()->db_select('CONS_PHONE', 'phone')
          ->fields('phone', array('PHONE_NUMBER_ID'))
          ->condition('PHONE_NUMBER_ID', $new_ids)
          ->execute()->fetch();
        
        $this->db()->db_update('CONS_CONSTITUENT')
          ->fields(array('PRIMARY_PHONE_ID' => $new_phones['PHONE_NUMBER_ID']))
          ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
          ->execute();
      }
    }
    
    $phones = array();
    $primary_phones = array();
    
    if ($this->record->getSelectedRecordID()) {
      $phones = $this->db()->db_select('CONS_PHONE', 'phone')
        ->fields('phone', array('PHONE_NUMBER_ID', 'EFFECTIVE_DATE', 'PHONE_TYPE', 'PHONE_NUMBER', 'PHONE_EXTENSION', 'PHONE_COUNTRY'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('PHONE_TYPE')
        ->orderBy('EFFECTIVE_DATE', 'DESC')
        ->execute()->fetchAll();

      $constituent_primary_phones = $this->db()->db_select('CONS_CONSTITUENT', 'cons')
        ->fields('cons', array('PRIMARY_PHONE_ID'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
      
      $primary_phones['PRIMARY_PHONE_ID'] = $constituent_primary_phones['PRIMARY_PHONE_ID'];
    }
    
    return $this->render('KulaHEdStudentBundle:SISContactInfo:phones.html.twig', array('phones' => $phones, 'primary_phones' => $primary_phones));
  }
  
  public function emailAddressesAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    $emails = array();
    
    if ($this->record->getSelectedRecordID()) {
      $emails = $this->db()->db_select('CONS_EMAIL_ADDRESS', 'emails')
        ->fields('emails', array('EMAIL_ADDRESS_ID', 'EMAIL_ADDRESS_TYPE', 'EFFECTIVE_DATE', 'EMAIL_ADDRESS'))
        ->condition('CONSTITUENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('EMAIL_ADDRESS_TYPE')
        ->orderBy('EFFECTIVE_DATE')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:SISContactInfo:emailaddresses.html.twig', array('emails' => $emails));
  }
  
  public function emergencyContactsAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.HEd.Student');
    
    $emergency = array();
    
    if ($this->record->getSelectedRecordID()) {
      $emergency = $this->db()->db_select('STUD_STUDENT_EMERGENCY_CONTACT', 'emergency')
        ->fields('emergency', array('SORT', 'EMERGENCY_CONTACT_ID', 'EMERGENCY_CONTACT_NAME', 'EMERGENCY_CONTACT_PHONE'))
        ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
        ->orderBy('SORT')
        ->orderBy('EMERGENCY_CONTACT_NAME')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:SISContactInfo:emergencycontacts.html.twig', array('emergency_contacts' => $emergency));
  }
  
}