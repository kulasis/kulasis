<?php

namespace Kula\Core\Bundle\LoginBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KulaCoreLoginBundle extends Bundle
{
  public function build(ContainerBuilder $container) {
      parent::build($container);
  }
  
  public function boot() {

  }

}
