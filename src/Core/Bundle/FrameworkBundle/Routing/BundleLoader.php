<?php 

namespace Kula\Core\Bundle\FrameworkBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

class BundleLoader extends Loader
{
  public function __construct($kernel) {
    $this->kernel = $kernel;
  }
    
    public function load($resource, $type = null) {
      $collection = new RouteCollection();
        
      foreach($this->kernel->getBundles() as $bundle) {
        $path = $bundle->getPath().'/Resources/config/routing.yml';
        if (file_exists($path)) {
          $collection->addCollection($this->import('@'.$bundle->getName().'/Resources/config/routing.yml', 'yaml'));
        }
      }
      return $collection;
    }
    
    public function supports($resource, $type = null) {
        return $type === 'kula_routing';
    }
    
}