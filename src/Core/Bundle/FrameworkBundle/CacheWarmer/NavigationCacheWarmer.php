<?php

namespace Kula\Core\Bundle\FrameworkBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class NavigationCacheWarmer extends CacheWarmer {
  
  public function __construct($navigation) {
    $this->navigation = $navigation;
  }
  
  public function warmUp($cacheDir) {
    $this->navigation->warmUp($cacheDir);
  }
  
  public function isOptional() {
    return false;
  }
  
}