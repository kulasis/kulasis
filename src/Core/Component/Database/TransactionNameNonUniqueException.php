<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\TransactionNameNonUniqueException
 */

namespace Kula\Core\Component\Database;

/**
 * Exception thrown when a savepoint or transaction name occurs twice.
 */
class TransactionNameNonUniqueException extends TransactionException implements DatabaseException { }
