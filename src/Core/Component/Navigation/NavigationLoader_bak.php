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
          $this->loadNavigationFromFile($nav);
          $this->paths[] = new FileResource($path);
        }
      }
    }

  }
  
  private function loadNavigationFromFile($navFromFile, $type = null, $parent = null) {
    
    if (count($navFromFile) > 0) {
      
      foreach($navFromFile as $name => $nav) {
        
        if ($parent) { $name = $parent.'.'.$name; }
        
        $this->navigation[$name] = (array_key_exists($name, $this->navigation)) ? array_merge_recursive($this->navigation[$name], $nav) : $nav;
        
        // Check
        if (isset($nav['first'])) {
          $this->navigation_first[$nav['first']] = $name;
        }
        
        if (isset($nav['type']) AND !$type) $type = $nav['type'];
        
        // Update parent's children
        if (isset($nav['parent']) AND in_array($type, array('form', 'report', 'dir'))) {
          $this->navigation[$nav['parent']]['children'][] = $name;
        } elseif ($parent AND in_array($type, array('form', 'report', 'dir'))) {
          $this->navigation[$parent]['children'][] = $name;
        } elseif (isset($nav['type']) AND in_array($type, array('form','report', 'dir'))) {
          if (!in_array($name, $this->navigation_top)) $this->navigation_top[] = $name;
        } else {
          if (isset($nav['type']) AND $nav['type'] == 'tab') {
            $this->addChildren('tab', 'tab_keys', array($name => $nav), $nav['parent']);
          }
        }
        
        if (isset($nav['tabs'])) {
          $this->addChildren('tab', 'tab_keys', $nav['tabs'], $name);
          $this->navigation[$name]['tabs'] = null;
        }
        if (isset($nav['menu_actions'])) {
          $this->addChildren('menu_action', 'menu_action_keys', $nav['menu_actions'], $name); 
          $this->navigation[$name]['menu_actions'] = null;
        }
        if (isset($nav['menu_reports'])) {
          $this->addChildren('menu_report', 'menu_report_keys', $nav['menu_reports'], $name); 
          $this->navigation[$name]['menu_reports'] = null;
        }
        if (isset($nav['button_add'])) {
          $this->navigation[$name.'.button_add'] = $nav['button_add'];
          $this->navigation[$name.'.button_add']['type'] = 'button_add';
        }
        if (isset($nav['button_delete'])) {
          $this->navigation[$name.'.button_delete'] = $nav['button_delete']; 
          $this->navigation[$name.'.button_delete']['type'] = 'button_delete';
        }
        
        if (isset($type)) $this->navigation[$name]['type'] = $type;
        
        // if forms, recursive loop
        if (isset($nav['forms'])) {
          $this->loadNavigationFromFile($nav['forms'], 'form', $name);
          unset($this->navigation[$name]['forms']);
        }
        
        // if reports, recursive loop
        if (isset($nav['reports'])) {
          $this->loadNavigationFromFile($nav['reports'], 'report', $name);
          unset($this->navigation[$name]['reports']);
        }
        
      } // end foreach on iteration
      
    } // end if on count
    
  }
  
  private function addChildren($type, $element, $children, $parent) {
    
    if (count($children) > 0) {
      
      // unset list
      if (!isset($this->navigation[$parent][$element]) OR !$this->is_numeric_array($this->navigation[$parent][$element]))
        $this->navigation[$parent][$element] = array();
      
      foreach($children as $name => $nav) {

        $keyName = $parent.'.'.$name;

        $nav['type'] = $type;
        $nav['parent'] = $parent;
        
        $this->navigation[$keyName] = (array_key_exists($keyName, $this->navigation)) ? array_merge_recursive($this->navigation[$keyName], $nav) : $nav;
        
        // Update parent's children
        $this->navigation[$parent][$element][] = $keyName;
        
      } // end foreach
      
    } // end if
    
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
        
        // TO DO sort keys in array using usort php function
        
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
  
  private function is_numeric_array($array) {
    $i = 0;
    foreach ($array as $a => $b) { if (is_int($a)) { ++$i; } }
    if (count($array) === $i) { return true; }
    else { return false; }
  }
}