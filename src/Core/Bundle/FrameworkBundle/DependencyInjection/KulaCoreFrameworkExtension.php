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
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;




class KulaCoreFrameworkExtension extends BaseFrameworkExtension {

  public function load(array $configs, ContainerBuilder $container) {
      parent::load($configs, $container);
      
      // Load services files
      $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
      $loader->load('services.yml');
  }

}