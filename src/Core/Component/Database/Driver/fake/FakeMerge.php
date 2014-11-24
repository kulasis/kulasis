<?php

/**
 * @file
 * Contains Kula\Core\Component\Database\Driver\fake\FakeMerge.
 */

namespace Kula\Core\Component\Database\Driver\fake;

use Kula\Core\Component\Database\Query\Condition;
use Kula\Core\Component\Database\Query\InvalidMergeQueryException;
use Kula\Core\Component\Database\Query\Merge;

/**
 * Defines FakeMerge for use in database tests.
 */
class FakeMerge extends Merge {

  /**
   * Constructs a fake merge object and initializes the conditions.
   *
   * @param array $database_contents
   *   The database contents faked as an array. Each key is a table name, each
   *   value is a list of table rows.
   * @param string $table
   *   The database table to merge into.
   */
  public function __construct(array &$database_contents, $table) {
    $this->databaseContents = &$database_contents;
    $this->table = $table;
    $this->conditionTable = $table;
    $this->condition = new Condition('AND');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!count($this->condition)) {
      throw new InvalidMergeQueryException(t('Invalid merge query: no conditions'));
    }
    $select = new FakeSelect($this->databaseContents, $this->conditionTable, 'c');
    $count = $select
      ->condition($this->condition)
      ->countQuery()
      ->execute()
      ->fetchField();
    if ($count) {
      $update = new FakeUpdate($this->databaseContents, $this->table);
      $update
        ->fields($this->updateFields)
        ->condition($this->condition)
        ->execute();
      return self::STATUS_UPDATE;
    }
    $insert = new FakeInsert($this->databaseContents, $this->table);
    $insert->fields($this->insertFields)->execute();
    return self::STATUS_INSERT;
  }

}
