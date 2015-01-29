<?php

namespace Kula\Core\Bundle\FrameworkBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class OrganizationCacheWarmer extends CacheWarmer {
  
  public function __construct($organization) {
    $this->organization = $organization;
  }
  
  public function warmUp($cacheDir) {
    $this->organization->warmUp($cacheDir);
  }
  
  public function isOptional() {
    return false;
  }
  
}