<?php

namespace Kula\Core\Bundle\FrameworkBundle\Extension;

class TwigExtension extends \Twig_Extension {
  
  private $db;
  private $request;
  private $session;
  
  public function __construct($db, $request, $session) {
    $this->db = $db;
    $this->request = $request;
    $this->session = $session;
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
      new \Twig_SimpleFunction('getNavigation', array('Kula\Core\Component\Navigation\Navigation', 'getFormsWithGroups'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('getReports', array('Kula\Core\Component\Navigation\Navigation', 'getReportsWithGroups'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('getTabs', array('Kula\Core\Component\Navigation\Navigation', 'getTabsForNavigation'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('getActionsMenu', array('Kula\Core\Component\Navigation\Navigation', 'getActionsMenuForNavigation'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('getReportsMenu', array('Kula\Core\Component\Navigation\Navigation', 'getReportsMenuForNavigation'), array('is_safe' => array('html'))),
      new \Twig_SimpleFunction('getMethodsForRoute', array('Kula\Core\Component\Navigation\Navigation', 'getMethodsForRoute'), array('is_safe' => array('html')))
    );
  }
  
  public function getGlobals() {
    $current_request = $this->request->getCurrentRequest();
    $globals_array = array();
    /*
    $navigation_info = \Kula\Core\Component\Navigation\Navigation::getNavigationInfoFromPath($current_request->attributes->get('_route'));
    
    $globals_array = array(
      'session' => $this->session,
      'focus' => $container->get('kula.focus'),
      'flash' => $container->get('session.flash_bag'),
      'request' => $current_request,
      
      'navigation_info' => $navigation_info,
      'partial' => $current_request->query->get('partial'),
      
      'mode' => 'edit',
      'form_action' => $current_request->getBaseUrl().$current_request->getPathInfo(),
    );
    */
    if ($this->session->get('user_id')) {
      $globals_array += array(
        'focus_usergroups' => Focus::usergroups($this->session->get('user_id')),
        'focus_organization' => Focus::getOrganizationMenu($this->session->get('organization_id')),
        'focus_terms' => Focus::terms($this->session->get('portal'), $this->session->get('administrator'), $this->session->get('user_id'))
      );
      
      if ($this->session->get('portal') == 'teacher' OR $this->session->get('portal') == 'student') {
        $organization = new \Kula\Component\Focus\Organization;
        $organization->setOrganization($this->session->get('organization_id'));
        $globals_array += array(
          'focus_schools' => Focus::getSchoolsMenu($organization->getSchoolOrganizationIDs()),
          'focus_teachers' => Focus::getTeachers($container->get('kula.focus')->getOrganizationTermIDs()),
          'focus_sections' => Focus::getSectionMenu($container->get('kula.focus')->getTeacherOrganizationTermID()),
        );
      }
      
    }
    
    return $globals_array;
  }
  
  public function getName() {
    return 'twig_extension';
  }
}