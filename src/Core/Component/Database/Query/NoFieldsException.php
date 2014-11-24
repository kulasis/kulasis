<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\Query\NoFieldsException
 */

namespace Kula\Core\Component\Database\Query;

use Kula\Core\Component\Database\DatabaseException;

/**
 * Exception thrown if an insert query doesn't specify insert or default fields.
 */
class NoFieldsException extends \InvalidArgumentException implements DatabaseException {}
