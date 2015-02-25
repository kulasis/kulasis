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
    
    $connection = mssql_connect($intgDB['HOST'], $intgDB['USERNAME'], $intgDB['PASSWORD']);
    mssql_select_db($intgDB['DATABASE_NAME'], $connection);
    
    return $connection;
  }
  
  public function pfaids_deleteRecords($intgDBID, $ssn = null, $awardYearToken = null) {
    
    $connection = $this->pfaids_connect($intgDBID);
    
    $query = "DELETE FROM external_data";
    
    if ($ssn)
      $query .= "WHERE ssn = ".str_replace("'", "''", $ssn)." ";
    if ($ssn AND $awardYearToken)
      $query .= " AND award_year_token = ".str_replace("'", "''", $awardYearToken)." ";
    
    mssql_query($query, $connection);
    return mssql_rows_affected($connection);
  }
  
  public function pfaids_addRecords($intgDBID, $id = null) {
    
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
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = stustatus.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('FINANCIAL_AID_YEAR'))
      ->condition('stustatus.ORGANIZATION_TERM_ID', $this->focus->getOrganizationTermIDs())
      ->execute();
    while ($stu_row = $students_result->fetch()) {
  
      $cleaned_ssn = str_ireplace('-', '', $stu_row['encrypted_ssn']);
  
      if (strlen($cleaned_ssn) == 9) {
    
        $pf_data = array(
          '[ssn]' => "'".$cleaned_ssn."'",
          '[award_year_token]' => $stu_row['FINANCIAL_AID_YEAR'],
          '[ALTERnate_id]' => "'".$stu_row['PERMANENT_NUMBER']."'",
          '[last_name]' => "'".str_replace("'", "''", $stu_row['LAST_NAME'])."'",
          '[first_name]' => "'".str_replace("'", "''", $stu_row['FIRST_NAME'])."'",
          '[middle_init]' => strlen(substr($stu_row['MIDDLE_NAME'], 0, 1)) > 0 ? "'".substr($stu_row['MIDDLE_NAME'], 0, 1)."'" : 'null', 
          '[name_mid]' => $stu_row['MIDDLE_NAME'] ? "'".str_replace("'", "''", $stu_row['MIDDLE_NAME'])."'" : 'null', 
          '[birth_date]' => $stu_row['BIRTH_DATE'] ? "'".date('mdY', strtotime($stu_row['BIRTH_DATE']))."'" : 'null',
        );
        
        // check if already exists
        $already_exists = mssql_fetch_array(mssql_query("SELECT ssn, award_year_token FROM external_data WHERE ssn = '".$cleaned_ssn."' AND award_year_token = '".$stu_row['FINANCIAL_AID_YEAR']."'"));
        
        if ($already_exists) {
          $inner_sql = array();
          
          foreach($pf_data as $key => $value) {
            $inner_sql[] = $key.' = '.$value;
          }
          
          $sql = "UPDATE external_data SET ".implode(', ', $inner_sql)." WHERE [ssn] = '".$cleaned_ssn."' AND [award_year_token] = '".$stu_row['FINANCIAL_AID_YEAR']."'";
          $update_count++;
        } else {
          $sql = "INSERT INTO external_data (".implode(', ', array_keys($pf_data)).") VALUES (".implode(", ", array_values($pf_data)).")";
          $insert_count++;
        }
    
        mssql_query($sql);
      }
      
      unset($cleaned_ssn, $pf_data);
  
    }
    
    $results['update_count'] = $update_count;
    $results['insert_count'] = $insert_count;
    
    return $results;
  }

  

}