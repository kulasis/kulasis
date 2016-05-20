<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

use Kula\Core\Component\DB\PosterException;
use Kula\Core\Component\Database\IntegrityConstraintViolationException;
use Kula\Core\Component\Database\DatabaseExceptionWrapper;

class CorePFAIDSController extends Controller {
  
  public function adminAction() {
    $this->authorize();
    
    if ($pf = $this->request->request->get('pf')) {
      // Load Service
      $pfaidsService = $this->get('kula.HEd.FAID.PFAIDS');
      
      // Delete records in external table
      if (isset($pf['action']) AND $pf['action'] == 'reset' AND isset($pf['Core.Integration.Database']['Core.Integration.Database.DatabaseList'])) {
        $affectedRows = $pfaidsService->pfaids_deleteRecords($pf['Core.Integration.Database']['Core.Integration.Database.DatabaseList'], isset($pf['award_year_token']) ? $pf['award_year_token'] : null);
        if ($affectedRows > 0) {
          $this->addFlash('success', 'Deleted '.$affectedRows.' row(s).');
        } else {
          $this->addFlash('info', 'No rows to delete.');
        }
      }
      
      // Add records
       if (isset($pf['action']) AND $pf['action'] == 'load' AND isset($pf['Core.Integration.Database']['Core.Integration.Database.DatabaseList']) AND isset($pf['award_year_token'])) {
         $affectedRows = $pfaidsService->pfaids_addRecords($pf['Core.Integration.Database']['Core.Integration.Database.DatabaseList'], $pf['award_year_token']);
         if ($affectedRows['insert_count'] > 0 OR $affectedRows['update_count'] > 0) {
           $this->addFlash('success', 'Inserted '.$affectedRows['insert_count'].' row(s). Updated '.$affectedRows['update_count'].' row(s).');
         } else {
           $this->addFlash('info', 'No rows inserted or updated.');
         }
       }
      
    }
    
    if ($this->request->request->get('load_all_students') == 'Y') {
      
      $fin_aid_year = $this->db()->db_select('CORE_TERM', 'term')
        ->fields('term', array('FINANCIAL_AID_YEAR'))
        ->condition('TERM_ID', $this->focus->getTermID())
        ->execute()->fetch();
      
      $pfaidsService = $this->get('kula.HEd.FAID.PFAIDS');
      $pfaidsService->synchronizeStudentAwardInfo($fin_aid_year['FINANCIAL_AID_YEAR']);
      
      $this->addFlash('success', 'Loaded PowerFAIDS data to Kula.');
    }
    
    return $this->render('KulaHEdFinancialAidBundle:CorePFAIDS:admin.html.twig');
  }
  
  public function configAction() {
    $this->authorize();
    $this->processForm();
    
    // Get term fin aid year
    $fin_aid_year = $this->db()->db_select('CORE_TERM', 'term')
      ->fields('term', array('FINANCIAL_AID_YEAR'))
      ->condition('TERM_ID', $this->focus->getTermID())
      ->execute()->fetch();
    
    $pfaidsService = $this->get('kula.HEd.FAID.PFAIDS');
    $pfaidsService->synchronizePOEs($fin_aid_year['FINANCIAL_AID_YEAR']);

    $poes_pfaids = $pfaidsService->getPOEs();
    $data = array();
    
    $poes = $this->db()->db_select('FAID_PFAID_POE', 'poe')
      ->fields('poe')->execute();
    $i = 0;
    while ($poe = $poes->fetch()) {
      if ($fin_aid_year['FINANCIAL_AID_YEAR'] == '' OR 
      ($poes_pfaids[$poe['poe_token']]['award_year_token'] == $fin_aid_year['FINANCIAL_AID_YEAR'])) {
        $data[$i] = $poes_pfaids[$poe['poe_token']];
        $data[$i]['poe_token'] = $poe['poe_token'];
        $data[$i]['TERM_ID'] = $poe['TERM_ID'];
        $data[$i]['LEVEL'] = $poe['LEVEL'];
        $data[$i]['CUSTOM_TERM_CREDIT_TOTAL'] = $poe['CUSTOM_TERM_CREDIT_TOTAL'];
        $i++;
      }
    }

    return $this->render('KulaHEdFinancialAidBundle:CorePFAIDS:config.html.twig', array('poes' => $data));
  }
  
  public function syncFAAction() {
    $this->authorize();
    $this->formNewWindow();
    $this->formAction('Core_HEd_FinancialAid_StudentFinancialAid_SyncFAPFAIDS_calculate');
    
    return $this->render('KulaHEdFinancialAidBundle:CorePFAIDS:action_syncFA.html.twig');
  }
  
  public function performSyncFAAction() {
    $this->authorize();
    
	$pfaidsService = $this->get('kula.HEd.FAID.PFAIDS');
	
	$response = '';
	
	$fin_aid_year = $this->db()->db_select('CORE_TERM', 'term')
      ->fields('term', array('FINANCIAL_AID_YEAR'))
      ->condition('TERM_ID', $this->focus->getTermID())
      ->execute()->fetch();
	
	// Loop through students in term
	$students = $this->db()->db_select('STUD_STUDENT_STATUS', 'stustatus')
		->fields('stustatus', array('STUDENT_STATUS_ID', 'PFAIDS_EXEMPT'))
		->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = stustatus.STUDENT_ID')
		->fields('cons', array('PERMANENT_NUMBER'))
		->condition('stustatus.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
		->execute();
	while ($student = $students->fetch()) {

      if ($student['PFAIDS_EXEMPT'] == '0') {
		
		try {
			$pfaidsService->synchronizeStudentAwardInfo($fin_aid_year['FINANCIAL_AID_YEAR'], $student['PERMANENT_NUMBER']);
			$response .= $student['PERMANENT_NUMBER'].": Processed.\n";
		} catch(\PDOException $exception) {
			$response .= $student['PERMANENT_NUMBER'].': '.$exception->getMessage()."\n";
		} catch(IntegrityConstraintViolationException $e) {
			$response .= $student['PERMANENT_NUMBER'].': '.$exception->getMessage()."\n";
		} catch(DatabaseExceptionWrapper $e) {
			$response .= $student['PERMANENT_NUMBER'].': '.$exception->getMessage()."\n";
		} 
	  
	  }
		
	}
	
    return $this->textResponse($response);
  }
}