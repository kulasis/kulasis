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
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Kula\Core\Bundle\FrameworkBundle\Exception\NotAuthorizedException;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    $this->chooser = $this->container->get('kula.core.chooser');
    $this->searcher = $this->container->get('kula.core.searcher');
    $this->poster = null;
  }

  protected function db() {
    return $this->container->get('kula.core.db');
  }
  
  protected function prePoster() {
    return new \Kula\Core\Component\DB\PrePoster;
  }
  
  protected function poster() {
    return $this->poster;
  }
  
  protected function newPoster() {
    return $this->container->get('kula.core.poster_factory')->newPoster();
  }
  
  protected function chooser($chooser) {
    return $this->chooser->get($chooser);
  }
  
  protected function processForm() {
    $result = '';
    // Check if HTTP method is post for Poster processing
    if ($this->request->getMethod() == 'POST') {
      if ($this->request->request->get('mode') == 'search') {
        $result = $this->searcher->startProcessing($this->db(), $this->container->get('kula.core.schema'), $this->container->get('kula.core.permission'), $this->request);
      } else {
        $this->poster = $this->container->get('kula.core.poster');
        if ($this->request->request->get('add'))
          $this->poster->addMultiple($this->request->request->get('add'));
        if ($this->request->request->get('edit'))
          $this->poster->editMultiple($this->request->request->get('edit'));
        if ($this->request->request->get('delete'))
          $this->poster->deleteMultiple($this->request->request->get('delete'));
        $this->poster->process();
        
        if ($this->poster->isPosted()) {
          $this->addFlash('success', 'Changes saved.');
        } else {
          $this->addFlash('info', 'No changes saved.');
        }
        
      }
    }
  }
  
  protected function form($variable, $table, $id = null, $field = null) {
    
    $query = $this->request->query->get($variable);
    $request = $this->request->request->get($variable);
    
    if (!isset($query) AND !isset($request)) {
      return null;
    } elseif (isset($query)) {
      $var = $this->request->query->get($variable);
    } elseif (isset($request)) {
      $var = $this->request->request->get($variable);
    }
    
    if (!isset($var[$table])) {
      return null;
    }
    
    if (isset($var[$table][$id][$field])) {
      return $var[$table][$id][$field];
    }
    
    if (isset($var[$table][$id]) AND $field === null) {
      return $var[$table][$id];
    }
    
    if (isset($var[$table][$field]) AND $id === null) {
      return $var[$table][$field];
    }
    
    if (isset($var[$table]) AND $field === null AND $id === null) {
      return $var[$table];
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
    $this->twig->addGlobal('kula_core_record', $this->record);
    $this->twig->addGlobal('record_bar_template_path', $this->record->getRecordBarTemplate());

    $this->twig->addGlobal('selected_record_bar_template_path', $this->record->getSelectedRecordBarTemplate());
    $this->twig->addGlobal('mode', $this->getSubmitMode());  
    $this->twig->addGlobal('form_action', $this->getFormAction());
  }
  
  public function authorize($tables = array()) {
		if (!($this->session->get('initial_role') > 0)) {
			throw new NotAuthorizedException();
		}
  }
  
  /**
   * Forwards the request to another controller.
   *
   * @param string $controller The controller name (a string like BlogBundle:Post:index)
   * @param array  $path       An array of path parameters
   * @param array  $query      An array of query parameters
   *
   * @return Response A Response instance
   */
  public function forward($routeName, array $query = array(), array $request = array())
  {
		if ($this->request->isXmlHttpRequest()) {
			$query['partial'] = 'window';
      $query['request'] = 'window';
		}
    
      $path['_route'] = $this->request->attributes->get('_route');
      $subRequest = $this->container->get('request_stack')->getCurrentRequest()->duplicate($query, $request, $path, null, null, array('REQUEST_URI' => $this->container->get('router')->generate($routeName, array())));
      $subRequest->setMethod('GET');
      
      return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
  }
  
	public function JSONResponse($data) {
		$response = new JsonResponse();
		$response->setData($data);
		return $response;
	}

}
