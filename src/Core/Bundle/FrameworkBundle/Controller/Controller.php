<?php

/**
 * @author Makoa Jacobsen <makoa@makoajacobsen.com>
 * @copyright Copyright (c) 2014, Oregon College of Art & Craft
 * @license MIT. Based on Symfony's FrameworkBundle, MIT license.
 *
 * @package Kula SIS
 * @subpackage Core
 *
 * Base controller that includes all Kula SIS\Core functionality. May be extended
 * further to include additional functionality.
 */

namespace Kula\Core\Bundle\FrameworkBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\DependencyInjection\ContainerInterface as ContainerInterface;

class Controller extends BaseController {
  
  protected $form_action;
  
  public function setContainer(ContainerInterface $container = null){
    parent::setContainer($container);
    
    $this->session = $this->container->get('kula.core.session');
    $this->flash = $this->container->get('session')->getFlashBag();
    $this->request = $this->container->get('request_stack')->getCurrentRequest();
    $this->twig = $this->container->get('twig');
    $this->focus = $this->container->get('kula.core.focus');
    $this->record = $this->container->get('kula.core.record');
  }

  protected function db() {
    return $this->container->get('kula.core.db');
  }
  
  protected function processForm() {
    $result = '';
    // Check if HTTP method is post for Poster processing
    if ($this->request->getMethod() == 'POST') {
      if ($this->request->request->get('mode') == 'search') {
        $result = \Kula\Component\Database\Searcher::startProcessing();  
      } else {
        $this->poster = $this->container->get('kula.core.poster');
      }
    }
  }
  
  public function getSubmitMode() {
    return $this->record->getSubmitMode();
  }
  
  public function setSubmitMode($mode) {
    $this->twig->addGlobal('mode', $mode);
  }
  
  public function getFormAction() {
    if (!$this->form_action)
      $this->formAction();
    return $this->form_action;
  }
  
  public function formAction($route_name = null, $parameters = array()) {
    if (!$route_name) {
      $parameters['id'] = $this->record->getSelectedRecordID();
      $this->form_action = $this->request->getBaseUrl().$this->request->getPathInfo();
    } else {
      $this->form_action = $this->generateUrl($route_name, $parameters);
      $this->twig->addGlobal('form_action', $this->form_action);
    }
  }
  
  public function setRecordType($record_type, $add_mode = null, $eager_search_data = null) {
    $this->record->setRecordType($record_type, $add_mode, $eager_search_data);
    
    $this->twig->addGlobal('record_type', $this->record->getRecordType());
    $this->twig->addGlobal('record_obj', $this->record);
    
    $this->twig->addGlobal('record_bar_template_path', $this->record->getRecordBarTemplate());
    
    $this->twig->addGlobal('selected_record_bar_template_path', $this->record->getSelectedRecordBarTemplate());
    $this->twig->addGlobal('mode', $this->getSubmitMode());  
    $this->twig->addGlobal('form_action', $this->getFormAction());
  }
  
  public function authorize($tables = array()) {
		if ($this->session->get('initial_role') > 0) {
			return true;
		}
    
  }

}