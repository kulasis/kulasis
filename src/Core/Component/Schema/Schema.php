<?php

namespace Kula\Core\Component\Schema;

use Symfony\Component\Yaml\Yaml;

use Kula\Core\Component\Database\Database;
use Kula\Core\Component\Database\Query\Condition;

class Schema {
  
  public $schema = array();
  
  public function synchronize() {
    
  }
  
  public function getSchemaFromBundles(array $bundles) {
    
    foreach($bundles as $bundle) {
      if (file_exists($bundle->getPath().'/Resources/config/schema.yml')) {
        $this->schema = array_merge_recursive($this->schema, Yaml::parse($bundle->getPath().'/Resources/config/schema.yml'));
      }
    }
    
  }
  
}