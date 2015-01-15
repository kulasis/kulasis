<?php

namespace Kula\Core\Component\Record;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class RecordStore {
  
  private $record;
  
  public function __construct($db, $fileName, $cacheDir, $debug) {
    $this->db = $db;
    $this->cacheDir = $cacheDir;
    $this->fileName = $fileName;
    $this->debug = $debug;
  }
  
  public function warmUp($cacheDir) {
    
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_RECORD_TYPES'));

    if (!$cache->isFresh()) {

      $record = new Record($this->db);
      $record->loadRecord();
      $cache->write(serialize($record));
      
    }
    
    $this->record = unserialize(file_get_contents((string) $cache));
    
  }
  
  public function getRecord() {
    if (!$this->record)
      $this->warmUp($this->cacheDir);
    return $this->record;
  }
  
}