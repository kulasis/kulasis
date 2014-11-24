<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\TransactionException
 */

namespace Kula\Core\Component\Database;

/**
 * Exception thrown by an error in a database transaction.
 */
class TransactionException extends \RuntimeException implements DatabaseException { }
