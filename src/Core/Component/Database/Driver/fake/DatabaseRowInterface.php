<?php

/**
 * @file
 * Contains Kula\Core\Component\Database\Driver\fake\DatabaseRowInterface.
 */

namespace Kula\Core\Component\Database\Driver\fake;

interface DatabaseRowInterface {

  /**
   * Get the field value from the row.
   *
   * @param mixed $field
   *   The field to get the value of.
   *
   * @return mixed
   *   The field value from the row.
   */
  public function getValue($field);
}
