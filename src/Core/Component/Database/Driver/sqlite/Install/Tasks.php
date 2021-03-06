<?php

/**
 * @file
 * Definition of Kula\Core\Component\Database\Driver\sqlite\Install\Tasks
 */

namespace Kula\Core\Component\Database\Driver\sqlite\Install;

use Kula\Core\Component\Database\Database;
use Kula\Core\Component\Database\Driver\sqlite\Connection;
use Kula\Core\Component\Database\DatabaseNotFoundException;
use Kula\Core\Component\Database\Install\Tasks as InstallTasks;

/**
 * Specifies installation tasks for SQLite databases.
 */
class Tasks extends InstallTasks {

  /**
   * {@inheritdoc}
   */
  protected $pdoDriver = 'sqlite';

  /**
   * {@inheritdoc}
   */
  public function name() {
    return t('SQLite');
  }

  /**
   * {@inheritdoc}
   */
  public function minimumVersion() {
    // @todo Consider upping to 3.6.8 in Drupal 8 to get SAVEPOINT support.
    return '3.3.7';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormOptions(array $database) {
    $form = parent::getFormOptions($database);

    // Remove the options that only apply to client/server style databases.
    unset($form['username'], $form['password'], $form['advanced_options']['host'], $form['advanced_options']['port']);

    // Make the text more accurate for SQLite.
    $form['database']['#title'] = t('Database file');
    $form['database']['#description'] = t('The absolute path to the file where @drupal data will be stored. This must be writable by the web server and should exist outside of the web root.', array('@drupal' => drupal_install_profile_distribution_name()));
    $default_database = conf_path(FALSE) . '/files/.ht.sqlite';
    $form['database']['#default_value'] = empty($database['database']) ? $default_database : $database['database'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function connect() {
    try {
      // This doesn't actually test the connection.
      db_set_active();
      // Now actually do a check.
      Database::getConnection();
      $this->pass('Drupal can CONNECT to the database ok.');
    }
    catch (\Exception $e) {
      // Attempt to create the database if it is not found.
      if ($e->getCode() == Connection::DATABASE_NOT_FOUND) {
        // Remove the database string from connection info.
        $connection_info = Database::getConnectionInfo();
        $database = $connection_info['default']['database'];

        // We cannot use file_directory_temp() here because we haven't yet
        // successfully connected to the database.
        $connection_info['default']['database'] = drupal_tempnam(sys_get_temp_dir(), 'sqlite');

        // In order to change the Database::$databaseInfo array, need to remove
        // the active connection, then re-add it with the new info.
        Database::removeConnection('default');
        Database::addConnectionInfo('default', 'default', $connection_info['default']);

        try {
          Database::getConnection()->createDatabase($database);
          Database::closeConnection();

          // Now, restore the database config.
          Database::removeConnection('default');
          $connection_info['default']['database'] = $database;
          Database::addConnectionInfo('default', 'default', $connection_info['default']);

          // Check the database connection.
          Database::getConnection();
          $this->pass('Drupal can CONNECT to the database ok.');
        }
        catch (DatabaseNotFoundException $e) {
          // Still no dice; probably a permission issue. Raise the error to the
          // installer.
          $this->fail(t('Database %database not found. The server reports the following message when attempting to create the database: %error.', array('%database' => $database, '%error' => $e->getMessage())));
        }
      }
      else {
        // Database connection failed for some other reason than the database
        // not existing.
        $this->fail(t('Failed to connect to your database server. The server reports the following message: %error.<ul><li>Is the database server running?</li><li>Does the database exist, and have you entered the correct database name?</li><li>Have you entered the correct username and password?</li><li>Have you entered the correct database hostname?</li></ul>', array('%error' => $e->getMessage())));
        return FALSE;
      }
    }
    return TRUE;
  }
}
