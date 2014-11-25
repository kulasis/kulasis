<?php

/**
 * @author Makoa Jacobsen <makoa@makoajacobsen.com>
 * @copyright Copyright (c) 2014, Oregon College of Art & Craft
 * @license MIT
 *
 * @package Kula SIS
 * @subpackage Core
 *
 * Extend Symfony's FrameworkBundle functionality.
 */

namespace Kula\Core\Bundle\FrameworkBundle;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle as BaseFrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

use Kula\Core\Component\Database\Database;

class FrameworkBundle extends BaseFrameworkBundle {

}
