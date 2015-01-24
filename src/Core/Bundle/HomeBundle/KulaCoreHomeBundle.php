<?php

namespace Kula\Core\Bundle\HomeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KulaCoreHomeBundle extends Bundle
{
  public function build(ContainerBuilder $container) {
      parent::build($container);
  }
  
  public function boot() {

  }

}
