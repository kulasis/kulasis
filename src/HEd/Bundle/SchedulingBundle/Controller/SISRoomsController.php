<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISRoomsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.Term', null, 
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
        ->condition('ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
        ->orderBy('ROOM_NUMBER', 'ASC')
        ->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdOfferingBundle:SISRooms:index.html.twig', array('rooms' => $rooms));
    
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('SIS.HEd.Room')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}