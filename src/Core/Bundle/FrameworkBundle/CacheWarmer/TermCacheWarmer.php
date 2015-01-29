<?php

namespace Kula\Core\Bundle\FrameworkBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class TermCacheWarmer extends CacheWarmer {
  
  public function __construct($term) {
    $this->term = $term;
  }
  
  public function warmUp($cacheDir) {
    $this->term->warmUp($cacheDir);
  }
  
  public function isOptional() {
    return false;
  }
  
}