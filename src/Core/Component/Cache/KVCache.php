<?php

namespace Kula\Core\Component\Cache;

class KVCache {
  
  protected $container;
  protected $apc;
  
  protected $cache_store;
  
  public function __construct($container, $apc) {
    $this->container = $container;
    $this->apc = $apc;
  }
  
  public function add($key, $value, $options = null) {
    if ($this->apc->checkForAPC()) {
      return $this->apc->add($key, $value, $options);
    } else {
      $this->cache_store[$key] = $value;
      return true;
    }
  }
  
  public function get($key, $options = null) {
    if ($this->apc->checkForAPC()) {
      return $this->apc->get($key, $options);
    } elseif (isset($this->cache_store[$key])) {
      return $this->cache_store[$key];
    }
  }
  
  public function delete($key) {
    if ($this->apc->checkForAPC()) {
      return $this->apc->delete($key);
    } elseif (isset($this->cache_store[$key])) {
      unset($this->cache_store[$key]);
      return true;
    }
  }
}