<?php

namespace Kula\Bundle\HEd\OfferingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class RoomsController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('SCHOOL_TERM', null, 
		array('CORE_ORGANIZATION_TERMS' =>
			array('ORGANIZATION_ID' => $this->focus->getSchoolIDs(),
						'TERM_ID' => $this->focus->getTermID()
					 )
		     )
		);
		$rooms = array();
		if ($this->record->getSelectedRecordID()) {
			
			// Get Rooms
			$rooms = $this->db()->select('STUD_ROOM')
				->predicate('ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
				->order_by('ROOM_NUMBER', 'ASC')
				->execute()->fetchAll();
			
		}
		
		return $this->render('KulaHEdOfferingBundle:Rooms:index.html.twig', array('rooms' => $rooms));
		
	}
	
	public function chooserAction() {
		$this->authorize();
		$data = \Kula\Bundle\HEd\OfferingBundle\Chooser\RoomsChooser::createChooserMenu($this->request->query->get('q'));
		return $this->JSONResponse($data);
	}
	
}