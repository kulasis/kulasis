<?php

namespace Kula\Core\Bundle\SystemBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SystemBundle extends Bundle
{
  public function build(ContainerBuilder $container)
  {
      parent::build($container);
	}
		
	public function boot() {
		
	}
	

}
