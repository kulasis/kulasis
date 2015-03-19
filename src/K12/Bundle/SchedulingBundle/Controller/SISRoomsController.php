<?php

namespace Kula\K12\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISRoomsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.Organization.School.Term', null, 
    array('CORE_ORGANIZATION_TERMS' =>
      array('ORGANIZATION_ID' => $this->focus->getSchoolIDs(),
            'TERM_ID' => $this->focus->getTermID()
           )
         )
    );
    $rooms = array();
    if ($this->record->getSelectedRecordID()) {
      
      // Get Rooms
      $rooms = $this->db()->db_select('STUD_ROOM')
        ->fields('STUD_ROOM')
        ->condition('ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
        ->orderBy('ROOM_NUMBER', 'ASC')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaK12SchedulingBundle:SISRooms:index.html.twig', array('rooms' => $rooms));
    
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('K12.Room')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}