<?php

namespace Kula\Core\Component\Field;

abstract class Field {
  
  protected $container;
  
  const REMOVE_FIELD = 'remove_field';
  
  public function __construct($container) {
    $this->container = $container;
  }
  
  public function db() {
    return $this->container->get('kula.core.db');
  }
  
}