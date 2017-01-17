<?php

namespace Kula\HEd\Bundle\StudentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreFormsController extends Controller {
  
  public function setupAction() {
    $this->authorize();
    $this->processForm();
    $this->setRecordType('Core.Organization.School.Term', null, 
    array('Core.Organization.Term' =>
      array('Core.Organization.Term.OrganizationID' => $this->focus->getSchoolIDs(),
            'Core.Organization.Term.TermID' => $this->focus->getTermID()
           )
         )
    );
    $forms = $this->db()->db_select('STUD_FORM', 'form')
      ->fields('form')
      ->condition('form.ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
      ->execute()->fetchAll();
    
    return $this->render('KulaHEdStudentBundle:CoreForms:setup.html.twig', array('forms' => $forms));
  }

  public function studentFormsAction() {
    $this->authorize();
    $this->setRecordType('Core.HEd.Student.Status');

    if ($items_to_add = $this->request->request->get('add')) {
      
      unset($items_to_add['HEd.Student.Form']['new_num']);

      if (count($items_to_add['HEd.Student.Form']) > 0) {

      $transaction = $this->db()->db_transaction();
      
      foreach($items_to_add as $table => $table_row) {
        foreach($table_row as $row_id => $row) {
          // Get form contents
          $form_contents = $this->db()->db_select('STUD_FORM', 'form')
            ->fields('form', array('FORM_TEXT'))
            ->condition('form.FORM_ID', $row['HEd.Student.Form.FormID'])
            ->execute()->fetch();

          $form_poster = $this->newPoster()->add('HEd.Student.Form', $row_id, array(
            'HEd.Student.Form.StudentStatusID' => $row['HEd.Student.Form.StudentStatusID'],
            'HEd.Student.Form.FormID' => $row['HEd.Student.Form.FormID'],
            'HEd.Student.Form.FormText' => $form_contents['FORM_TEXT']
          ))->process();
        }
      }
      
      $transaction->commit();

      } else {
      $this->processForm();
      }
    } 

    $forms = array();
    
    if ($this->record->getSelectedRecordID()) {
      $forms = $this->db()->db_select('STUD_STUDENT_FORMS', 'stuforms')
        ->fields('stuforms', array('STUDENT_FORM_ID', 'FORM_ID'))
        ->join('STUD_FORM', 'form', 'stuforms.FORM_ID = form.FORM_ID')
        ->fields('form', array('FORM_NAME'))
        ->condition('stuforms.STUDENT_STATUS_ID', $this->record->getSelectedRecordID())
        ->orderBy('form.FORM_NAME', 'ASC')
        ->execute()->fetchAll();
    }
    
    return $this->render('KulaHEdStudentBundle:CoreForms:student_forms.html.twig', array('forms' => $forms));
  }
  
}