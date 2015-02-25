<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class SISPFAIDSController extends Controller {
  
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
    
    return $this->render('KulaHEdFinancialAidBundle:SISPFAIDS:admin.html.twig');
  }
}