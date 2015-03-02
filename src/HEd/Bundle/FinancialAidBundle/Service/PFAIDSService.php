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
  
  private function pfaids_connect($id) {
    
    // Get database connection parameters
    $intgDB = $this->db->db_select('CORE_INTG_DATABASE')
      ->fields('CORE_INTG_DATABASE')
      ->condition('APPLICATION', 'PFAIDEU')
      ->condition('INTG_DATABASE_ID', $id)
      ->execute()->fetch();
    
    $connection = mssql_connect($intgDB['HOST'], $intgDB['USERNAME'], $intgDB['PASSWORD']) or die("Couldn't connect to SQL Server on ".$intgDB['HOST']);
    mssql_select_db($intgDB['DATABASE_NAME'], $connection) or die("Couldn't open database on SQL Server for ".$intgDB['HOST']);
    
    return $connection;
  }
  
  public function pfaids_deleteRecords($intgDBID, $awardYearToken = null, $ssn = null) {
    
    $connection = $this->pfaids_connect($intgDBID);
    
    $query = "DELETE FROM external_data";
    
    if ($awardYearToken)
      $query .= " WHERE award_year_token = '".str_replace("'", "''", $awardYearToken)."'";
    if ($awardYearToken AND $ssn)
      $query .= " AND ssn = '".str_replace("'", "''", $ssn)."'";
    
    mssql_query($query, $connection);
    return mssql_rows_affected($connection);
  }
  
  public function pfaids_addRecords($intgDBID, $awardYearToken, $id = null) {
    
    $connection = $this->pfaids_connect($intgDBID);
    
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
          $pf_data['dateint1_field_id'] = "'7752'";
          $pf_data['dateint1_value'] = sprintf('%0.2f', round($stu_row['TOTAL_CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP));
        } else {
          // need to add total credits and term credits attempted
          $pf_data['dateint1_field_id'] = "'7752'";
          $pf_data['dateint1_value'] = sprintf('%0.2f', round($stu_row['TOTAL_CREDITS_EARNED'] + $stu_row['TERM_CREDITS_ATTEMPTED'], 2, PHP_ROUND_HALF_UP));
        }
        
        // If transfer credits
        if ($stu_row['TRNS_CREDITS_EARNED'] > 0) {
          $pf_data['dateint2_field_id'] = "'7753'";
          $pf_data['dateint2_value'] = sprintf('%0.2f', round($stu_row['TRNS_CREDITS_EARNED'], 2, PHP_ROUND_HALF_UP));
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

  

}