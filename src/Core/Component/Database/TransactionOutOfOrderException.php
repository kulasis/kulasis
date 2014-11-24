<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\TransactionOutOfOrderException
 */

namespace Kula\Core\Component\Database;

/**
 * Exception thrown when a rollback() resulted in other active transactions being rolled-back.
 */
class TransactionOutOfOrderException extends TransactionException implements DatabaseException { }
