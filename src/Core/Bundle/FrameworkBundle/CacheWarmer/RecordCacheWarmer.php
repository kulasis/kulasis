<?php

namespace Kula\Core\Bundle\FrameworkBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class RecordCacheWarmer extends CacheWarmer {
  
  public function __construct($record) {
    $this->record = $record;
  }
  
  public function warmUp($cacheDir) {
    $this->record->warmUp($cacheDir);
  }
  
  public function isOptional() {
    return false;
  }
  
}