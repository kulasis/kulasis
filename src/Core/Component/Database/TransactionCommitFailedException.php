<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\TransactionCommitFailedException
 */

namespace Kula\Core\Component\Database;

/**
 * Exception thrown when a commit() function fails.
 */
class TransactionCommitFailedException extends TransactionException implements DatabaseException { }
