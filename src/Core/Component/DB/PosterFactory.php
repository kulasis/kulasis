<?php

namespace Kula\Core\Component\DB;

use Kula\Core\Component\DB\Poster;

class PosterFactory {
  
  public function __construct($container) {
    $this->container = $container;
  }
  
  public function newPoster() {
    return new Poster($this->container);
  }
  
  public static function getPoster($container) {
    return new Poster($container);
  }
  
}