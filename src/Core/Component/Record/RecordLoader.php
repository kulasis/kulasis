<?php

namespace Kula\Core\Component\Record;

use Kula\Core\Component\DefinitionLoader\DefinitionLoader;
use Symfony\Component\Config\Resource\FileResource;

class RecordLoader {
  
  private $records = array();
  public $paths = array();
  
  public function getRecordsFromBundles(array $bundles) {
    
    $records = DefinitionLoader::loadDefinitionsFromBundles($bundles, 'record');
    
    if ($records) {
      foreach($records as $path => $record) {
        $this->loadRecord($record, $path);
        $this->paths[] = new FileResource($path);
      }
    }
    
  }
  
  public function loadRecord($records, $path) {
    
    foreach($records as $recordName => $record) {
      
      $this->records[$recordName] = $record;
      
    }
  }
  
  public function synchronizeDatabaseCatalog(\Kula\Core\Component\DB\DB $db) {
    
    foreach($this->records as $recordName => $record) {
      
      // Check table exists in database
      $catalogRecordTable = $db->db_select('CORE_RECORD_TYPES', 'record_types')
        ->fields('record_types')
        ->condition('RECORD_NAME', $recordName)
        ->execute()->fetch();
      
      $recordFields = array();
      
      if ($catalogRecordTable['RECORD_TYPE_ID']) {
        
        if ($catalogRecordTable['CLASS'] != $record['class']) 
          $recordFields['CLASS'] = $record['class'];
        if (count($recordFields) > 0) {
          $recordFields['UPDATED_TIMESTAMP'] = date('Y-m-d H:i:s');
          $db->db_update('CORE_RECORD_TYPES')->fields($recordFields)->condition('RECORD_NAME', $recordName)->execute();
        }
      } else {
        
        $recordFields['RECORD_NAME'] = $recordName;
        $recordFields['PORTAL'] = strtolower(substr($recordName, 0, strpos($recordName, '.')));
        $recordFields['CLASS'] = (isset($record['class'])) ? $record['class'] : null;
        $recordFields['CREATED_TIMESTAMP'] = date('Y-m-d H:i:s');
        $recordID = $db->db_insert('CORE_RECORD_TYPES')->fields($recordFields)->execute();
        
      }
      
      
    }
  
  }
  
}