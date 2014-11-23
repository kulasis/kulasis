<?php

/**
 * @author Makoa Jacobsen <makoa@makoajacobsen.com>
 * @copyright Copyright (c) 2014, Oregon College of Art & Craft
 * @license MIT. Based on Symfony's FrameworkBundle, MIT license.
 *
 * @package Kula SIS
 * @subpackage Core
 *
 * Extend Symfony's FrameworkBundle functionality.
 */

namespace Kula\Core\Bundle\FrameworkBundle\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension as BaseFrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FrameworkExtension extends BaseFrameworkExtension {

  public function load(array $configs, ContainerBuilder $container) {
      parent::load($configs, $container);
  }

}