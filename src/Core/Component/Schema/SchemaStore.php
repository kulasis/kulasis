<?php

namespace Kula\Core\Component\Schema;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class SchemaStore implements WarmableInterface {
  
  private $schema;
  
  public function __construct($db, $fileName, $cacheDir, $debug, $kernel) {
    $this->db = $db;
    $this->cacheDir = $cacheDir;
    $this->fileName = $fileName;
    $this->debug = $debug;
    $this->kernel = $kernel;
  }
  
  public function warmUp($cacheDir) {
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_SCHEMA_TABLES', 'CORE_SCHEMA_FIELDS'));

    if (!$cache->isFresh()) {
      
      $schema_obj = new \Kula\Core\Component\Schema\SchemaLoader;
      $schema_obj->getSchemaFromBundles($this->kernel->getBundles());
      $schema_obj->synchronizeDatabaseCatalog($this->db);
      $paths = $schema_obj->paths;
      $schema_obj = null;
      //echo round(memory_get_usage()/1048576,2).' of '.ini_get('memory_limit')." - finish compiling\n";
      $schema = new Schema($this->db);
      //echo round(memory_get_usage()/1048576,2).' of '.ini_get('memory_limit')." - make schema\n";
      $schema->loadTables();
      //echo round(memory_get_usage()/1048576,2).' of '.ini_get('memory_limit')." - load tables\n";
      $schema->loadFields();
      //echo round(memory_get_usage()/1048576,2).' of '.ini_get('memory_limit')."- load fields\n";
      $cache->write(serialize($schema), $paths);
      //echo round(memory_get_usage()/1048576,2).' of '.ini_get('memory_limit')." - write\n";
      $schema = null;
      

    }
    unset($this->schema);
    $this->schema = unserialize(file_get_contents((string) $cache));
  }
  
  public function getSchema() {
    if (!$this->schema)
      $this->warmUp($this->cacheDir);
    return $this->schema;
  }
    
  
}