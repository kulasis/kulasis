<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISDegreeController extends Controller {
  
  public function degreesAction() {
    $this->authorize();
    $this->processForm();
    
    $degress = array();
    
      // Get Degrees
      $degrees = $this->db()->db_select('STUD_DEGREE')
        ->fields('STUD_DEGREE', array('DEGREE_ID', 'DEGREE_NAME', 'EFFECTIVE_TERM_ID', 'MINIMUM_GPA', 'MINIMUM_CREDITS'))
        ->orderBy('DEGREE_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:SISDegree:degrees.html.twig', array('degrees' => $degrees));
  }
  
  public function majorsAction() {
    $this->authorize();
    $this->processForm();
    
    $majors = array();
    
      // Get Majors
      $majors = $this->db()->db_select('STUD_DEGREE_MAJOR')
        ->fields('STUD_DEGREE_MAJOR', array('MAJOR_ID', 'MAJOR_NAME', 'EFFECTIVE_TERM_ID', 'MINIMUM_GPA', 'MINIMUM_CREDITS'))
        ->orderBy('MAJOR_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:SISDegree:majors.html.twig', array('majors' => $majors));
  }
  
  public function minorsAction() {
    $this->authorize();
    $this->processForm();
    
    $minors = array();
    
      // Get Minors
      $minors = $this->db()->db_select('STUD_DEGREE_MINOR')
        ->fields('STUD_DEGREE_MINOR', array('MINOR_ID', 'MINOR_NAME', 'EFFECTIVE_TERM_ID', 'MINIMUM_GPA', 'MINIMUM_CREDITS'))
        ->orderBy('MINOR_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:SISDegree:minors.html.twig', array('minors' => $minors));
  }
  
  public function concentrationsAction() {
    $this->authorize();
    $this->processForm();
    
    $concentrations = array();
    
      // Get Concentrations
      $concentrations = $this->db()->db_select('STUD_DEGREE_CONCENTRATION')
        ->fields('STUD_DEGREE_CONCENTRATION', array('CONCENTRATION_ID', 'CONCENTRATION_NAME', 'EFFECTIVE_TERM_ID', 'MINIMUM_GPA', 'MINIMUM_CREDITS'))
        ->orderBy('CONCENTRATION_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:Degree:concentrations.html.twig', array('concentrations' => $concentrations));
  }
  
}