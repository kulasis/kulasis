<?php

namespace Kula\Core\Component\Lookup;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class LookupStore {
  
  private $lookup;
  
  public function __construct($db, $fileName, $cacheDir, $debug) {
    $this->db = $db;
    $this->cacheDir = $cacheDir;
    $this->fileName = $fileName;
    $this->debug = $debug;
  }
  
  public function warmUp($cacheDir) {
    
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_LOOKUP_TABLES', 'CORE_LOOKUP_VALUES'));

    if (!$cache->isFresh()) {

      $lookup = new Lookup($this->db);
      $cache->write(serialize($lookup));
      
    }
    
    $this->lookup = unserialize(file_get_contents((string) $cache));
    $this->lookup->setDependencies($this->db);
  }
  
  public function getLookup() {
    if (!$this->lookup)
      $this->warmUp($this->cacheDir);
    return $this->lookup;
  }
  
}