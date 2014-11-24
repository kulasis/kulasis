<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\DatabaseExceptionWrapper.
 */

namespace Kula\Core\Component\Database;

/**
 * This wrapper class serves only to provide additional debug information.
 *
 * This class will always wrap a PDOException.
 */
class DatabaseExceptionWrapper extends \RuntimeException implements DatabaseException {

}
