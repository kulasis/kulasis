<?php

namespace Kula\Core\Bundle\FrameworkBundle\Extension;

use Kula\Core\Component\Twig\Focus;

class TwigExtension extends \Twig_Extension {
  
  private $db;
  private $request;
  private $session;
  
  public function __construct($container) {
    $this->container = $container;
    $this->db = $container->get('kula.core.db');
    $this->request = $container->get('request_stack');
    $this->session = $container->get('kula.core.session');
    $this->flash = $container->get('flash');
    $this->navigation = $container->get('kula.core.navigation');
    $this->router = $container->get('router');
    $this->lookup = $container->get('kula.core.lookup');
    
    \Kula\Core\Component\Twig\Field::setDependencies($container, $container->get('kula.core.permission'), $container->get('kula.core.focus'), $container->get('kula.core.record'), $container->get('kula.core.poster'), $container->get('kula.core.schema'), $container->get('kula.core.db'), $container->get('kula.core.session'), $container->get('kula.core.chooser'), $container->get('kula.core.lookup'));
  }
  
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('kula_field', array('Kula\Core\Component\Twig\Field', 'field'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('kula_field_name', array('Kula\Core\Component\Twig\Field', 'fieldName'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('kula_display_html', array('Kula\Core\Component\Twig\Field', 'displayHTML'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('kula_table_add', array('Kula\Core\Component\Twig\Field', 'addButton'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('form_button', array('Kula\Core\Component\Twig\GenericField', 'button'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('form_select', array('Kula\Core\Component\Twig\GenericField', 'select'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('form_text', array('Kula\Core\Component\Twig\GenericField', 'text'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('linkTo', array('Kula\Core\Component\Twig\GeneratePath', 'linkTo'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('table_header', array('Kula\Core\Component\Twig\Table', 'header'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('table_footer', array('Kula\Core\Component\Twig\Table', 'footer'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('table_row_form', array('Kula\Core\Component\Twig\Table', 'rowForm'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('table_tbody_open', array('Kula\Core\Component\Twig\Table', 'openTBody'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('table_tbody_close', array('Kula\Core\Component\Twig\Table', 'closeTBody'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('getMethodsForRoute', array('Kula\Core\Component\Utility\Router', 'getMethodsForRoute'), array('is_safe' => array('html'))) 
    );
  }
  
  public function getGlobals() {
    $current_request = $this->request->getCurrentRequest();
    $globals_array = array();
    
    $globals_array = array(
      'kula_instance_name' => $this->container->getParameter('instance_name'),
      'session' => $this->session,
      'focus' => $this->container->get('kula.core.focus'),
      'flash' => $this->flash,
      'request' => $current_request,
      'kula_core_navigation' => $this->navigation,
      'partial' => $current_request->query->get('partial'),
      'router' => $this->router,
      'mode' => 'edit',
      'form_action' => $current_request->getBaseUrl().$current_request->getPathInfo(),
    );
    
    if ($this->session->get('user_id')) {
      $globals_array += array(
        'focus_usergroups' => Focus::usergroups($this->db, $this->session->get('user_id'))
      );
    }
    if ($this->session->get('user_id') AND $this->session->get('portal') == 'sis') {
        $globals_array += array(
        'focus_organization' => Focus::getOrganizationMenu($this->container->get('kula.core.organization'), $this->session->get('organization_id')),
        'focus_terms' => Focus::terms($this->container->get('kula.core.organization'), $this->container->get('kula.core.term'), $this->session->get('organization_id'), $this->session->get('portal'), $this->session->get('administrator'), $this->session->get('user_id'))
      );
      
    }
    
    if ($this->session->get('portal') == 'teacher' OR $this->session->get('portal') == 'student') {
      $globals_array += array(
        'focus_schools' => Focus::getSchoolsMenu($this->container->get('kula.core.organization'), $this->container->get('kula.core.organization')->getSchools($this->session->get('organization_id'))),
        'focus_terms' => Focus::terms($this->container->get('kula.core.organization'), $this->container->get('kula.core.term'), $this->session->get('organization_id'), $this->session->get('portal'), $this->session->get('administrator'), $this->session->get('user_id'), $this->container->get('kula.core.db'), $this->container->get('kula.core.focus')),
        'focus_teachers' => Focus::getTeachers($this->container->get('kula.core.db'), $this->container->get('kula.core.focus')->getOrganizationTermIDs()),
        'focus_sections' => Focus::getSectionMenu($this->container->get('kula.core.db'), $this->container->get('kula.core.focus')->getTeacherOrganizationTermID()),
      );
    }
    
    return $globals_array;
  }
  
  public function getName() {
    return 'twig_extension';
  }
}