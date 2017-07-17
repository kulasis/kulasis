<?php

namespace Kula\Core\Component\Cache;

class KVCache {
  
  protected $container;
  protected $apc;
  
  protected $cache_store;
  
  public function __construct($container, $apc) {
    $this->container = $container;
    $this->apc = $apc;
    $this->cache_app = $this->container->get('cache.app');
  }
  
  public function add($key, $value, $options = null) {

    $cachedItem = $this->cache_app->getItem($key);
    $cachedItem->set($value);
    $this->cache_app->save($cachedItem);
/*
    if ($this->apc->checkForAPC()) {
      return $this->apc->add($key, $value, $options);
    } else {
      $this->cache_store[$key] = $value;
      return true;
    }
*/
  }
  
  public function exists($key, $options = null) {

    return $this->cache_app->getItem($key)->isHit(); 
/*
    if ($this->apc->checkForAPC()) {
      return $this->apc->exists($key, $options);
    } elseif (isset($this->cache_store[$key])) {
      return array_key_exists($key, $this->cache_store);
    }
*/
  }
  
  public function get($key, $options = null) {

    return $this->cache_app->getItem($key)->get();
    /*
    if ($this->apc->checkForAPC()) {
      return $this->apc->get($key, $options);
    } elseif (isset($this->cache_store[$key])) {
      return $this->cache_store[$key];
    }
    */
  }
  
  public function delete($key) {

    return $this->cache_app->removeItem($key);

/*
    if ($this->apc->checkForAPC()) {
      return $this->apc->delete($key);
    } elseif (isset($this->cache_store[$key])) {
      unset($this->cache_store[$key]);
      return true;
    }
    */
  }
  
  public function verifyCacheLoaded($key) {

    return $this->get($key);
/*
    if ($this->apc->checkForAPC())
      return $this->apc->verifyCacheLoaded($key);
*/
  }
  
  public function setCacheLoaded($key) {

    return $this->add($key, time());

/*
    if ($this->apc->checkForAPC())
      return $this->apc->setCacheLoaded($key);
*/
  }
}