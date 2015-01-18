<?php

namespace Kula\Core\Component\DB;

use Kula\Core\Component\DB\DB as DB;
use Kula\Core\Component\Schema\Schema as Schema;
use Symfony\Component\HttpFoundation\RequestStack as RequestStack;
use Kula\Core\Component\DB\Poster;

class PosterFactory {
  
  public static function getPoster(DB $db, Schema $schema, RequestStack $request_stack) {
    
    return new Poster($db, $schema, $request_stack);
    
  }
  
}