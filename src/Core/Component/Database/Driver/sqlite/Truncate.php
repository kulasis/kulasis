<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\Driver\sqlite\Truncate
 */

namespace Kula\Core\Component\Database\Driver\sqlite;

use Kula\Core\Component\Database\Query\Truncate as QueryTruncate;

/**
 * SQLite specific implementation of TruncateQuery.
 *
 * SQLite doesn't support TRUNCATE, but a DELETE query with no condition has
 * exactly the effect (it is implemented by DROPing the table).
 */
class Truncate extends QueryTruncate {
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    return $comments . 'DELETE FROM {' . $this->connection->escapeTable($this->table) . '} ';
  }
}
