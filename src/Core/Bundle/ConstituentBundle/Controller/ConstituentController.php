<?php

namespace Kula\Core\Bundle\ConstituentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class ConstituentController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('Core.Constituent');
    
    return $this->render('KulaCoreConstituentBundle:Constituent:index.html.twig');
  }
  
  public function combineAction() {
    $this->authorize();
    
    $combine = $this->request->request->get('non');
    if (isset($combine['Core.Constituent']['delete']['Core.Constituent.ID']['value']) AND isset($combine['Core.Constituent']['keep']['Core.Constituent.ID']['value'])) {
      
      if ($result = $this->get('kula.Core.Combine')->combine('CONS_CONSTITUENT', $combine['Core.Constituent']['delete']['Core.Constituent.ID']['value'], $combine['Core.Constituent']['keep']['Core.Constituent.ID']['value'])) {
        $this->addFlash('success', 'Combined constitutents.');
      } else {
        $this->addFlash('error', 'Unable to combined constitutents.');
      }
      
    }

    return $this->render('KulaCoreConstituentBundle:Constituent:combine.html.twig');
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('Core.Constituent')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}
