<?php

namespace Kula\Core\Component\Lookup;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class LookupStore {
  
  private $lookup;
  
  public function __construct($db, $fileName, $cacheDir, $debug, $kernel) {
    $this->db = $db;
    $this->cacheDir = $cacheDir;
    $this->fileName = $fileName;
    $this->debug = $debug;
    $this->kernel = $kernel;
  }
  
  public function warmUp($cacheDir) {
    
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_LOOKUP_TABLES', 'CORE_LOOKUP_VALUES'));

    if (!$cache->isFresh()) {
      
      $lookup_obj = new \Kula\Core\Component\Lookup\LookupLoader;
      $lookup_obj->getLookupsFromBundles($this->kernel->getBundles());
      $lookup_obj->synchronizeDatabaseCatalog($this->db);
      
      $lookup = new Lookup($this->db);
      $cache->write(serialize($lookup), $lookup_obj->paths);
      
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