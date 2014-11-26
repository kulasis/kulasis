<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kula\Core\Component\DB\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;

/**
 * MemoryDataCollector.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DatabaseDataCollector extends DataCollector implements LateDataCollectorInterface {
    private $databaseLog;
    
    public function __construct($database) {
      $this->databaseLog = $database;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null) {
    }

    /**
     * {@inheritdoc}
     */
    public function lateCollect() {
        $this->data['queries'] = $this->databaseLog->getLogger();
    }
    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'database';
    }
    
    public function getQueries() {
      return $this->data['queries'];
    }
    
    public function getQueryCount() {
      return count($this->data['queries']);
    }
}
