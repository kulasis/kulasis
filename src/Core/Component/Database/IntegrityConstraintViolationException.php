<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\IntegrityConstraintViolationException
 */

namespace Kula\Core\Component\Database;

/**
 * Exception thrown if a query would violate an integrity constraint.
 *
 * This exception is thrown e.g. when trying to insert a row that would violate
 * a unique key constraint.
 */
class IntegrityConstraintViolationException extends \RuntimeException implements DatabaseException { }
