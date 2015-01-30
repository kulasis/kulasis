<?php

namespace Kula\Core\Component\Chooser;

use Kula\Core\Component\DefinitionLoader\DefinitionLoader;
use Symfony\Component\Config\Resource\FileResource;

class ChooserLoader {
  
  private $choosers = array();
  public $paths = array();
  
  public function getChoosersFromBundles(array $bundles) {
    
    $choosers = DefinitionLoader::loadDefinitionsFromBundles($bundles, 'chooser');
    
    if ($choosers) {
      foreach($choosers as $path => $chooser) {
        $this->loadChooser($chooser, $path);
        $this->paths[] = new FileResource($path);
      }
    }
    
  }
  
  public function loadChooser($choosers, $path) {
    
    foreach($choosers as $chooserName => $chooser) {
      
      $this->choosers[$chooserName] = $chooser;
      
    }
  }
  
  public function synchronizeDatabaseCatalog(\Kula\Core\Component\DB\DB $db) {
    
    foreach($this->choosers as $chooserName => $chooser) {
      
      // Check table exists in database
      $catalogRecordTable = $db->db_select('CORE_CHOOSER', 'chooser', array('target' => 'schema'))
        ->fields('chooser')
        ->condition('CHOOSER_NAME', $chooserName)
        ->execute()->fetch();
      
      $chooserFields = array();
      
      if ($catalogRecordTable['CHOOSER_ID']) {
        
        if ($catalogRecordTable['CLASS'] != $chooser['class']) 
          $chooserFields['CLASS'] = $chooser['class'];
        if (count($chooserFields) > 0) {
          $chooserFields['UPDATED_TIMESTAMP'] = date('Y-m-d H:i:s');
          $db->db_update('CORE_CHOOSER', array('target' => 'schema'))->fields($chooserFields)->condition('CHOOSER_NAME', $chooserName)->execute();
        }
      } else {
        
        $chooserFields['CHOOSER_NAME'] = $chooserName;
        $chooserFields['CLASS'] = (isset($chooser['class'])) ? $chooser['class'] : null;
        $chooserFields['CREATED_TIMESTAMP'] = date('Y-m-d H:i:s');
        $chooserID = $db->db_insert('CORE_CHOOSER', array('target' => 'schema'))->fields($chooserFields)->execute();
        
      }
      
      
    }
  
  }
  
}