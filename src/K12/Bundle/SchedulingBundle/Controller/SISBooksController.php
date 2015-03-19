<?php

namespace Kula\K12\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISBooksController extends Controller {
  
  public function booksAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('SIS.K12.Course');
    $books = array();
    if ($this->record->getSelectedRecordID()) {
      
      // Get Rooms
      $books = $this->db()->db_select('STUD_COURSE_BOOK', 'coursebook')
        ->fields('coursebook', array('COURSE_BOOK_ID', 'ISBN_NUMBER', 'PUBLISHER', 'TITLE', 'AUTHOR', 'COST'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = coursebook.ORGANIZATION_TERM_ID')
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_NAME'))
        ->condition('COURSE_ID', $this->record->getSelectedRecordID())
        ->condition('coursebook.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermID())
        ->orderBy('term.START_DATE', 'DESC')
        ->orderBy('TITLE', 'ASC')
        ->execute()->fetchAll();
    }
    
    // Get organization term and name for adding
    $org_term = $this->db()->db_select('CORE_ORGANIZATION_TERMS', 'orgterms')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'orgterms.TERM_ID = term.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->condition('ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermID())
      ->execute()->fetch();
    
    return $this->render('KulaK12SchedulingBundle:SISBooks:books.html.twig', array('books' => $books, 'organization_term_id' => $this->focus->getOrganizationTermID(), 'organization_term_id_display' => $org_term['TERM_ABBREVIATION'] . ' / ' . $org_term['ORGANIZATION_NAME']));
  }
  
}