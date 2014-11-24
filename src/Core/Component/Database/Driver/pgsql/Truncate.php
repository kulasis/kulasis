<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\Driver\pgsql\Truncate
 */

namespace Kula\Core\Component\Database\Driver\pgsql;

use Kula\Core\Component\Database\Query\Truncate as QueryTruncate;

class Truncate extends QueryTruncate {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $this->connection->addSavepoint();
    try {
      $result = parent::execute();
    }
    catch (\Exception $e) {
      $this->connection->rollbackSavepoint();
      throw $e;
    }
    $this->connection->releaseSavepoint();

    return $result;
  }
}
