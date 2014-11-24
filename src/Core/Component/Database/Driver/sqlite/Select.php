<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\Driver\sqlite\Select
 */

namespace Kula\Core\Component\Database\Driver\sqlite;

use Kula\Core\Component\Database\Query\Select as QuerySelect;

class Select extends QuerySelect {
  public function forUpdate($set = TRUE) {
    // SQLite does not support FOR UPDATE so nothing to do.
    return $this;
  }
}