<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreRoomsController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
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
    
    return $this->render('KulaHEdSchedulingBundle:CoreRooms:index.html.twig', array('rooms' => $rooms));
    
  }
  
  public function chooserAction() {
    $this->authorize();
    $data = $this->chooser('HEd.Room')->createChooserMenu($this->request->query->get('q'));
    return $this->JSONResponse($data);
  }
  
}