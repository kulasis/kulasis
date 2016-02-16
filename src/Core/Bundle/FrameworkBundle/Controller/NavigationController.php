<?php

namespace Kula\Core\Bundle\FrameworkBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class NavigationController extends Controller {
  
  public function navigationTreeAction() {
    $this->authorize();
    
    $tree = array();
    
    $tops = $this->get('kula.core.navigation')->getNavigationTreeTop();
    
    foreach($tops as $top) {
      $tree[] = array('text' => $top);
    }
    
    return $this->JSONResponse($tree);
  }
  
}