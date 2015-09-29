<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class TeacherDegreeAuditController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('Teacher.HEd.Advisor.Student');
    
    $degree_audit = array();
    
    if ($this->record->getSelectedRecordID()) {
    
      $degree_audit_service = $this->get('kula.HEd.grading.degreeaudit');
      $degree_audit = $degree_audit_service->getDegreeAuditForStudentStatus($this->record->getSelectedRecordID());
    
    }
    
    return $this->render('KulaHEdGradingBundle:TeacherDegreeAudit:index.html.twig', array('degree_audit' => $degree_audit));
  }
  
  
}