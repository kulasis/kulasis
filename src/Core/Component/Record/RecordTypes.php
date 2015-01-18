<?php

namespace Kula\Core\Component\Record;

class RecordTypes {
  
  private $recordTypes = array();
  
  private $db;
  
  public function __construct($db) {
    $this->db = $db;
  }
  
  public function loadRecordTypes() {
    $recordTypesResults = $this->db->db_select('CORE_RECORD_TYPES', 'recordtypes')
      ->fields('recordtypes')
      ->execute();
    while ($recordTypesRow = $recordTypesResults->fetch()) {
      $this->recordTypes[$recordTypesRow['PORTAL']][$recordTypesRow['RECORD_NAME']] = new RecordType($recordTypesRow['RECORD_TYPE_ID'], $recordTypesRow['RECORD_NAME'], $recordTypesRow['PORTAL'], $recordTypesRow['CLASS']);
    }
  }
  
  public function getRecordType($portal, $recordTypeName) {
    return $this->recordTypes[$portal][$recordTypeName];
  }
  
  public function __sleep() {
    $this->db = null;
    
    return array('recordTypes');
  }
  
}