<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISGradingSetupMarkScaleController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    
    $mark_scales = array();
    
      // Get Mark Scales
      $mark_scales = $this->db()->db_select('STUD_MARK_SCALE')
        ->fields('STUD_MARK_SCALE', array('MARK_SCALE_NAME', 'MARK_SCALE_ID', 'INACTIVE_AFTER', 'AUDIT'))
        ->orderBy('MARK_SCALE_NAME', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:SISGradingSetupMarkScale:mark_scales.html.twig', array('mark_scales' => $mark_scales));
  }
  
  public function detailAction($mark_scale_id) {
    $this->authorize();
    $this->processForm();
    
    $mark_scale = array();
    
    // Get Mark Scales
    $mark_scale = $this->db()->db_select('STUD_MARK_SCALE')
      ->fields('STUD_MARK_SCALE', array('MARK_SCALE_NAME', 'MARK_SCALE_ID'))
      ->condition('MARK_SCALE_ID', $mark_scale_id)
      ->execute()->fetch();
    
    $marks = array();
    
      // Get Marks
      $marks = $this->db()->db_select('STUD_MARK_SCALE_MARKS')
        ->fields('STUD_MARK_SCALE_MARKS', array('MARK_SCALE_MARK_ID', 'SORT', 'MARK', 'GETS_CREDIT', 'GPA_VALUE', 'INACTIVE_AFTER', 'ALLOW_TEACHER', 'ALLOW_COMMENTS', 'REQUIRE_COMMENTS'))
        ->condition('MARK_SCALE_ID', $mark_scale_id)
        ->orderBy('SORT', 'ASC')
        ->execute()->fetchAll();
    
    return $this->render('KulaHEdGradingBundle:SISGradingSetupMarkScale:mark_scale_detail.html.twig', array('marks' => $marks, 'mark_scale' => $mark_scale));
  }
}