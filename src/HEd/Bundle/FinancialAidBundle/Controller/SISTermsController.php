<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Controller;

use Kula\Core\Bundle\KulaCoreFrameworkBundle\Controller\Controller;

class SISPackageController extends Controller {
  
  public function indexAction() {
    $this->authorize();
    $this->setRecordType('HEd.Student');
    
    $award_terms = array();
    
    $fin_aid_year = $this->db()->db_select('CORE_TERM', 'term')
      ->fields('term', array('FINANCIAL_AID_YEAR'))
      ->condition('TERM_ID', $this->focus->getTermID())
      ->execute()->fetch();
    
    $post_info_add = $this->request->request->get('add');
    if ($post_info_add) {
    
      unset($post_info_add['FAID_STUDENT_AWARD_YEAR_TERMS']['new_num']);

      if (count($post_info_add['FAID_STUDENT_AWARD_YEAR_TERMS']) == 0) {
        unset($post_info_add);
      }
    
    }
    if (isset($post_info_add)) {
      
      // Check for year
      $award_year_info = $this->db()->db_select('FAID_STUDENT_AWARD_YEAR', 'awardyr')
        ->fields('awardyr', array('AWARD_YEAR_ID'))
        ->condition('AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
        ->condition('STUDENT_ID', $this->record->getSelectedRecordID())
        ->execute()->fetch();
      
      if ($award_year_info['AWARD_YEAR_ID'] == '') {
        // Create year record
        $poster_factory = new \Kula\Component\Database\Poster(
          array('FAID_STUDENT_AWARD_YEAR' => 
            array('new' =>
              array('STUDENT_ID' => $this->record->getSelectedRecordID(),
                    'AWARD_YEAR' => $fin_aid_year['FINANCIAL_AID_YEAR']))));
        $award_year_id = $poster_factory->getResultForTable('insert', 'FAID_STUDENT_AWARD_YEAR')['new'];
        
      } else {
        $award_year_id = $award_year_info['AWARD_YEAR_ID'];
      }
      
      $post_info_add = $this->request->request->get('add');
      
      foreach($post_info_add as $table => $row_info) {
        foreach($row_info as $row_id => $row) {
          $post_info_add[$table][$row_id]['AWARD_YEAR_ID'] = $award_year_id;
        }
      }
      $poster_factory = new \Kula\Component\Database\Poster($post_info_add);
    } else {
      $this->processForm();
    }
    
    if ($this->record->getSelectedRecordID()) {

      $award_terms = $this->db()->db_select('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm')
        ->fields('faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE', 'ORGANIZATION_TERM_ID', 'SEQUENCE'))
        ->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
        ->fields('faidstuawardyr', array('AWARD_YEAR'))
        ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = faidstuawrdyrtrm.ORGANIZATION_TERM_ID')
        ->join('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = orgterms.ORGANIZATION_ID')
        ->fields('org', array('ORGANIZATION_ABBREVIATION'))
        ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
        ->fields('term', array('TERM_ABBREVIATION'))
        ->condition('faidstuawardyr.STUDENT_ID', $this->record->getSelectedRecordID())
        ->condition('faidstuawardyr.AWARD_YEAR', $fin_aid_year['FINANCIAL_AID_YEAR'])
        ->orderBy('term.START_DATE', 'ASC');
      $award_terms = $award_terms->execute()->fetchAll();
      
    }
    
    return $this->render('KulaHEdFinancialAidBundle:Terms:terms.html.twig', array('fin_aid_year' => $fin_aid_year['FINANCIAL_AID_YEAR'], 'award_terms' => $award_terms));
  }
}