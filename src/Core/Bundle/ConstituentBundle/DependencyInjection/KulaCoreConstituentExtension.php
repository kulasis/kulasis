<?php

namespace Kula\Core\Bundle\ConstituentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

use Symfony\Component\DependencyInjection\Reference;

class KulaCoreConstituentExtension extends Extension {

  public function load(array $configs, ContainerBuilder $container) {

    $loader_yml = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    $loader_yml->load('services.yml');

  }
}