<?php

namespace Kula\Core\Component\Twig;

use Kula\Core\Router;

class GeneratePath {
  
  public static function linkTo($link_name, $route_name, $parameters = array()) {
    
    $container = $GLOBALS['kernel']->getContainer();
    
    $link = '<a href="' . $container->get('router')->generate($route_name, $parameters) . '">' . $link_name . '</a>';
    
    return $link;
    
  }
  
}