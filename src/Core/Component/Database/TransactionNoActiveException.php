<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\TransactionNoActiveException
 */

namespace Kula\Core\Component\Database;

/**
 * Exception for when popTransaction() is called with no active transaction.
 */
class TransactionNoActiveException extends TransactionException implements DatabaseException { }
