<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISHoldsController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->setRecordType('STUDENT');
		
		if ($this->request->request->get('void')) {
			$items_to_void = $this->request->request->get('void');
			
			$this->db()->beginTransaction();
			
			foreach($items_to_void as $table => $table_row) {
				foreach($table_row as $row_id => $row) {
					$void_poster = new \Kula\Component\Database\Poster(null, array('STUD_STUDENT_HOLDS' => array($row_id => array('VOIDED' => array('checkbox' => 'Y', 'checkbox_hidden' => ''), 'VOIDED_TIMESTAMP' => date('Y-m-d H:i:s'), 'VOIDED_USERSTAMP' => $this->session->get('user_id')))));
					unset($data);
				}
			}
			
			$this->db()->commit();
			
		} else {
			$this->processForm();
		}
		
		$holds = array();
		
		if ($this->record->getSelectedRecordID()) {
			$holds = $this->db()->select('STUD_STUDENT_HOLDS', 'stuholds')
				->fields('stuholds', array('STUDENT_HOLD_ID', 'HOLD_ID', 'HOLD_DATE', 'COMMENTS', 'VOIDED', 'VOIDED_REASON', 'VOIDED_TIMESTAMP'))
				->join('STUD_HOLD', 'hold', array('HOLD_NAME'), 'stuholds.HOLD_ID = hold.HOLD_ID')
				->left_join('CORE_USER', 'user', array('USERNAME'), 'user.USER_ID = stuholds.VOIDED_USERSTAMP')
				->predicate('stuholds.STUDENT_ID', $this->record->getSelectedRecordID())
				->order_by('HOLD_DATE', 'DESC', 'stuholds')
			  ->execute()->fetchAll();
		}
		
		return $this->render('KulaHEdStudentBundle:Holds:index.html.twig', array('holds' => $holds));
	}
}