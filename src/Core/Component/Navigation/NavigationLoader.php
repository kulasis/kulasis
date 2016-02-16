<?php

namespace Kula\Core\Component\Navigation;

use Kula\Core\Component\DefinitionLoader\DefinitionLoader;
use Symfony\Component\Config\Resource\FileResource;

class NavigationLoader {
  
  private $navigation_top = array();
  private $navigation = array();
  private $navigation_first = array();
  public $paths = array();
  
  public function __construct($db, $cache) {
    $this->db = $db;
    $this->cache = $cache;
  }
  
  public function getNavigationFromBundles(array $bundles) {
    
    $navigation = DefinitionLoader::loadDefinitionsFromBundles($bundles, 'navigation');
    
    if ($navigation) {
      foreach($navigation as $path => $nav) {
        if ($nav) {
          $this->loadFile($nav);
          $this->paths[] = new FileResource($path);
        }
      }
    }

  }
  
  private function loadFile($navFromFile) {
    
    if (count($navFromFile) > 0) {
      
      foreach($navFromFile as $name => $nav) {
        
        // We're at the first level of the file so every first level element has to have a type, if not previously defined
        if (!isset($this->navigation[$name]) AND 
          (!isset($nav['type']) OR 
           !in_array($nav['type'], array('dir', 'form', 'report', 'tab', 'menu_action', 'menu_report', 'button_add', 'button_delete'))
          ) 
        ) {
          throw new \Exception($name.' does not have a valid type defined.');
        }
        
        // if it is already defined, get type
        if (isset($this->navigation[$name])) { 
          $type = $this->navigation[$name]['type'];
        } else {
          $type = $nav['type'];
        }
        
        if (isset($nav['first'])) {
          $this->navigation_first[$nav['first']] = $name;
        }
        
        if ($type == 'dir') {
          $name = $this->loadDirectory($name, $nav);
          
          if (isset($nav['parent'])) {
            $this->navigation[$nav['parent']]['children'][] = $name;
          }
        } 
        
        if ($type == 'form') {
          $name = $this->loadForm($name, $nav);
          
          if (isset($nav['parent'])) {
            $this->navigation[$nav['parent']]['children'][] = $name;
          }
        } 
        
        if ($type == 'report') {
          $name = $this->loadReport($name, $nav);
          
          if (isset($nav['parent'])) {
            $this->navigation[$nav['parent']]['children'][] = $name;
          }
        } 
        
        if ($type == 'tab') {
          $name = $this->loadTab($name, $nav);
          
          if (isset($nav['parent'])) {
            $this->navigation[$nav['parent']]['tabs'][] = $name;
          }
        } 
        
        if ($type == 'menu_action') {
          $name = $this->loadMenu('action', $name, $nav);
          if (isset($nav['parent'])) {
            $this->navigation[$nav['parent']]['menu_actions'][] = $name;
          }
        }
        
        if ($type == 'menu_report') {
          $name = $this->loadMenu('report', $name, $nav);
          if (isset($nav['parent'])) {
            $this->navigation[$nav['parent']]['menu_reports'][] = $name;
          }
        }
        
        
        
      } // end foreach
        
    } // end if
    
  }
  
  private function loadDirectory($name, $nav, $parent = null) {
    
    // Must load to navigation array first
    $name = $this->loadNavigationItem($name, $nav, $parent);
    
    if (isset($nav['forms'])) {
      foreach($nav['forms'] as $form_name => $form_nav) {
        $form_name = $this->loadForm($form_name, $form_nav, $name);
        $this->navigation[$name]['children'][] = $form_name;
      }
    }
    
    if (isset($nav['reports'])) {
      foreach($nav['reports'] as $report_name => $report_nav) {
        $report_name = $this->loadReport($report_name, $report_nav, $name);
        $this->navigation[$name]['children'][] = $report_name;
      }
    }
    
    return $name;
  }
  
  private function loadForm($name, $nav, $parent = null) {
    
    // Must load to navigation array first
    if (!isset($nav['type'])) $nav['type'] = 'form';
    $name = $this->loadNavigationItem($name, $nav, $parent);
    
    // Load tabs
    if (isset($nav['tabs']) AND count($nav['tabs'] > 0)) {
      foreach($nav['tabs'] as $tab_name => $tab_nav) {
        $tab_name = $this->loadTab($tab_name, $tab_nav, $name);
        $this->navigation[$name]['tabs'][] = $tab_name;
      }
    }
    
    // Load add button
    if (isset($nav['button_add'])) {
      $nav['button_add']['type'] = 'button_add';
      $button_add_name = $this->loadNavigationItem('button_add', $nav['button_add'], $name);
      $this->navigation[$name]['button_add'] = $button_add_name;
    }
    
    // Load delete button
    if (isset($nav['button_delete'])) {
      $nav['button_delete']['type'] = 'button_delete';
      $button_add_name = $this->loadNavigationItem('button_delete', $nav['button_delete'], $name);
      $this->navigation[$name]['button_delete'] = $button_add_name;
    }
    
    // Load menu actions
    if (isset($nav['menu_actions']) AND count($nav['menu_actions'] > 0)) {
      foreach($nav['menu_actions'] as $menu_action_name => $menu_action_nav) {
        $menu_action_name = $this->loadMenu('menu_action', $menu_action_name, $menu_action_nav, $name);
        $this->navigation[$name]['menu_actions'][] = $menu_action_name;
      }
    }
    
    // Load menu reports
    if (isset($nav['menu_reports']) AND count($nav['menu_reports'] > 0)) {
      foreach($nav['menu_reports'] as $menu_report_name => $menu_report_nav) {
        $menu_report_name = $this->loadMenu('menu_report', $menu_report_name, $menu_report_nav, $name);
        $this->navigation[$name]['menu_reports'][] = $menu_report_name;
      }
    }
    
    return $name;
  }
  
  private function loadReport($name, $nav, $parent = null) {
    
    // Must load to navigation array first
    if (!isset($nav['type'])) $nav['type'] = 'report';
    $name = $this->loadNavigationItem($name, $nav, $parent);
    
    return $name;
    
  }
  
  private function loadTab($name, $nav, $parent = null) {
    
    // Must load to navigation array first
    if (!isset($nav['type'])) $nav['type'] = 'tab';
    $name = $this->loadNavigationItem($name, $nav, $parent);
    
    return $name;
  }
  
  private function loadMenu($type, $name, $nav, $parent = null) {
    
    // Must load to navigation array first
    if (!isset($nav['type'])) $nav['type'] = $type;
    $name = $this->loadNavigationItem($name, $nav, $parent);
    
    return $name;
    
  }
  
  private function loadNavigationItem($name, $nav, $parent = null) {
    
    if ($parent) {
      $name = $parent.'.'.$name;
      $nav['parent'] = $parent;
    } else {
      $this->navigation_top[] = $name;
    }
    
    if (isset($nav['forms'])) unset($nav['forms']);
    if (isset($nav['reports'])) unset($nav['reports']);
    if (isset($nav['tabs'])) unset($nav['tabs']);
    if (isset($nav['button_add'])) unset($nav['button_add']);
    if (isset($nav['button_delete'])) unset($nav['button_delete']);
    if (isset($nav['menu_actions'])) unset($nav['menu_actions']);
    if (isset($nav['menu_reports'])) unset($nav['menu_reports']);
    
    if (array_key_exists($name, $this->navigation)) {
      if (isset($this->navigation[$name]['type'])) unset($nav['type']);
      $nav = array_merge_recursive($this->navigation[$name], $nav);
    }
    
    $this->navigation[$name] = $nav;
    
    return $name;
    
  }
  
  public function synchronizeDatabaseCatalog() {
    
    // Loop through navigation
    foreach($this->navigation as $key => $nav) {
      
      // Check if key exists
      $exists = $this->db->db_select('CORE_NAVIGATION', 'nav')
        ->fields('nav', array('NAVIGATION_ID', 'NAVIGATION_NAME', 'SORT', 'DISPLAY_NAME', 'DIVIDER_BEFORE', 'CONFIRMATION_MESSAGE'))
        ->condition('nav.NAVIGATION_NAME', $key)
        ->execute()->fetch();
        $this->navigation[$key]['id'] = $exists['NAVIGATION_ID'];
      if (!$exists) {
        $this->navigation[$key]['id'] = $this->db->db_insert('CORE_NAVIGATION', array('target' => 'schema'))->fields(array('NAVIGATION_NAME' => $key, 'NAVIGATION_TYPE' => $nav['type'], 'CREATED_TIMESTAMP' => date('Y-m-d H:i:s')))->execute();
      } else {
        // modify $this->navigation with any overrides
        if (array_key_exists('DISPLAY_NAME', $exists) AND $exists['DISPLAY_NAME']) $this->navigation[$key]['display_name'] = $exists['DISPLAY_NAME'];
        if (array_key_exists('DIVIDER_BEFORE', $exists) AND $exists['DIVIDER_BEFORE']) $this->navigation[$key]['divider_before'] = $exists['DIVIDER_BEFORE'];
        if (array_key_exists('CONFIRMATION_MESSAGE', $exists) AND $exists['CONFIRMATION_MESSAGE']) $this->navigation[$key]['confirmation_message'] = $exists['CONFIRMATION_MESSAGE'];
        
      }
      
    } // end foreach
    
    // now sort
    foreach($this->navigation as $name => $nav) {
      
      if (isset($this->navigation[$name]['children'])) {
        
        $this->sort_children($name);
        
      }
      
    } // end foreach
    
    $this->sort_top();
        
  }
  
  private function sort_top() {
    
    $ordered_dir_children = array();
    $ordered_form_report_children = array();
    
    foreach($this->navigation_top as $child) {
      // check if type directory
      if (isset($this->navigation[$child]['type']) AND $this->navigation[$child]['type'] == 'dir') {
        // directory
        $ordered_dir_children[] = $child;
      } else {
        // form or report
        $ordered_form_report_children[] = $child;
      }
    } // end foreach
    
    sort($ordered_dir_children);
    sort($ordered_form_report_children);
    
    $this->navigation_top = array_merge($ordered_dir_children, $ordered_form_report_children);
    
  }
  
  private function sort_children($child_name) {
    
    foreach($this->navigation[$child_name] as $name => $nav) {
      
      if (isset($this->navigation[$child_name]['children'])) {
    
        $ordered_dir_children = array();
        $ordered_form_report_children = array();
    
        // loop through children 
        foreach($this->navigation[$child_name]['children'] as $child) {
    
          // check if type directory
          if (isset($this->navigation[$child]['type']) AND $this->navigation[$child]['type'] == 'dir') {
            // directory
            $ordered_dir_children[] = $child;
          } else {
            // form or report
            $ordered_form_report_children[] = $child;
          }
        }

        usort($ordered_dir_children, array($this, 'sort_children_compared'));
        usort($ordered_form_report_children, array($this, 'sort_children_compared'));
        
        $this->navigation[$child_name]['children'] = array_merge($ordered_dir_children, $ordered_form_report_children);
        
        // loop through children
        foreach($this->navigation[$child_name]['children'] as $child) {
          if (isset($this->navigation[$child]['children']))
            $this->sort_children($child);
        }
      
      } // end if on children
      
    } // end foreach
    
  }
  
  private function sort_children_compared($a, $b) {
    
    // get sort
    if (isset($this->navigation[$a]['sort']) AND isset($this->navigation[$b]['sort'])) {
      if ($this->navigation[$a]['sort'] == $this->navigation[$b]['sort']) {
        return 0;
      }
      return ($this->navigation[$a]['sort'] < $this->navigation[$b]['sort']) ? -1 : 1;
    }
    
    // if a has sort and b doesn't, a wins
    if (isset($this->navigation[$a]['sort']) AND !isset($this->navigation[$b]['sort'])) {
      return 1;
    }
    
    // sort based on name
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
  }
  
  public function loadNavigation() {
    $this->cache->add('navigation.top', $this->navigation_top);
    $this->cache->add('navigation.first', $this->navigation_first);
    // Loop through navigation
    foreach($this->navigation as $key => $nav) {
      $this->cache->add('navigation.'.$key, $nav);
    } // end foreach
    return array('top' => $this->navigation_top, 'first' => $this->navigation_first, 'navigation' => $this->navigation);
  }
  

}