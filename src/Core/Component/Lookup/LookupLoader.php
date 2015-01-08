<?php

namespace Kula\Core\Component\Lookup;

use Kula\Core\Component\DefinitionLoader\DefinitionLoader;


class LookupLoader {
  
  private $lookups = array();
  
  public function getLookupsFromBundles(array $bundles) {
    
    $lookups = DefinitionLoader::loadDefinitionsFromBundles($bundles, 'lookup');
    
    if ($lookups) {
      foreach($lookups as $path => $lookup) {
        $this->loadLookup($lookup, $path);
      }
    }
    
  }
  
  public function loadLookup($lookup, $path) {
    
    foreach($lookup as $lookupTableName => $lookupTable) {
      
      $this->lookups[$lookupTableName] = $lookupTable;
      unset($this->lookups[$lookupTableName]['values']);
      
      $firstRound = false;
      // Determine if first round
      if (!isset($this->lookups[$lookupTableName]['values'])) {
        $firstRound = true;
      }
      
      foreach($lookupTable['values'] as $value) {
        
        if ($firstRound ||
            !isset($this->lookups[$lookupTableName]['allow_update']) || 
            $this->lookups[$lookupTableName]['allow_update']) {
          
          $this->lookups[$lookupTableName]['values'][$value['code']] = $value;
          
        }
        
      }
      
    }
  }
  
  public function synchronizeDatabaseCatalog(\Kula\Core\Component\DB\DB $db) {
    
    foreach($this->lookups as $lookupTableName => $lookupTable) {
      
      // Check table exists in database
      $catalogLookupTable = $db->db_select('CORE_LOOKUP_TABLES', 'lookup_tables')
        ->fields('lookup_tables')
        ->condition('LOOKUP_TABLE_NAME', $lookupTableName)
        ->execute()->fetch();
      
      $lookupTableID = $catalogLookupTable['LOOKUP_TABLE_ID'];
      
      if ($catalogLookupTable['LOOKUP_TABLE_NAME']) {
        
        if ($catalogLookupTable['LOOKUP_TABLE_DESCRIPTION'] != $lookupTable['description']) 
          $lookupTableFields['LOOKUP_TABLE_DESCRIPTION'] = $lookupTable['description'];
        if ($catalogLookupTable['LOOKUP_TABLE_UPDATE'] != $lookupTable['allow_update'])
          $lookupTableFields['LOOKUP_TABLE_UPDATE'] = ($lookupTable['allow_update']) ? 'Y' : 'N';
        if (count($lookupTableFields) > 0)
          $db->db_update('CORE_LOOKUP_TABLES')->fields($lookupTableFields)->condition('LOOKUP_TABLE_NAME', $lookupTableName)->execute();
        
      } else {
        
        $lookupTableFields['LOOKUP_TABLE_NAME'] = $lookupTableName;
        $lookupTableFields['LOOKUP_TABLE_DESCRIPTION'] = $lookupTable['description'];
        $lookupTableFields['LOOKUP_TABLE_UPDATE'] = ($lookupTable['allow_update']) ? 'Y' : 'N';
        $lookupTableID = $db->db_insert('CORE_LOOKUP_TABLES')->fields($lookupTableFields)->execute();
        
      }
      
      //print_r($lookupTable['values']);
      
      foreach($lookupTable['values'] as $value) {
        
        $catalogLookupValue = $db->db_select('CORE_LOOKUP_VALUES', 'lookup_values')
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
          if (count($lookupTableValueFields) > 0)
            $db->db_update('CORE_LOOKUP_VALUES')->fields($lookupTableValueFields)->condition('CODE', $value['code'])->execute();
          
        } else {
          
          $lookupTableValueFields['LOOKUP_TABLE_ID'] = $lookupTableID;
          $lookupTableValueFields['CODE'] = $value['code'];
          $lookupTableValueFields['DESCRIPTION'] = $value['description'];
          $lookupTableValueFields['SORT'] = (isset($value['sort'])) ? $value['sort'] : null;
          $lookupTableValueFields['INACTIVE_AFTER'] = (isset($value['inactive_date'])) ? $value['inactive_date'] : null;
          $lookupTableValueFields['CONVERSION'] = (isset($value['conversion'])) ? $value['conversion'] : null;
          $db->db_insert('CORE_LOOKUP_VALUES')->fields($lookupTableValueFields)->execute();
          
        }
        
        
      }
      
    }
  
  }
  
}