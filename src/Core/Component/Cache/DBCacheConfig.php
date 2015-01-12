<?php

namespace Kula\Core\Component\Cache;

use Symfony\Component\Config\ConfigCache as BaseConfigCache;

class DBCacheConfig extends BaseConfigCache {
  
  protected $file;
  protected $debug;
  protected $db;
  protected $tables;
  
  public function __construct($file, $debug, $db, $tables) {
    parent::__construct($file, $debug);
    
    $this->file = $file;
    $this->debug = $debug;
    $this->db = $db;
    $this->tables = $tables;
  }
  
  public function isFresh() {
    
    // if file doesn't exist, always false
    if (!is_file($this->file)) {
        return false;
    }
    
    // if production, always true
    if (!$this->debug) {
        return true;
    }
    
    $time = filemtime($this->file);
    
    if ($time < $this->getLastUpdatestamp()) {
      return false;
    }
    
    return true;
  }
  
  private function getLastUpdatestamp() {
    
    $latestTimestamp = '';
    
    foreach($this->tables as $table) {
      
      $result = $this->db->db_select($table, 'tables')
        ->expression('MAX(CREATED_TIMESTAMP)', 'max_created')
        ->expression('MAX(UPDATED_TIMESTAMP)', 'max_updated')
        ->execute()->fetch();
      
      $createdLatest = $result['max_created'] == '' ? strtotime($result['max_created']) : null;
      $updatedLatest = $result['max_updated'] == '' ? strtotime($result['max_updated']) : null;
      
      if ($latestTimestamp < $createdLatest)
        $latestTimestamp = $createdLatest;
      if ($latestTimestamp < $updatedLatest)
        $latestTimestamp = $updatedLatest;
      
    }
    
    return $latestTimestamp;
    
  }
  
}