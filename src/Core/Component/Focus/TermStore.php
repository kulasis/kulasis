<?php

namespace Kula\Core\Component\Focus;

use Kula\Core\Component\Cache\DBCacheConfig as DBConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Config\FileLocator;

class TermStore {
  
  private $term;
  
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
    
    $cache = new DBConfigCache($cacheDir.'/'.$this->fileName.'.php', $this->debug, $this->db, array('CORE_TERM'));
    
    if (!$cache->isDBFresh()) {
      
      $term = new Term($this->db);
      $term->loadTerms();
      $cache->write(serialize($term));
      
    }
    
    $this->term = unserialize(file_get_contents((string) $cache));
    $this->term->awake($this->db);
  }
  
  public function getTerm() {
    if (!$this->term)
      $this->warmUp($this->cacheDir);
    return $this->term;
  }
  
}