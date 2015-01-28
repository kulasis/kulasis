<?php

namespace Kula\Bundle\HEd\OfferingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class BooksController extends Controller {
	
	public function booksAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('COURSE');
		$books = array();
		if ($this->record->getSelectedRecordID()) {
			
			// Get Rooms
			$books = $this->db()->select('STUD_COURSE_BOOK', 'coursebook')
				->fields('coursebook', array('COURSE_BOOK_ID', 'ISBN_NUMBER', 'PUBLISHER', 'TITLE', 'AUTHOR', 'COST'))
				->join('CORE_ORGANIZATION_TERMS', 'orgterms', null, 'orgterms.ORGANIZATION_TERM_ID = coursebook.ORGANIZATION_TERM_ID')
				->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'term.TERM_ID = orgterms.TERM_ID')
				->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
				->predicate('COURSE_ID', $this->record->getSelectedRecordID())
				->predicate('coursebook.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs()[0])
				->order_by('START_DATE', 'DESC', 'term')
				->order_by('TITLE', 'ASC')
				->execute()->fetchAll();
		}
		
		// Get organization term and name for adding
		$org_term = $this->db()->select('CORE_ORGANIZATION_TERMS', 'orgterms')
			->join('CORE_ORGANIZATION', 'org', array('ORGANIZATION_NAME'), 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
			->join('CORE_TERM', 'term', array('TERM_ABBREVIATION'), 'orgterms.TERM_ID = term.TERM_ID')
			->predicate('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs()[0])
			->execute()->fetch();
		
		return $this->render('KulaHEdOfferingBundle:Books:books.html.twig', array('books' => $books, 'organization_term_id' => $this->focus->getOrganizationTermIDs()[0], 'organization_term_id_display' => $org_term['TERM_ABBREVIATION'] . ' / ' . $org_term['ORGANIZATION_NAME']));
	}
	
}