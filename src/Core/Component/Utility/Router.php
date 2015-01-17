<?php

namespace Kula\Core\Component\Utility;

class Router {

  public static function getMethodsForRoute($router, $route_name) {
    $route_collection = $router->getRouteCollection();
    
    if ($route = $route_collection->get($route_name))
      return $route->getMethods();
  }
  
}