<?php

namespace Kula\Core\Component\Navigation;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class NavigationStore {
  
  private $navigation;
  
  public function __construct($db, $fileName, $cacheDir, $debug, $kernel, $session, $permission, $request, $cache) {
    $this->db = $db;
    $this->cacheDir = $cacheDir;
    $this->fileName = $fileName;
    $this->debug = $debug;
    $this->kernel = $kernel;
    $this->session = $session;
    $this->permission = $permission;
    $this->request = $request;
    $this->cache = $cache;
  }
  
  public function warmUp($cacheDir) {
    
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_NAVIGATION'));

    if (!$cache->isFresh() OR !$this->cache->verifyCacheLoaded('navigation')) {
      
      $nav_obj = new \Kula\Core\Component\Navigation\NavigationLoader($this->db, $this->cache);
      $nav_obj->getNavigationFromBundles($this->kernel->getBundles());
      $nav_obj->synchronizeDatabaseCatalog();
      
      $navigation = $nav_obj->loadNavigation();
      $cache->write(serialize($navigation), $nav_obj->paths);
      
      $this->cache->setCacheLoaded('navigation');
    }
    
    if (!$this->cache->verifyCacheLoaded('navigation')) {
      $navigation = unserialize(file_get_contents((string) $cache));
    }
    $this->navigation = new Navigation($this->db, $this->session, $this->permission, $this->request, $this->cache);
  }
  
  public function getNavigation() {
    if (!$this->navigation)
      $this->warmUp($this->cacheDir);
    return $this->navigation;
  }
  
}