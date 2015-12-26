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
use Symfony\Component\HttpKernel\Event\GetResponseEvent as GetResponseEvent;

use Kula\Core\Component\Database\Database;

class KulaCoreFrameworkBundle extends BaseFrameworkBundle {
  
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();

    if (preg_match('/(android|blackberry|iphone|ipad|phone|playbook|mobile)/i', $request->headers->get('user-agent'))) {
      //ONLY AFFECT HTML REQUESTS
      //THIS ENSURES THAT YOUR JSON REQUESTS TO E.G. REST API, DO NOT GET SERVED TEXT/HTML CONTENT-TYPE
      if ($request->getRequestFormat() == "html") {
        $request->setRequestFormat('mobile');
      }
    }
  }
  
}
