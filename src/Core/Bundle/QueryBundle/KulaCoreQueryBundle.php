<?php

namespace Kula\Core\Bundle\QueryBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KulaCoreQueryBundle extends Bundle
{
  public function build(ContainerBuilder $container) {
      parent::build($container);
  }
  
  public function boot() {

  }

}
