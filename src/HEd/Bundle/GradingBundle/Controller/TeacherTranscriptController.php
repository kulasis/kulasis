<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class TeacherTranscriptController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('Teacher.HEd.Advisor.Student');
    
    $transcript_data = array();
    $transcript_schedule = array();
    $transcript_degree = array();
    
    if ($this->record->getSelectedRecordID()) {
      $transcript_service = $this->get('kula.HEd.grading.transcript');
      $transcript_service->loadTranscriptForStudent($this->record->getSelectedRecord()['STUDENT_ID']);
      $transcript_data = $transcript_service->getTranscriptData();
      $transcript_schedule = $transcript_service->getCurrentScheduleData();
      $transcript_degree = $transcript_service->getDegreeData();
    }
    return $this->render('KulaHEdGradingBundle:TeacherTranscript:transcript.html.twig', array('data' => $transcript_data, 'schedule' => $transcript_schedule, 'degrees' => $transcript_degree));
  
  }
  
}