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

namespace Kula\HEd\Bundle\FinancialAidBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class KulaHEdFinancialAidExtension extends Extension {

  public function load(array $configs, ContainerBuilder $container) {
      // Load services files
      $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
      $loader->load('services.yml');
  }

}