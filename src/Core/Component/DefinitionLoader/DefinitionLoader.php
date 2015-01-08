<?php

namespace Kula\Core\Component\DefinitionLoader;

use Symfony\Component\Yaml\Yaml;

class DefinitionLoader {

  public static function loadDefinitionsFromBundles(array $bundles, $fileName) {
    
    $definition = array();
    
    foreach($bundles as $bundle) {
      $path = $bundle->getPath().'/Resources/config/'.$fileName.'.yml';
      if (file_exists($path)) {
        $bundledSchema = Yaml::parse($path);
        
        if (isset($bundledSchema['imports'])) {
          
          foreach($bundledSchema['imports'] as $import) {
            
            $importPath = $bundle->getPath().'/Resources/config/' . $import['resource'];
            
            if (file_exists($importPath)) {
              $definition[$importPath] = Yaml::parse($importPath);
            }
          }
          
        } else {
          unset($bundledSchema['imports']);
          $definition[$path] = $bundledSchema;
        }
          
        
      }
    }
    return $definition;
  }

}