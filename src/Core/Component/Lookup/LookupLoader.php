<?php

namespace Kula\Core\Component\Lookup;

use Kula\Core\Component\DefinitionLoader\DefinitionLoader;
use Symfony\Component\Config\Resource\FileResource;

class LookupLoader {
  
  private $lookups = array();
  public $paths = array();
  
  public function getLookupsFromBundles(array $bundles) {
    
    $lookups = DefinitionLoader::loadDefinitionsFromBundles($bundles, 'lookup');
    
    if ($lookups) {
      foreach($lookups as $path => $lookup) {
        $this->loadLookup($lookup, $path);
        $this->paths[] = new FileResource($path);
      }
    }
    
  }
  
  public function loadLookup($lookup, $path) {
    
    if ($lookup) {
    foreach($lookup as $lookupTableName => $lookupTable) {
      
      $this->lookups[$lookupTableName] = $lookupTable;
      unset($this->lookups[$lookupTableName]['values']);
      
      $firstRound = false;
      // Determine if first round
      if (!isset($this->lookups[$lookupTableName]['values'])) {
        $firstRound = true;
      }
      
      if (isset($lookupTable['values'])) {
      foreach($lookupTable['values'] as $value) {
        
        if ($firstRound ||
            !isset($this->lookups[$lookupTableName]['allow_update']) || 
            $this->lookups[$lookupTableName]['allow_update']) {
          
          $this->lookups[$lookupTableName]['values'][$value['code']] = $value;
          
        }
        
      }
      }
      
    }
    }
  }
  
  public function synchronizeDatabaseCatalog(\Kula\Core\Component\DB\DB $db) {
    
    foreach($this->lookups as $lookupTableName => $lookupTable) {
      
      // Check table exists in database
      $catalogLookupTable = $db->db_select('CORE_LOOKUP_TABLES', 'lookup_tables', array('target' => 'schema'))
        ->fields('lookup_tables')
        ->condition('LOOKUP_TABLE_NAME', $lookupTableName)
        ->execute()->fetch();
      
      $lookupTableID = $catalogLookupTable['LOOKUP_TABLE_ID'];
      
      $lookupTableFields = array();
      
      if ($catalogLookupTable['LOOKUP_TABLE_NAME']) {
        
        if ($catalogLookupTable['LOOKUP_TABLE_DESCRIPTION'] != $lookupTable['description']) 
          $lookupTableFields['LOOKUP_TABLE_DESCRIPTION'] = $lookupTable['description'];
        if ($catalogLookupTable['LOOKUP_TABLE_UPDATE'] != $lookupTable['allow_update'])
          $lookupTableFields['LOOKUP_TABLE_UPDATE'] = ($lookupTable['allow_update']) ? 1 : 0;
        if (count($lookupTableFields) > 0) {
          $lookupTableFields['UPDATED_TIMESTAMP'] = date('Y-m-d H:i:s');
          $db->db_update('CORE_LOOKUP_TABLES', array('target' => 'schema'))->fields($lookupTableFields)->condition('LOOKUP_TABLE_NAME', $lookupTableName)->execute();
        }
      } else {
        
        $lookupTableFields['LOOKUP_TABLE_NAME'] = $lookupTableName;
        $lookupTableFields['LOOKUP_TABLE_DESCRIPTION'] = $lookupTable['description'];
        $lookupTableFields['LOOKUP_TABLE_UPDATE'] = ($lookupTable['allow_update']) ? 1 : 0;
        $lookupTableFields['CREATED_TIMESTAMP'] = date('Y-m-d H:i:s');
        $lookupTableID = $db->db_insert('CORE_LOOKUP_TABLES', array('target' => 'schema'))->fields($lookupTableFields)->execute();
        
      }
      
      //print_r($lookupTable['values']);
      if (isset($lookupTable['values'])) {
      foreach($lookupTable['values'] as $value) {
        
        $catalogLookupValue = $db->db_select('CORE_LOOKUP_VALUES', 'lookup_values', array('target' => 'schema'))
          ->fields('lookup_values')
          ->condition('LOOKUP_TABLE_ID', $lookupTableID)
          ->condition('CODE', $value['code'])
          ->execute()->fetch();
        
        $lookupTableValueFields = array();
        
        if ($catalogLookupValue['LOOKUP_VALUE_ID']) {
          
          if (isset($value['description']) AND $catalogLookupValue['DESCRIPTION'] != $value['description'])
             $lookupTableValueFields['DESCRIPTION'] = $value['description'];
          if (isset($value['sort']) AND $catalogLookupValue['SORT'] != $value['sort'])
            $lookupTableValueFields['SORT'] = (isset($value['sort'])) ? $value['sort'] : null;
          if (isset($value['inactive_date']) AND $catalogLookupValue['INACTIVE_AFTER'] != $value['inactive_date'])
            $lookupTableValueFields['INACTIVE_AFTER'] = (isset($value['inactive_date'])) ? $value['inactive_date'] : null;
          if (isset($value['conversion']) AND $catalogLookupValue['CONVERSION'] != $value['conversion'])
            $lookupTableValueFields['CONVERSION'] = (isset($value['conversion'])) ? $value['conversion'] : null;
          if (count($lookupTableValueFields) > 0) {
            $lookupTableValueFields['UPDATED_TIMESTAMP'] = date('Y-m-d H:i:s');
            $db->db_update('CORE_LOOKUP_VALUES', array('target' => 'schema'))->fields($lookupTableValueFields)->condition('CODE', $value['code'])->execute();
          }
        } else {
          
          $lookupTableValueFields['LOOKUP_TABLE_ID'] = $lookupTableID;
          $lookupTableValueFields['CODE'] = $value['code'];
          $lookupTableValueFields['DESCRIPTION'] = $value['description'];
          $lookupTableValueFields['SORT'] = (isset($value['sort'])) ? $value['sort'] : null;
          $lookupTableValueFields['INACTIVE_AFTER'] = (isset($value['inactive_date'])) ? $value['inactive_date'] : null;
          $lookupTableValueFields['CONVERSION'] = (isset($value['conversion'])) ? $value['conversion'] : null;
          $lookupTableValueFields['CREATED_TIMESTAMP'] = date('Y-m-d H:i:s');
          $db->db_insert('CORE_LOOKUP_VALUES', array('target' => 'schema'))->fields($lookupTableValueFields)->execute();
          
        }
        
        
      }
      }
      
    }
  
  }
  
}