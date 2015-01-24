<?php

namespace Kula\Core\Component\Chooser;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class ChooserStore {
  
  private $choosers;
  
  public function __construct($db, $fileName, $cacheDir, $debug, $kernel, $session, $focus) {
    $this->db = $db;
    $this->cacheDir = $cacheDir;
    $this->fileName = $fileName;
    $this->debug = $debug;
    $this->kernel = $kernel;
    $this->focus = $focus;
    $this->session = $session;
  }
  
  public function warmUp($cacheDir) {
    
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_CHOOSER'));

    if (!$cache->isFresh()) {
      
      $chooser_obj = new \Kula\Core\Component\Chooser\ChooserLoader;
      $chooser_obj->getChoosersFromBundles($this->kernel->getBundles());
      $chooser_obj->synchronizeDatabaseCatalog($this->db);

      $choosers = new Choosers($this->db, $this->session, $this->focus);
      $choosers->loadChoosers();
      $cache->write(serialize($choosers), $chooser_obj->paths);
      
    }
    
    $this->choosers = unserialize(file_get_contents((string) $cache));
    $this->choosers->loadDependencies($this->db, $this->session, $this->focus);
    
  }
  
  public function getChoosers() {
    if (!$this->choosers)
      $this->warmUp($this->cacheDir);
    return $this->choosers;
  }
  
}