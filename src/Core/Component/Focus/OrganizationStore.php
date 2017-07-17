<?php

namespace Kula\Core\Component\Focus;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class OrganizationStore {
  
  private $organization;
  
  public function __construct($db, $fileName, $cacheDir, $debug, $kernel, $request, $cache, $schema) {
    $this->db = $db;
    $this->cacheDir = $cacheDir;
    $this->fileName = $fileName;
    $this->debug = $debug;
    $this->kernel = $kernel;
    $this->request = $request;
    $this->cache = $cache;
    $this->schema = $schema;
  }
  
  public function warmUp($cacheDir) {
    
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_ORGANIZATION', 'CORE_ORGANIZATION_TERMS'));

    if (!$cache->isDBFresh() OR !$this->cache->verifyCacheLoaded('organization')) {
     
      $organizationLoader = new OrganizationLoader($this->db, $this->cache, $this->schema);
      $organization = $organizationLoader->loadOrganization();
      $cache->write(serialize($organization));
      
      $this->cache->setCacheLoaded('organization');
    }
    
    if (!$this->cache->verifyCacheLoaded('organization')) {
      $term = unserialize(file_get_contents((string) $cache));
    }
    $this->organization = new Organization($this->cache);
  }
  
  public function getOrganization() {
    if (!$this->organization)
      $this->warmUp($this->cacheDir);
    return $this->organization;
  }
  
}