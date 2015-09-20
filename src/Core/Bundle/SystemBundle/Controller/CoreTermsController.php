<?php

namespace Kula\Core\Bundle\SystemBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreTermsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    
    $term_service = $this->get('kula.core.term');
    
    // Get terms
    $terms = $this->db()->db_select('CORE_TERM')
      ->fields('CORE_TERM')
      ->condition('START_DATE', $term_service->getStartDate($this->focus->getTermID()), '>=')
      ->orderBy('START_DATE', 'ASC')
      ->orderBy('END_DATE', 'ASC')
      ->orderBy('TERM_ABBREVIATION', 'ASC')
      ->orderBy('TERM_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaCoreSystemBundle:Terms:index.html.twig', array('terms' => $terms));
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('Core.Term')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
}