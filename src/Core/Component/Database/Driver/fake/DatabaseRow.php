<?php

/**
 * @file
 * Contains Kula\Core\Component\Database\Driver\fake\DatabaseRow.
 */

namespace Kula\Core\Component\Database\Driver\fake;

class DatabaseRow implements DatabaseRowInterface {

  /**
   * Construct a new row.
   *
   * @param array $row
   *   The row data.
   */
  public function __construct(array $row) {
    $this->row = $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($field) {
    return $this->row[$field];
  }

}
