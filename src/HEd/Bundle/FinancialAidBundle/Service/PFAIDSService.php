<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Service;

class PFAIDSService {
  
  protected $db;
  
  protected $poster_factory;
  
  protected $record;

  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory,
                              $record = null,
                              $session = null,
                              $focus = null,
                              $ssn_key = null) {
    $this->db = $db;
    $this->record = $record;
    $this->posterFactory = $poster_factory;
    $this->session = $session;
    $this->focus = $focus;
    $this->ssn_key = $ssn_key;
  }
  
  private function pfaids_connect($application, $id = null) {
    
    // Get database connection parameters
    $intgDB = $this->db->db_select('CORE_INTG_DATABASE')
      ->fields('CORE_INTG_DATABASE')
      ->condition('APPLICATION', $application);
    if ($id) {
      $intgDB = $intgDB->condition('INTG_DATABASE_ID', $id);
    }
      $intgDB = $intgDB->execute()->fetch();
    
    $connection = mssql_connect($intgDB['HOST'], $intgDB['USERNAME'], $intgDB['PASSWORD']) or die("Couldn't connect to SQL Server on ".$intgDB['HOST']);
    mssql_select_db($intgDB['DATABASE_NAME'], $connection) or die("Couldn't open database on SQL Server for ".$intgDB['HOST']);
    
    return $connection;
  }
  
  public function pfaids_deleteRecords($intgDBID, $awardYearToken = null, $ssn = null) {
    
    $connection = $this->pfaids_connect('PFAIDEU', $intgDBID);
    
    $query = "DELETE FROM external_data";
    
    if ($awardYearToken)
      $query .= " WHERE award_year_token = '".str_replace("'", "''", $awardYearToken)."'";
    if ($awardYearToken AND $ssn)
      $query .= " AND ssn = '".str_replace("'", "''", $ssn)."'";
    
    mssql_query($query, $connection);
    return mssql_rows_affected($connection);
  }
  
  public function pfaids_addRecords($intgDBID, $awardYearToken, $id = null) {
    
    $connection = $this->pfaids_connect('PFAIDEU', $intgDBID);
    
    $insert_count = 0;
    $update_count = 0;
    
    $students_result = $this->db->db_select('CONS_CONSTITUENT', 'cons')
      ->fields('cons')
      ->expression("AES_DECRYPT(cons.SOCIAL_SECURITY_NUMBER, '".$this->ssn_key."')", 'encrypted_ssn')
      ->join('STUD_STUDENT', 'stu', 'cons.CONSTITUENT_ID = stu.STUDENT_ID')
      ->fields('stu')
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = stu.STUDENT_ID')
      ->fields('stustatus')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterm', 'orgterm.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterm.TERM_ID')
      ->fields('term', array('FINANCIAL_AID_YEAR'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY_TERMS', 'chterms', 'chterms.STUDENT_STATUS_ID = stustatus.STUDENT_STATUS_ID')
      ->fields('chterms', array('TOTAL_GPA', 'TOTAL_CREDITS_EARNED', 'TERM_CREDITS_ATTEMPTED', 'TERM_CREDITS_EARNED', 'TRNS_CREDITS_EARNED'))
      ->leftJoin('CONS_PHONE', 'phone', 'phone.PHONE_NUMBER_ID = cons.PRIMARY_PHONE_ID')
      ->fields('phone', array('PHONE_NUMBER'))
      ->leftJoin('CONS_EMAIL_ADDRESS', 'email', 'email.EMAIL_ADDRESS_ID = cons.PRIMARY_EMAIL_ID')
      ->fields('email', array('EMAIL_ADDRESS'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'race_values', "race_values.CODE = cons.RACE AND race_values.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'Constituent.Race')")
      ->fields('race_values', array('PF_CODE' => 'race_PF_CODE'))
      ->leftJoin('CORE_TERM', 'enterterm', 'enterterm.TERM_ID = stustatus.ENTER_TERM_ID')
      ->fields('enterterm', array('TERM_ABBREVIATION'))
      ->condition('stustatus.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
      ->execute();
    while ($stu_row = $students_result->fetch()) {
  
      $cleaned_ssn = str_ireplace('-', '', $stu_row['encrypted_ssn']);
  
      if (strlen($cleaned_ssn) == 9) {
    
        $pf_data = array(
          '[ssn]' => "'".$cleaned_ssn."'",
          '[award_year_token]' => $awardYearToken,
          '[ALTERnate_id]' => "'".$stu_row['PERMANENT_NUMBER']."'",
          '[last_name]' => "'".str_replace("'", "''", $stu_row['LAST_NAME'])."'",
          '[first_name]' => "'".str_replace("'", "''", $stu_row['FIRST_NAME'])."'",
          '[middle_init]' => strlen(substr($stu_row['MIDDLE_NAME'], 0, 1)) > 0 ? "'".substr($stu_row['MIDDLE_NAME'], 0, 1)."'" : 'null', 
          '[name_mid]' => $stu_row['MIDDLE_NAME'] ? "'".str_replace("'", "''", $stu_row['MIDDLE_NAME'])."'" : 'null', 
          '[birth_date]' => $stu_row['BIRTH_DATE'] ? "'".date('mdY', strtotime($stu_row['BIRTH_DATE']))."'" : 'null',
        );
        
        // Get address
        if ($stu_row['HOME_ADDRESS_ID']) {
          $address_id = $stu_row['HOME_ADDRESS_ID'];
        } elseif ($stu_row['MAILING_ADDRESS_ID']) {
          $address_id = $stu_row['MAILING_ADDRESS_ID'];
        } elseif ($stu_row['RESIDENCE_ADDRESS_ID']) {
          $address_id = $stu_row['RESIDENCE_ADDRESS_ID'];
        }
        
        $primary_address = $this->db->db_select('CONS_ADDRESS', 'addr')
          ->fields('addr')
          ->condition('ADDRESS_ID', $address_id)
          ->execute()->fetch();
        
        $pf_data['primary_street1'] = "'".str_replace("'", "''", $primary_address['THOROUGHFARE'])."'";
        $pf_data['primary_city'] = "'".str_replace("'", "''", $primary_address['LOCALITY'])."'";
        $pf_data['primary_state'] = "'".str_replace("'", "''", $primary_address['ADMINISTRATIVE_AREA'])."'";
        $pf_data['primary_zip'] = "'".str_replace("'", "''", str_replace(str_split(' -'), '', $primary_address['POSTAL_CODE']))."'";
        $pf_data['primary_telephone'] = "'".str_replace("'", "''", str_replace(str_split('()- '), '', $stu_row['PHONE_NUMBER']))."'"; 
        $pf_data['email_address'] = "'".str_replace("'", "''", $stu_row['EMAIL_ADDRESS'])."'";
        
        $pf_data['legal_residence'] = "'".str_replace("'", "''", $primary_address['ADMINISTRATIVE_AREA'])."'";
        
        // Get degree information
        $degree_info = $this->db->db_select('STUD_STUDENT_DEGREES', 'studegree')
          ->fields('studegree')
          ->join('STUD_DEGREE', 'degree', 'degree.DEGREE_ID = studegree.DEGREE_ID')
          ->fields('degree', array('PF_CODE' => 'degree_PF_CODE'))
          ->leftJoin('STUD_STUDENT_DEGREES_CONCENTRATIONS', 'studegreecon', 'studegreecon.STUDENT_DEGREE_ID = studegree.STUDENT_DEGREE_ID')
          ->leftJoin('STUD_DEGREE_CONCENTRATION', 'concentration', 'concentration.CONCENTRATION_ID = studegreecon.CONCENTRATION_ID')
          ->fields('concentration', array('PF_CODE' => 'concentration_PF_CODE'))
          ->condition('studegree.STUDENT_DEGREE_ID', $stu_row['SEEKING_DEGREE_1_ID'])
          ->execute()->fetch();
        
        $pf_data['us_citizen'] = ($stu_row['CITIZENSHIP_COUNTRY'] == 'US') ? '1' : 'null';
        $pf_data['gender'] = $stu_row['GENDER'] != '' ? "'".$stu_row['GENDER']."'" : 'null';
        $pf_data['veteran_status'] = ($stu_row['VETERAN'] == '1') ? "'Y'" : 'null';
        $pf_data['race'] = ($stu_row['race_PF_CODE'] != '') ? "'".$stu_row['race_PF_CODE']."'" : 'null';
        $pf_data['hispanic'] = ($stu_row['race_PF_CODE'] == '1') ? "'2'" : 'null';
        $pf_data['citizen_country'] = $stu_row['CITIZENSHIP_COUNTRY'] ? "'".$stu_row['CITIZENSHIP_COUNTRY']."'" : 'null';
        
        $pf_data['grad_date'] = (isset($degree_info['EXPECTED_GRADUATION_DATE']) AND $degree_info['EXPECTED_GRADUATION_DATE']) ? "'".date('mdY', strtotime($degree_info['EXPECTED_GRADUATION_DATE']))."'" : 'null';
        $pf_data['admission_status'] = "'A'";
        $pf_data['transfer_flag'] = ($stu_row['ENTER_CODE'] == '04') ? "'Y'" : 'null';
        
        // Get earliest enter date for financial aid year
        $enterDate = $this->db->db_select('STUD_STUDENT_STATUS', 'stustatus')
          ->fields('stustatus', array('ENTER_DATE'))
          ->condition('stustatus.STUDENT_ID', $stu_row['STUDENT_ID'])
          ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
          ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
          ->condition('term.FINANCIAL_AID_YEAR', $stu_row['FINANCIAL_AID_YEAR'])
          ->orderBy('ENTER_DATE', 'ASC')
          ->execute()->fetch();
        $pf_data['date_enrolled'] = ($enterDate['ENTER_DATE'] != '') ? "'".date('mdY', strtotime($enterDate['ENTER_DATE']))."'" : 'null';
        
        // Get latest enter date for financial aid year
        $enterDate = $this->db->db_select('STUD_STUDENT_STATUS', 'stustatus')
          ->fields('stustatus', array('LEAVE_DATE', 'STATUS'))
          ->condition('stustatus.STUDENT_ID', $stu_row['STUDENT_ID'])
          ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
          ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
          ->condition('term.FINANCIAL_AID_YEAR', $stu_row['FINANCIAL_AID_YEAR'])
          ->orderBy('LEAVE_DATE', 'DESC')
          ->execute()->fetch();
        $pf_data['date_withdrawn'] = ($enterDate['LEAVE_DATE'] != '' AND $enterDate['STATUS'] == 'I') ? "'".date('mdY', strtotime($enterDate['LEAVE_DATE']))."'" : 'null';
        
        if ($stu_row['COHORT']) {
          $pf_data['string1_field_id'] = "'2807'";
          $pf_data['string1_value'] = "'".$stu_row['COHORT']."'";
        }
        
        if ($stu_row['LEVEL']) {
          $pf_data['string2_field_id'] = "'2802'";
          $pf_data['string2_value'] = "'".$stu_row['LEVEL']."'";
        }
        
        if ($stu_row['ENTER_TERM_ID']) {
          $pf_data['string3_field_id'] = "'2801'";
          $pf_data['string3_value'] = "'".str_replace('-', '', $stu_row['TERM_ABBREVIATION'])."'";
        }
        
        if ($stu_row['ENTER_CODE']) {
          $pf_data['string4_field_id'] = "'2806'";
          $pf_data['string4_value'] = "'".$stu_row['ENTER_CODE']."'";
        }
        
        if ($stu_row['LEAVE_CODE']) {
          $pf_data['string5_field_id'] = "'2805'";
          $pf_data['string5_value'] = "'".$stu_row['LEAVE_CODE']."'";
        }
        
        $pf_data['academic_division'] = ($degree_info['degree_PF_CODE'] != '') ? "'".$degree_info['degree_PF_CODE']."'" : 'null';
        $pf_data['major_code'] = ($degree_info['concentration_PF_CODE'] != '') ? "'".$degree_info['concentration_PF_CODE']."'" : 'null';
        $pf_data['college_gpa'] = ($stu_row['TOTAL_GPA'] != '') ? "'".substr(str_replace('.', '', $stu_row['TOTAL_GPA']), 0, 3)."'" : 'null';
        
        // If term credits earned greater than 0, then term ended
        if ($stu_row['TERM_CREDITS_EARNED'] > 0) {
          $pf_data['decimal1_field_id'] = "'7752'";
          $pf_data['decimal1_value'] = sprintf('%0.2f', round($stu_row['TOTAL_CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP));
        } else {
          // need to add total credits and term credits attempted
          $pf_data['decimal1_field_id'] = "'7752'";
          $pf_data['decimal1_value'] = sprintf('%0.2f', round($stu_row['TOTAL_CREDITS_EARNED'] + $stu_row['TERM_CREDITS_ATTEMPTED'], 2, PHP_ROUND_HALF_UP));
        }
        
        // If transfer credits
        if ($stu_row['TRNS_CREDITS_EARNED'] > 0) {
          $pf_data['decimal2_field_id'] = "'7753'";
          $pf_data['decimal2_value'] = sprintf('%0.2f', round($stu_row['TRNS_CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP));
        }
        
        // check if already exists
        $already_exists = mssql_fetch_array(mssql_query("SELECT ssn, award_year_token FROM external_data WHERE ssn = '".$cleaned_ssn."' AND award_year_token = '".$awardYearToken."'"));
        
        if ($already_exists) {
          $inner_sql = array();
          
          foreach($pf_data as $key => $value) {
            $inner_sql[] = $key.' = '.$value;
          }
          
          $sql = "UPDATE external_data SET ".implode(', ', $inner_sql)." WHERE [ssn] = '".$cleaned_ssn."' AND [award_year_token] = '".$awardYearToken."'";
          $update_count++;
        } else {
          $sql = "INSERT INTO external_data (".implode(', ', array_keys($pf_data)).") VALUES (".implode(", ", array_values($pf_data)).")";
          $insert_count++;
        }
        //echo $sql.'<br />';
        mssql_query($sql);
      }
      
      unset($cleaned_ssn, $pf_data);
  
    }
    
    $results['update_count'] = $update_count;
    $results['insert_count'] = $insert_count;
    
    return $results;
  }

  public function synchronizePOEs($faid_award_year = null) {
    
     $connection = $this->pfaids_connect('PFAIDR');
     
     $query = "SELECT poe_token FROM poe";
     if (isset($faid_award_year)) 
       $query .= " WHERE award_year_token = '".$faid_award_year."'";
     $poes_result = mssql_query($query);
     while ($poes_row = mssql_fetch_array($poes_result)) {
       
       $kula_poe = $this->db->db_select('FAID_PFAID_POE', 'pfaid_poe')
         ->fields('pfaid_poe')->condition('poe_token', $poes_row['poe_token'])
         ->execute()->fetch();
       if ($kula_poe['poe_token'] == '') {
         
         $this->posterFactory->newPoster()->noLog()->add('HEd.FAID.PFAID.POE', 'new', array(
           'HEd.FAID.PFAID.POE.POEToken' => $poes_row['poe_token']
         ))->process()->getResult();
         
       }
       
     }
    
  }
  
  public function getPOEs($faid_award_year = null) {
    $connection = $this->pfaids_connect('PFAIDR');
    
    $poe = array();
    
    $query = "SELECT * FROM poe";
    if (isset($faid_award_year)) 
      $query .= " WHERE award_year_token = '".$faid_award_year."'";
    $poes_result = mssql_query($query);
    while ($poes_row = mssql_fetch_array($poes_result)) {
      $poe[$poes_row['poe_token']] = $poes_row;
    }
    
    return $poe;
  }
  
  public function synchronizeStudentAwardInfo($faid_award_year = null, $permanent_number = null) {
    $connection = $this->pfaids_connect('PFAIDR');
    
    if ($connection) {
    
    $kula_awards = array();

    $pf_stu_award_query = "SELECT say_fm_stu.stu_award_year_token, stu_award_year.award_year_token, primary_efc, secondary_efc, say_fm_stu.fisap_income, student.alternate_id, tot_budget
      FROM stu_award_year 
      JOIN student ON student.student_token = stu_award_year.student_token
      LEFT JOIN say_fm_stu ON say_fm_stu.stu_award_year_token = stu_award_year.stu_award_year_token
      LEFT JOIN stu_ay_sum_data ON stu_ay_sum_data.stu_award_year_token = stu_award_year.stu_award_year_token
      WHERE 1 = 1 AND alternate_id != ''";
      if ($faid_award_year) {
        $pf_stu_award_query .= " AND award_year_token = '".$faid_award_year."'";
      }
      if ($permanent_number) {
        $pf_stu_award_query .= " AND alternate_id = '".$permanent_number."'";
      }

    $pf_stu_awards = mssql_query($pf_stu_award_query);
    while ($pf_stu_award = mssql_fetch_array($pf_stu_awards)) {

      // check if award year exists
      $award_year = $this->db->db_select('FAID_STUDENT_AWARD_YEAR', 'awardyear', array('nolog' => true))
        ->fields('awardyear', array('AWARD_YEAR_ID'))
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = awardyear.STUDENT_ID')
        ->condition('cons.PERMANENT_NUMBER', $pf_stu_award['alternate_id'])
        ->condition('awardyear.AWARD_YEAR', $pf_stu_award['award_year_token'])
        ->condition('awardyear.ORGANIZATION_ID', $this->focus->getOrganizationID())
        ->execute()->fetch();
      
      if ($award_year['AWARD_YEAR_ID']) {
        
        // Update data
        $this->posterFactory->newPoster()->noLog()->edit('HEd.FAID.Student.AwardYear', $award_year['AWARD_YEAR_ID'], array(
          'HEd.FAID.Student.AwardYear.PrimaryEFC' => $pf_stu_award['primary_efc'],
          'HEd.FAID.Student.AwardYear.SecondaryEFC' => $pf_stu_award['secondary_efc'],
          'HEd.FAID.Student.AwardYear.TotalIncome' => $pf_stu_award['fisap_income'],
          'HEd.FAID.Student.AwardYear.TotalCostOfAttendance' => $pf_stu_award['tot_budget'],
        ))->process()->getResult();
        
      } else {
        
        // Get student_id
        $student_id = $this->db->db_select('CONS_CONSTITUENT', 'constituent', array('nolog' => true))
          ->fields('constituent', array('CONSTITUENT_ID'))
          ->condition('constituent.PERMANENT_NUMBER', $pf_stu_award['alternate_id'])
          ->execute()->fetch();
        
        if ($student_id['CONSTITUENT_ID']) {
        
          // Insert Data
          $award_year['AWARD_YEAR_ID'] = $this->posterFactory->newPoster()->noLog()->add('HEd.FAID.Student.AwardYear', 'new', array(
            'HEd.FAID.Student.AwardYear.StudentID' => $student_id['CONSTITUENT_ID'],
            'HEd.FAID.Student.AwardYear.AwardYear' => $pf_stu_award['award_year_token'],
            'HEd.FAID.Student.AwardYear.OrganizationID' => $this->focus->getOrganizationID(),
            'HEd.FAID.Student.AwardYear.PrimaryEFC' => $pf_stu_award['primary_efc'],
            'HEd.FAID.Student.AwardYear.SecondaryEFC' => $pf_stu_award['secondary_efc'],
            'HEd.FAID.Student.AwardYear.TotalIncome' => $pf_stu_award['fisap_income'],
            'HEd.FAID.Student.AwardYear.TotalCostOfAttendance' => $pf_stu_award['tot_budget']
          ))->process()->getResult();
          
        } else {
          $award_year['AWARD_YEAR_ID'] = null;
        }
        
      } // end if on award_year_id
      
      // award year now exists
      if ($award_year['AWARD_YEAR_ID']) {
        
        // get POE terms and percentages
        $pf_stu_award_year_terms = mssql_query("SELECT DISTINCT poe.poe_token, poe_dcycle_seqn, att_pct_yr
          FROM stu_award_transactions
          JOIN poe ON poe.poe_token = stu_award_transactions.poe_token
          WHERE stu_award_transactions.stu_award_year_token = '".$pf_stu_award['stu_award_year_token']."' AND scheduled_amount > 0");
        while ($pf_stu_award_year_term = mssql_fetch_array($pf_stu_award_year_terms)) {
          
          // determine org term id
          $organization_term_id = $this->db->db_select('CORE_ORGANIZATION_TERMS', 'orgterms', array('nolog' => true))
            ->fields('orgterms', array('ORGANIZATION_TERM_ID'))
            ->join('FAID_PFAID_POE', 'poe', 'poe.TERM_ID = orgterms.TERM_ID')
            ->condition('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
            ->condition('poe.poe_token', $pf_stu_award_year_term['poe_token'])
            ->execute()->fetch();
          
          // check if term exists
          $award_year_term = $this->db->db_select('FAID_STUDENT_AWARD_YEAR_TERMS', 'awardterms', array('nolog' => true))
            ->fields('awardterms', array('AWARD_YEAR_TERM_ID'))
            ->condition('AWARD_YEAR_ID', $award_year['AWARD_YEAR_ID'])
            ->condition('ORGANIZATION_TERM_ID', $organization_term_id['ORGANIZATION_TERM_ID'])
            ->condition('SEQUENCE', $pf_stu_award_year_term['poe_dcycle_seqn'])
            ->execute()->fetch();
          
          // check if award term exists
          if (!$award_year_term['AWARD_YEAR_TERM_ID']) {
            
            $this->posterFactory->newPoster()->noLog()->add('HEd.FAID.Student.AwardYear.Term', 'new', array(
              'HEd.FAID.Student.AwardYear.Term.AwardYearID' => $award_year['AWARD_YEAR_ID'],
              'HEd.FAID.Student.AwardYear.Term.OrganizationTermID' => $organization_term_id['ORGANIZATION_TERM_ID'],
              'HEd.FAID.Student.AwardYear.Term.Sequence' => $pf_stu_award_year_term['poe_dcycle_seqn'],
              'HEd.FAID.Student.AwardYear.Term.Percentage' => $pf_stu_award_year_term['att_pct_yr']
            ))->process()->getResult();
            
          } // end if award term exists
          
        } // end while on student award terms
        
      } // end if on 2nd level check
      
      // get award year awards
      $pf_stu_award_year_awards = mssql_query("SELECT actual_amt, fund_ledger_number
        FROM stu_award
        JOIN funds ON funds.fund_token = stu_award.fund_ay_token
        WHERE stu_award.stu_award_year_token = '".$pf_stu_award['stu_award_year_token']."'");
      while ($pf_stu_award_year_award = mssql_fetch_array($pf_stu_award_year_awards)) {
        
        // check if award year award exists
        $award_year_award = $this->db->db_select('FAID_STUDENT_AWARD_YEAR_AWARDS', 'awardyearaward', array('nolog' => true))
          ->fields('awardyearaward', array('AWARD_YEAR_AWARD_ID'))
          ->join('FAID_AWARD_CODE', 'code', 'code.AWARD_CODE_ID = awardyearaward.AWARD_CODE_ID')
          ->condition('AWARD_YEAR_ID', $award_year['AWARD_YEAR_ID'])
          ->condition('AWARD_CODE', $pf_stu_award_year_award['fund_ledger_number'])
          ->execute()->fetch();
        
        if ($award_year_award['AWARD_YEAR_AWARD_ID']) {
          
          // update award
          $this->posterFactory->newPoster()->noLog()->edit('HEd.FAID.Student.AwardYear.Award', $award_year_award['AWARD_YEAR_AWARD_ID'], array(
            'HEd.FAID.Student.AwardYear.Award.GrossAmount' => $pf_stu_award_year_award['actual_amt']
          ))->process()->getResult();
          
        } else {
          
          if ($award_year['AWARD_YEAR_ID']) {
          
            // get award code ID
            $award_code_id = $this->db->db_select('FAID_AWARD_CODE', 'code', array('nolog' => true))
              ->fields('code', array('AWARD_CODE_ID'))
              ->condition('AWARD_CODE', $pf_stu_award_year_award['fund_ledger_number'])
              ->execute()->fetch();
            
            if ($award_code_id['AWARD_CODE_ID']) {
            
              // insert award
              $this->posterFactory->newPoster()->noLog()->add('HEd.FAID.Student.AwardYear.Award', 'new', array(
                'HEd.FAID.Student.AwardYear.Award.AwardYearID' => $award_year['AWARD_YEAR_ID'],
                'HEd.FAID.Student.AwardYear.Award.AwardCodeID' => $award_code_id['AWARD_CODE_ID'],
                'HEd.FAID.Student.AwardYear.Award.GrossAmount' => $pf_stu_award_year_award['actual_amt']
              ))->process()->getResult();
            
            }
          
          }
        
        } 
        
        
      } // end while on award year awards
      
      // get awards for terms
      $pf_stu_term_awards = mssql_query("SELECT poe.poe_token, poe_dcycle_seqn, fund_ledger_number, scheduled_date, cod_disbursement_date, scheduled_amount, gross_disbursement_amount, net_disbursement_amount, stu_award.status
        FROM stu_award_transactions
        JOIN poe ON poe.poe_token = stu_award_transactions.poe_token
        JOIN stu_award ON stu_award.stu_award_token = stu_award_transactions.stu_award_token
        JOIN funds ON funds.fund_token = stu_award.fund_ay_token
        WHERE stu_award_transactions.stu_award_year_token = '".$pf_stu_award['stu_award_year_token']."' AND scheduled_amount > 0 AND stu_award.status IN ('A','P')");
      while ($pf_stu_term_award = mssql_fetch_array($pf_stu_term_awards)) {
      
        // determine org term id
        $organization_term_id = $this->db->db_select('CORE_ORGANIZATION_TERMS', 'orgterms', array('nolog' => true))
          ->fields('orgterms', array('ORGANIZATION_TERM_ID'))
          ->join('FAID_PFAID_POE', 'poe', 'poe.TERM_ID = orgterms.TERM_ID')
          ->condition('orgterms.ORGANIZATION_ID', $this->focus->getOrganizationID())
          ->condition('poe.poe_token', $pf_stu_term_award['poe_token'])
          ->execute()->fetch();
        
        // get award code ID
        $award_code_id = $this->db->db_select('FAID_AWARD_CODE', 'code', array('nolog' => true))
          ->fields('code', array('AWARD_CODE_ID', 'SHOW_ON_STATEMENT'))
          ->condition('AWARD_CODE', $pf_stu_term_award['fund_ledger_number'])
          ->execute()->fetch();
        
        $award_term = $this->db->db_select('FAID_STUDENT_AWARD_YEAR_TERMS', 'awardterms', array('nolog' => true))
          ->fields('awardterms', array('AWARD_YEAR_TERM_ID'))
          ->condition('awardterms.AWARD_YEAR_ID', $award_year['AWARD_YEAR_ID'])
          ->condition('awardterms.ORGANIZATION_TERM_ID', $organization_term_id['ORGANIZATION_TERM_ID'])
          ->condition('awardterms.SEQUENCE', $pf_stu_term_award['poe_dcycle_seqn'])
          ->execute()->fetch();
        
        $award = $this->db->db_select('FAID_STUDENT_AWARDS', 'awards', array('nolog' => true))
          ->fields('awards', array('AWARD_ID'))
          ->condition('awards.AWARD_YEAR_TERM_ID', $award_term['AWARD_YEAR_TERM_ID'])
          ->condition('awards.AWARD_CODE_ID', $award_code_id['AWARD_CODE_ID'])
          ->execute()->fetch();
      
        // determine award status
        $award_status = null;
        if ($pf_stu_term_award['status'] == 'P')
          $award_status = 'PEND';
        if ($pf_stu_term_award['status'] == 'A')
          $award_status = 'APPR';
      
        // check if award exists
        if ($award['AWARD_ID']) {
          $kula_award[] = $award['AWARD_ID'];
          
          // update award
          $this->posterFactory->newPoster()->noLog()->edit('HEd.FAID.Student.Award', $award['AWARD_ID'], array(
            'HEd.FAID.Student.Award.AwardStatus' => ($award_status != '') ? $award_status : null,
            'HEd.FAID.Student.Award.DisbursementDate' => ($pf_stu_term_award['cod_disbursement_date'] != '') ? date('Y-m-d', strtotime($pf_stu_term_award['cod_disbursement_date'])) : null,
            'HEd.FAID.Student.Award.GrossAmount' => $pf_stu_term_award['scheduled_amount'],
            'HEd.FAID.Student.Award.NetAmount' => ($pf_stu_term_award['net_disbursement_amount'] > 0) ? $pf_stu_term_award['net_disbursement_amount'] : $pf_stu_term_award['scheduled_amount']
          ))->process()->getResult();
          
        } else {
          
          if ($award_term['AWARD_YEAR_TERM_ID'] AND $award_code_id['AWARD_CODE_ID']) {
          
            // insert data
            $kula_award[] = $this->posterFactory->newPoster()->noLog()->add('HEd.FAID.Student.Award', 'new', array(
              'HEd.FAID.Student.Award.AwardYearTermID' => $award_term['AWARD_YEAR_TERM_ID'],
              'HEd.FAID.Student.Award.AwardCodeID' => $award_code_id['AWARD_CODE_ID'],
              'HEd.FAID.Student.Award.AwardStatus' => ($award_status != '') ? $award_status : null,
              'HEd.FAID.Student.Award.DisbursementDate' => ($pf_stu_term_award['cod_disbursement_date'] != '') ? date('Y-m-d', strtotime($pf_stu_term_award['cod_disbursement_date'])) : null,
              'HEd.FAID.Student.Award.GrossAmount' => $pf_stu_term_award['scheduled_amount'],
              'HEd.FAID.Student.Award.NetAmount' => ($pf_stu_term_award['net_disbursement_amount'] > 0) ? $pf_stu_term_award['net_disbursement_amount'] : $pf_stu_term_award['scheduled_amount'],
              'HEd.FAID.Student.Award.OriginalAmount' => $pf_stu_term_award['scheduled_amount'],
              'HEd.FAID.Student.Award.ShowOnStatement' => $award_code_id['SHOW_ON_STATEMENT']
            ))->process()->getResult();

          }
          
        }
        
        unset($award_status);
      
      
      } // end while on student awards
      
      // Loop through awards not updated or added in kula
      $untouched_awards_result = $this->db->db_select('FAID_STUDENT_AWARDS', 'faidstuawrds')
        ->fields('faidstuawrds', array('AWARD_ID', 'AWARD_STATUS', 'DISBURSEMENT_DATE', 'GROSS_AMOUNT', 'NET_AMOUNT', 'ORIGINAL_AMOUNT', 'SHOW_ON_STATEMENT', 'AWARD_CODE_ID'))
        ->join('FAID_AWARD_CODE', 'awardcode', 'faidstuawrds.AWARD_CODE_ID = awardcode.AWARD_CODE_ID')
        ->fields('awardcode', array('AWARD_CODE', 'AWARD_DESCRIPTION'))
        ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'faidstuawrdyrtrm', 'faidstuawrds.AWARD_YEAR_TERM_ID = faidstuawrdyrtrm.AWARD_YEAR_TERM_ID')
        ->fields('faidstuawrdyrtrm', array('AWARD_YEAR_TERM_ID', 'PERCENTAGE', 'ORGANIZATION_TERM_ID', 'SEQUENCE'))
        ->join('FAID_STUDENT_AWARD_YEAR', 'faidstuawardyr', 'faidstuawrdyrtrm.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID')
        ->fields('faidstuawardyr', array('AWARD_YEAR_ID', 'AWARD_YEAR'))
        ->join('FAID_STUDENT_AWARD_YEAR_AWARDS', 'faidawardyearawards', 'faidawardyearawards.AWARD_YEAR_ID = faidstuawardyr.AWARD_YEAR_ID AND awardcode.AWARD_CODE_ID = faidawardyearawards.AWARD_CODE_ID')
        ->fields('faidawardyearawards', array('AWARD_YEAR_AWARD_ID'))
        ->join('CONS_CONSTITUENT', 'cons', 'cons.CONSTITUENT_ID = faidstuawardyr.STUDENT_ID')
        ->condition('cons.PERMANENT_NUMBER', $pf_stu_award['alternate_id'])
        ->condition('faidstuawardyr.AWARD_YEAR', $pf_stu_award['award_year_token']);
      if (count($kula_award) > 0) {
        $untouched_awards_result = $untouched_awards_result->condition('faidstuawrds.AWARD_ID', $kula_award, 'NOT IN');
      }
        $untouched_awards_result = $untouched_awards_result->execute();
      while ($untouched_award = $untouched_awards_result->fetch()) {
        $this->posterFactory->newPoster()->noLog()->delete('HEd.FAID.Student.Award', $untouched_award['AWARD_ID'])->process();
        
        // check if award year record still has children
        $faid_student_award_year = $this->db->db_select('FAID_STUDENT_AWARDS', 'stuawardsyears')
          ->expression('COUNT(*)', 'total')
          ->join('FAID_STUDENT_AWARD_YEAR_TERMS', 'stuawardyearterms', 'stuawardyearterms.AWARD_YEAR_TERM_ID = stuawardsyears.AWARD_YEAR_TERM_ID')
          ->condition('stuawardsyears.AWARD_CODE_ID', $untouched_award['AWARD_CODE_ID'])
          ->condition('stuawardyearterms.AWARD_YEAR_ID', $untouched_award['AWARD_YEAR_ID'])
          ->execute()->fetch();
        if ($faid_student_award_year == 0) {
          $this->posterFactory->newPoster()->noLog()->delete('HEd.FAID.Student.AwardYear.Award', $untouched_award['AWARD_YEAR_AWARD_ID'])->process();
        }
      
      } // end while
      
    } // end while on $stu_awards
    
    } // end if connection
    
  }

}