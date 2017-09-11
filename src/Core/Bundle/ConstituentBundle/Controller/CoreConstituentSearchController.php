<?php

namespace Kula\Core\Bundle\ConstituentBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

class CoreConstituentSearchController extends Controller {

  public function indexAction() {
    $this->authorize();
    $this->setSubmitMode('search');

    $constituents = array();
    $post_data = array();


    if ($this->request->getMethod() == 'POST') {

      $searcher = $this->get('kula.core.searcher');
      $post_data = $searcher->startProcessing();
    
      $db_select = $searcher->prepareSearch($post_data, 'CONS_CONSTITUENT', 'CONSTITUENT_ID');

      $db_select = $db_select->fields('CONS_CONSTITUENT', array('LAST_NAME', 'FIRST_NAME', 'PERMANENT_NUMBER', 'MIDDLE_NAME', 'MAIDEN_NAME', 'PREFERRED_NAME'));

      // if phone number submitted, add on phone table
      if (isset($post_data['Core.Constituent.Phone'])) {
        $db_select = $db_select->join('CONS_PHONE', 'CONS_PHONE', 'CONS_PHONE.CONSTITUENT_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
        $db_select = $db_select->fields('CONS_PHONE', array('PHONE_NUMBER', 'PHONE_TYPE'));
      }
      // if phone number submitted, add on phone table
      if (isset($post_data['Core.Constituent.EmailAddress'])) {
        $db_select = $db_select->join('CONS_EMAIL_ADDRESS', 'CONS_EMAIL_ADDRESS', 'CONS_EMAIL_ADDRESS.CONSTITUENT_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
        $db_select = $db_select->fields('CONS_EMAIL_ADDRESS', array('EMAIL_ADDRESS', 'EMAIL_ADDRESS_TYPE'));
      }

      $db_select = $db_select->leftJoin('STUD_STUDENT', 'STUD_STUDENT', 'STUD_STUDENT.STUDENT_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
      $db_select = $db_select->fields('STUD_STUDENT', array('STUDENT_ID'));

      $db_select = $db_select->range(0, 100);

      $db_select = $db_select->orderBy('CONS_CONSTITUENT.LAST_NAME', 'ASC')
        ->orderBy('CONS_CONSTITUENT.FIRST_NAME', 'ASC')
        ->orderBy('CONS_CONSTITUENT.MIDDLE_NAME', 'ASC');

      $constituents = $db_select->execute()->fetchAll();
    }

    
    return $this->render('KulaCoreConstituentBundle:CoreConstituentSearch:results.html.twig', array('constituents' => $constituents, 'post_data' => $post_data));
  }

}