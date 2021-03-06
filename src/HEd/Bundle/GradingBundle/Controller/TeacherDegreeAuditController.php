<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class TeacherDegreeAuditController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('Teacher.HEd.Advisor.Student');
    
    $degree_audit = array();
    $degrees = '';
    $areas = '';
    
    $total_needed = 0;
    $total_completed = 0;
    $total_remaining = 0;
    
    if ($this->record->getSelectedRecordID()) {
      $degree_audit_service = $this->get('kula.HEd.grading.degreeaudit');
      $degree_audit = $degree_audit_service->getDegreeAuditForStudentStatus($this->record->getSelectedRecordID());
      $degrees = (count($degree_audit_service->getDegrees()) > 0) ? implode(', ', $degree_audit_service->getDegrees()) : '';
      $areas = (count($degree_audit_service->getAreas()) > 0) ? implode(', ', $degree_audit_service->getAreas()) : '';
      $total_needed = $degree_audit_service->getTotalDegreeNeeded();
      $total_completed = $degree_audit_service->getTotalDegreeCompleted();
      $total_remaining = $degree_audit_service->getTotalDegreeRemaining();
    }
    
    return $this->render('KulaHEdGradingBundle:TeacherDegreeAudit:index.html.twig', array('degree_audit' => $degree_audit, 'degrees' => $degrees, 'areas' => $areas, 'total_needed' => $total_needed, 'total_completed' => $total_completed, 'total_remaining' => $total_remaining));
  }
  
  
}