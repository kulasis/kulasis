<?php

namespace Kula\K12\Bundle\SchedulingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\ReportController;

class CoreAAMasterRosterReportController extends ReportController {
  
  public function indexAction() {
    $this->authorize();
    if ($this->request->query->get('record_type') == 'Core.K12.Section' AND $this->request->query->get('record_id') != '')
      $this->setRecordType('Core.K12.Section');
    //$this->assign("grade_levels", Kula_Records_GradeLevel::getGradeLevelsForSchoolForMenu($_SESSION['kula']['school']['id'], "Y"));
    return $this->render('KulaK12SchedulingBundle:CoreClassRosterReport:reports_signinout.html.twig');
  }
  
  public function generateAction()
  {  
    $this->authorize();
    
    $form = $this->request->request->get('non');
    
    $pdf = new \Kula\K12\Bundle\SchedulingBundle\Report\AAMasterRosterReport("P");
    $pdf->SetFillColor(245,245,245);
    $pdf->row_count = 0;
    
    // Get Data and Load
    $result = $this->db()->db_select('STUD_SECTION', 'section')
      ->fields('section', array('SECTION_ID', 'SECTION_NUMBER', 'SECTION_NAME'))
      ->join('STUD_COURSE', 'course', 'course.COURSE_ID = section.COURSE_ID')
      ->fields('course', array('COURSE_TITLE', 'SHORT_TITLE'))
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->join('CORE_ORGANIZATION', 'org', 'orgterms.ORGANIZATION_ID = org.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_ABBREVIATION'))
      ->leftJoin('STUD_STAFF_ORGANIZATION_TERMS', 'stafforgterm', 'stafforgterm.STAFF_ORGANIZATION_TERM_ID = section.STAFF_ORGANIZATION_TERM_ID')
      ->leftJoin('STUD_STAFF', 'staff', 'staff.STAFF_ID = stafforgterm.STAFF_ID')
      ->fields('staff', array('ABBREVIATED_NAME'))
      ->join('STUD_STUDENT_CLASSES', 'class', 'class.SECTION_ID = section.SECTION_ID')
      ->fields('class', array('STUDENT_CLASS_ID', 'START_DATE', 'END_DATE'))
      ->join('STUD_STUDENT_STATUS', 'status', 'status.STUDENT_STATUS_ID = class.STUDENT_STATUS_ID')
      ->fields('status', array('STUDENT_STATUS_ID', 'AGE', 'SHIRT_SIZE'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'grvalue', "grvalue.CODE = status.GRADE AND grvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'K12.Student.Enrollment.Grade')")
      ->fields('grvalue', array('DESCRIPTION' => 'GRADE'))
      ->leftJoin('CORE_LOOKUP_VALUES', 'entercodevalue', "entercodevalue.CODE = status.ENTER_CODE AND grvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'K12.Student.Enrollment.EnterCode')")
      ->fields('entercodevalue', array('DESCRIPTION' => 'ENTER_CODE'))
      ->join('STUD_STUDENT', 'student', 'status.STUDENT_ID = student.STUDENT_ID')
      ->fields('student', array('STUDENT_ID', 'MEDICAL_NOTES'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'GENDER', 'NOTES'))
      ->condition('DROPPED', '0');
    $org_term_ids = $this->focus->getOrganizationTermIDs();
    if (isset($org_term_ids) AND count($org_term_ids) > 0)
      $result = $result->condition('section.ORGANIZATION_TERM_ID', $org_term_ids);
    
    // Add on selected record
    $record_id = $this->request->request->get('record_id');
    if (isset($record_id) AND $record_id != '')
      $result = $result->condition('section.SECTION_ID', $record_id);
    
    $result = $result
      ->orderBy('stucon.LAST_NAME', 'ASC')
      ->orderBy('stucon.FIRST_NAME', 'ASC')
      ->execute();
    
    $last_section_id = 0;
    
    while ($row = $result->fetch()) {
      
      if (isset($meetings[$row['SECTION_ID']]))  {
      $pdf->setData(array_merge($row, $meetings[$row['SECTION_ID']]));
      } else {
        $pdf->setData($row);  
      }
      if ($last_student_id != $row['STUDENT_ID']) {
        $pdf->row_count = 1;
        $pdf->StartPageGroup();
        $pdf->AddPage();
      }
      
  	  // Get group
  	  $row['group'] = '';
  	  if (isset($form['section_number']) AND $form['section_number'] != '') {
  		  $group = $this->db()->db_select('STUD_SECTION', 'section')
  			->fields('section', array('SECTION_NAME', 'SECTION_NUMBER'))
  			->join('STUD_STUDENT_CLASSES', 'cla', 'cla.SECTION_ID = section.section_ID')
  			->condition('cla.STUDENT_STATUS_ID', $row['STUDENT_STATUS_ID'])
  			->join('STUD_COURSE', 'crs', 'crs.COURSE_ID = section.COURSE_ID')
  			->fields('crs', array('COURSE_TITLE'))
  			->condition('section.SECTION_NUMBER', $form['section_number'], 'LIKE')
  			->condition('cla.DROPPED', 0)
  			->execute()->fetch();
  		  $row['group'] = ($group['SECTION_NAME'] != '') ? $group['SECTION_NAME'] : $group['COURSE_TITLE'];
  	  }
      
      // Get parents
      $parentsStr = array();
      $parentsIDs = array();
      $parentsIDs[] = $row['STUDENT_ID'];
      $parents = $this->db()->db_select('CONS_CONSTITUENT', 'constituent')
        ->fields('constituent', array('CONSTITUENT_ID', 'LAST_NAME', 'FIRST_NAME'))
        ->join('CONS_RELATIONSHIP', 'relationship', 'relationship.RELATED_CONSTITUENT_ID = constituent.CONSTITUENT_ID')
        ->condition('relationship.CONSTITUENT_ID', $row['STUDENT_ID'])
        ->join('STUD_STUDENT_PARENTS', 'stupar', 'stupar.STUDENT_PARENT_ID = relationship.RELATIONSHIP_ID')
        ->leftJoin('CORE_LOOKUP_VALUES', 'relvalue', "relvalue.CODE = relationship.RELATIONSHIP AND relvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'Constituent.Relationship.Type')")
        ->fields('relvalue', array('DESCRIPTION' => 'RELATIONSHIP'))
        ->condition('stupar.CONTACT_NOT_ALLOWED', 0)
        ->condition('stupar.RESTRAINING_ORDER', 0)
        ->execute();
      while ($parent = $parents->fetch()) {
        $parentsStrElement = $parent['FIRST_NAME'].' '.$parent['LAST_NAME'];
        $parentsIDs[] = $parent['CONSTITUENT_ID'];
        if ($parent['RELATIONSHIP']) $parentsStrElement .= ' ('.$parent['RELATIONSHIP'].')';
        $parentsStr[] = $parentsStrElement;
        unset($parentsStrElement);
      }
      $row['parents'] = implode(', ', $parentsStr);
      
      // Get address
      
      // Get Email
      $emailsStr = array();
      $emails = $this->db()->db_select('CONS_EMAIL_ADDRESS', 'email')
        ->fields('email', array('EMAIL_ADDRESS', 'EMAIL_ADDRESS_TYPE'))
        ->condition('email.CONSTITUENT_ID', $parentsIDs)
        ->leftJoin('CORE_LOOKUP_VALUES', 'emailvalue', "emailvalue.CODE = email.EMAIL_ADDRESS_TYPE AND emailvalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'Constituent.EmailAddress.Type')")
        ->fields('emailvalue', array('DESCRIPTION' => 'EMAIL_TYPE'))
        ->condition('email.UNDELIVERABLE', 0)
        ->condition('email.ACTIVE', 1)
        ->execute();
      while ($email = $emails->fetch()) {
        $emailsStrElement = $email['EMAIL_ADDRESS'];
        if ($email['EMAIL_TYPE']) $emailsStrElement .= ' ('.$email['EMAIL_TYPE'].')';
        $emailsStr[] = $emailsStrElement;
        unset($emailsStrElement);
      }
      $row['email_addresses'] = implode(', ', $emailsStr);
      
      // Get phones
      $phonesStr = array();
      $phones = $this->db()->db_select('CONS_PHONE', 'phone')
        ->fields('phone', array('PHONE_NUMBER', 'PHONE_EXTENSION'))
        ->condition('phone.CONSTITUENT_ID', $parentsIDs)
        ->leftJoin('CORE_LOOKUP_VALUES', 'phovalue', "phovalue.CODE = phone.PHONE_TYPE AND phovalue.LOOKUP_TABLE_ID = (SELECT LOOKUP_TABLE_ID FROM CORE_LOOKUP_TABLES WHERE LOOKUP_TABLE_NAME = 'Constituent.Phone.Type')")
        ->fields('phovalue', array('DESCRIPTION' => 'PHONE_TYPE'))
        ->condition('phone.DISCONNECTED', 0)
        ->condition('phone.ACTIVE', 1)
        ->execute();
      while ($phone = $phones->fetch()) {
        $phonesStrElement = $phone['PHONE_NUMBER'];
        if ($phone['PHONE_EXTENSION']) $phonesStrElement .= ' '.$phone['PHONE_EXTENSION'];
        if ($phone['PHONE_TYPE']) $phonesStrElement .= ' ('.$phone['PHONE_TYPE'].')';
        $phonesStr[] = $phonesStrElement;
        unset($phonesStrElement);
      }
      $row['phones'] = implode(', ', $phonesStr);
      
      // Get emergency contacts
      $emergencyContacts = $this->db()->db_select('STUD_STUDENT_EMERGENCY_CONTACT', 'emr')
        ->fields('emr', array('EMERGENCY_CONTACT_NAME', 'EMERGENCY_CONTACT_PHONE', 'EMERGENCY_CONTACT_EMAIL', 'RELATIONSHIP', 'SORT',  'AUTHORIZED_DRIVER'))
        ->condition('emr.STUDENT_ID', $row['STUDENT_ID'])
        ->orderBy('emr.SORT', 'ASC')
        ->execute();
      while ($emergencyContact = $emergencyContacts->fetch()) {
        $row['emergency_contacts'][] = $emergencyContact;
      }
      
      $pdf->table_row($row);
      $last_student_id = $row['STUDENT_ID'];
      $pdf->row_count++;
      $pdf->row_page_count++;
      $pdf->row_total_count++;
    }
    // Closing line
    return $this->pdfResponse($pdf->Output('','S'));
  
  }
}