<?php

namespace Kula\Core\Component\DB;

use Kula\Core\Component\DB\Poster;

class PosterFactory {
  
  public static function getPoster($container) {
    return new Poster($container);
  }
  
}