<?php

namespace Kula\Core\Bundle\FrameworkBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class SchemaCacheWarmer extends CacheWarmer {
  
  public function __construct($schema) {
    $this->schema = $schema;
  }
  
  public function warmUp($cacheDir) {
    $this->schema->warmUp($cacheDir);
  }
  
  public function isOptional() {
    return false;
  }
  
}