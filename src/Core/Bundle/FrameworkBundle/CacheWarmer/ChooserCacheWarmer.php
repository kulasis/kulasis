<?php

namespace Kula\Core\Bundle\FrameworkBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class ChooserCacheWarmer extends CacheWarmer {
  
  public function __construct($chooser) {
    $this->chooser = $chooser;
  }
  
  public function warmUp($cacheDir) {
    $this->chooser->warmUp($cacheDir);
  }
  
  public function isOptional() {
    return false;
  }
  
}