<?php

namespace Kula\Core\Component\Focus;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class OrganizationStore {
  
  private $organization;
  
  public function __construct($db, $fileName, $cacheDir, $debug, $kernel, $session, $permission, $request) {
    $this->db = $db;
    $this->cacheDir = $cacheDir;
    $this->fileName = $fileName;
    $this->debug = $debug;
    $this->kernel = $kernel;
    $this->session = $session;
    $this->permission = $permission;
    $this->request = $request;
  }
  
  public function warmUp($cacheDir) {
    
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_ORGANIZATION', 'CORE_ORGANIZATION_TERMS'));
    
    if (!$cache->isDBFresh()) {
      
      $organization = new Organization($this->db);
      $organization->loadOrganization();
      $cache->write(serialize($organization));
      
    }
    
    $this->organization = unserialize(file_get_contents((string) $cache));
    $this->organization->awake($this->db);
  }
  
  public function getOrganization() {
    if (!$this->organization)
      $this->warmUp($this->cacheDir);
    return $this->organization;
  }
  
}