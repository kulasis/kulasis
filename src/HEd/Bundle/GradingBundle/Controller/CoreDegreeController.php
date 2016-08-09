<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreDegreeController extends Controller {
  
  public function degreesAction() {
    $this->authorize();
    $this->processForm();
    
    $degress = array();
    
      // Get Degrees
      $degrees = $this->db()->db_select('STUD_DEGREE')
        ->fields('STUD_DEGREE')
        ->orderBy('DEGREE_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:CoreDegree:degrees.html.twig', array('degrees' => $degrees));
  }
  
  public function majorsAction() {
    $this->authorize();
    $this->processForm();
    
    $majors = array();
    
      // Get Majors
      $majors = $this->db()->db_select('STUD_DEGREE_MAJOR')
        ->fields('STUD_DEGREE_MAJOR')
        ->orderBy('MAJOR_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:CoreDegree:majors.html.twig', array('majors' => $majors));
  }
  
  public function minorsAction() {
    $this->authorize();
    $this->processForm();
    
    $minors = array();
    
      // Get Minors
      $minors = $this->db()->db_select('STUD_DEGREE_MINOR')
        ->fields('STUD_DEGREE_MINOR')
        ->orderBy('MINOR_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:CoreDegree:minors.html.twig', array('minors' => $minors));
  }
  
  public function concentrationsAction() {
    $this->authorize();
    $this->processForm();
    
    $concentrations = array();
    
      // Get Concentrations
      $concentrations = $this->db()->db_select('STUD_DEGREE_CONCENTRATION')
        ->fields('STUD_DEGREE_CONCENTRATION')
        ->orderBy('CONCENTRATION_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:CoreDegree:concentrations.html.twig', array('concentrations' => $concentrations));
  }

  public function areasAction() {
    $this->authorize();
    $this->processForm();
    
    $areas = array();
    
      // Get Concentrations
      $areas = $this->db()->db_select('STUD_DEGREE_AREA')
        ->fields('STUD_DEGREE_AREA')
        ->orderBy('AREA_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:CoreDegree:areas.html.twig', array('areas' => $areas));
  }
  
}