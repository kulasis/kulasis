<?php

namespace Kula\Core\Component\Schema;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class SchemaStore implements WarmableInterface {
  
  private $schema;
  
  public function __construct($db, $fileName, $cacheDir, $debug) {
    $this->db = $db;
    $this->cacheDir = $cacheDir;
    $this->fileName = $fileName;
    $this->debug = $debug;
  }
  
  public function warmUp($cacheDir) {
    
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_SCHEMA_TABLES', 'CORE_SCHEMA_FIELDS'));

    if (!$cache->isFresh()) {

      $schema = new Schema($this->db);
      $schema->loadTables();
      $schema->loadFields();
      $cache->write(serialize($schema));
      
    }
    
    $this->schema = unserialize(file_get_contents((string) $cache));
    
  }
  
  public function getSchema() {
    if (!$this->schema)
      $this->warmUp($this->cacheDir);
    return $this->schema;
  }
    
  
}