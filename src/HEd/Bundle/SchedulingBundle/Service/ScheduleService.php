<?php

namespace Kula\HEd\Bundle\SchedulingBundle\Service;

class ScheduleService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;
  
  public function __construct(\Kula\Component\Database\Connection $db, 
                              \Kula\Component\Database\PosterFactory $poster_factory,
                              $record = null,
                              $session = null) {
    $this->database = $db;
    $this->record = $record;
    $this->poster_factory = $poster_factory;
    $this->session = $session;
  }
  
  public function addClassForStudentStatus($student_status_id, $section_id, $start_date) {
    
    // Get Seciton Info
    $section_info = $this->database->select('STUD_SECTION')
      ->fields(null, array('START_DATE', 'CREDITS', 'MARK_SCALE_ID'))
      ->predicate('SECTION_ID', $section_id)
      ->execute()->fetch();
    // Get Student Status
    $student_status_info = $this->database->select('STUD_STUDENT_STATUS')
      ->fields(null, array('LEVEL'))
      ->predicate('STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
      
    $class_info['STUDENT_STATUS_ID'] = $student_status_id;  
    $class_info['SECTION_ID'] = $section_id;
    $class_info['CREDITS_ATTEMPTED'] = $section_info['CREDITS'];
    $class_info['MARK_SCALE_ID'] = $section_info['MARK_SCALE_ID'];
    $class_info['LEVEL'] = $student_status_info['LEVEL'];
  
    if ($section_info['START_DATE'] < $start_date)
      $class_info['START_DATE'] = $start_date;
    else
      $class_info['START_DATE'] = $section_info['START_DATE'];
    
    if (!$this->database->inTransaction())
      $this->database->beginTransaction();
    
    $student_class_poster = $this->poster_factory->newPoster(array('STUD_STUDENT_CLASSES' => array('new' => $class_info)));
    $student_class_id = $student_class_poster->getResultForTable('insert', 'STUD_STUDENT_CLASSES')['new'];
    
    // check if exists in wait list
    $waitlist_info = $this->database->select('STUD_STUDENT_WAIT_LIST')
      ->fields(null, array('STUDENT_WAIT_LIST_ID'))
      ->predicate('STUDENT_STATUS_ID', $student_status_id)
      ->predicate('SECTION_ID', $section_id)
      ->execute()->fetch();
    if ($waitlist_info['STUDENT_WAIT_LIST_ID']) {
      $waitlist_poster = $this->poster_factory->newPoster(null, null, array('STUD_STUDENT_WAIT_LIST' => array($waitlist_info['STUDENT_WAIT_LIST_ID'] => array('delete_row' => 'Y'))));
    }
    
    // process course fees
    //$billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->database, $this->poster_factory, $this->record, $this->session);
    //$billing_service->addCourseFees($student_class_id);
    
    if ($student_class_id) {
      
      // Update section totals
      $section_row = $this->database->select('STUD_SECTION')
        ->fields(null, array('ENROLLED_TOTAL'))
        ->predicate('SECTION_ID', $section_id)
        ->execute()->fetch();
      
      $new_total = $section_row['ENROLLED_TOTAL'] + 1;
      $section_poster = $this->poster_factory->newPoster(null, array('STUD_SECTION' => array($section_id => array('ENROLLED_TOTAL' => $new_total))));
      $section_poster_affected = $section_poster->getResultForTable('update', 'STUD_SECTION')[$section_id];
      if ($section_poster_affected) {
        $this->database->commit();
      } else {
        $this->database->rollback();
      }
      
      return $student_class_id;
      
    } else {
      $this->database->rollback();
      return false;
    }
    
  }
  
  public function addWaitListClassForStudentStatus($student_status_id, $section_id) {
    
    $waitlist_info = array();
    $waitlist_info['STUDENT_STATUS_ID'] = $student_status_id;  
    $waitlist_info['SECTION_ID'] = $section_id;
    $waitlist_info['ADDED_TIMESTAMP'] = date('Y-m-d H:i:s');
    
    if (!$this->database->inTransaction())
      $this->database->beginTransaction();
    
    $waitlist_poster = $this->poster_factory->newPoster(array('STUD_STUDENT_WAIT_LIST' => array('new' => $waitlist_info)));
    $waitlist_id = $waitlist_poster->getResultForTable('insert', 'STUD_STUDENT_WAIT_LIST')['new'];
    
    if ($waitlist_id) {
    
      // Update section totals
      $section_row = $this->database->select('STUD_SECTION')
      ->fields(null, array('WAIT_LISTED_TOTAL'))
      ->predicate('SECTION_ID', $section_id)
      ->execute()->fetch();
    
      $new_total = $section_row['WAIT_LISTED_TOTAL'] + 1;
    
      $section_poster = $this->poster_factory->newPoster(null, array('STUD_SECTION' => array($section_id => array('WAIT_LISTED_TOTAL' => $new_total))));
      $section_poster_affected = $section_poster->getResultForTable('update', 'STUD_SECTION')[$section_id];
      if ($section_poster_affected) {
        $this->database->commit();
      } else {
        $this->database->rollback();
      }
    
    } else {
      $this->database->rollback();
      return false;
    }
    
  }
  
  public function dropAllClassesForStudentStatus($student_status_id, $drop_date) {
    $predicate_or = new \Kula\Component\Database\Query\Predicate('OR');
    $predicate_or = $predicate_or->predicate('DROPPED', null)->predicate('DROPPED', 'N');
    
    if (!$this->database->inTransaction())
      $this->database->beginTransaction();
    
    $classes_result = $this->database->select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array('STUDENT_CLASS_ID'))
      ->predicate('STUDENT_STATUS_ID', $student_status_id)
      ->predicate($predicate_or)
      ->execute();
    while ($classes_row = $classes_result->fetch()) {
      $this->dropClassForStudentStatus($classes_row['STUDENT_CLASS_ID'], $drop_date);
    }
    
    if (!$this->database->inTransaction())
      $this->database->commit();
  }
  
  public function dropClassForStudentStatus($class_id, $drop_date) {
    
    // set start date
    $term_info = $this->database->select('CORE_TERM')
      ->fields(null, array('START_DATE', 'END_DATE'))
      ->join('CORE_ORGANIZATION_TERMS', null, null, 'CORE_TERM.TERM_ID = CORE_ORGANIZATION_TERMS.TERM_ID')
      ->predicate('ORGANIZATION_TERM_ID', $this->record->getSelectedRecord()['ORGANIZATION_TERM_ID'])
      ->execute()->fetch();
    
    if ($term_info['START_DATE'] < date('Y-m-d'))
      $end_date = date('Y-m-d');
    else
      $end_date = null;
        
    if (!$this->database->inTransaction())
      $this->database->beginTransaction();

    $class_row = $this->database->select('STUD_STUDENT_CLASSES')
          ->predicate('STUDENT_CLASS_ID', $class_id)
          ->execute()->fetch();
        
    $class_data['DROPPED']['checkbox_hidden'] = '';
    $class_data['DROPPED']['checkbox'] = 'Y';
    $class_data['DROP_DATE'] = $drop_date;
    $class_data['END_DATE'] = $end_date;
    if ($drop_date < $class_row['START_DATE']) $class_data['START_DATE'] = null;
    if ($drop_date >= $class_row['START_DATE']) $class_data['END_DATE'] = $drop_date;
    $class_poster = $this->poster_factory->newPoster(null, array('STUD_STUDENT_CLASSES' => array($class_id => $class_data)));
    $class_poster_affected = $class_poster->getResultForTable('update', 'STUD_STUDENT_CLASSES')[$class_id];
    if ($class_poster_affected) {
      
      // process course fees
      //if ($drop_date < $class_row['START_DATE']) {
      //  $billing_service = new \Kula\Bundle\HEd\StudentBillingBundle\ConstituentBillingService($this->database, $this->poster_factory, $this->record, $this->session);
      //  $billing_service->removeCourseFees($class_id);
      //}
      
      // Update section totals
      $section_row = $this->database->select('STUD_SECTION')
        ->fields(null, array('ENROLLED_TOTAL'))
        ->predicate('SECTION_ID', $class_row['SECTION_ID'])
        ->execute()->fetch();
          
      $new_total = $section_row['ENROLLED_TOTAL'] - 1;
      $section_poster = $this->poster_factory->newPoster(null, array('STUD_SECTION' => array($class_row['SECTION_ID'] => array('ENROLLED_TOTAL' => $new_total))));
      $section_poster_affected = $section_poster->getResultForTable('update', 'STUD_SECTION')[$class_row['SECTION_ID']];
      if ($section_poster_affected) {
        $this->database->commit();
      } else {
        $this->database->rollback();
      }
    } else {
      $this->database->rollback();
    }  
  }
  
  public function dropWaitListClassForStudentStatus($waitlist_id) {

    $class_row = $this->database->select('STUD_STUDENT_WAIT_LIST')
          ->predicate('STUDENT_WAIT_LIST_ID', $waitlist_id)
          ->execute()->fetch();
    
    if (!$this->database->inTransaction())
      $this->database->beginTransaction();
    
    $waitlist_poster = $this->poster_factory->newPoster(null, null, array('STUD_STUDENT_WAIT_LIST' => array($waitlist_id => array('delete_row' => 'Y'))));
    $wait_list_poster_affected = $waitlist_poster->getResultForTable('delete', 'STUD_STUDENT_WAIT_LIST')[$waitlist_id];

    if ($wait_list_poster_affected) {
      // Update section totals
      $section_row = $this->database->select('STUD_SECTION')
        ->fields(null, array('WAIT_LISTED_TOTAL'))
        ->predicate('SECTION_ID', $class_row['SECTION_ID'])
        ->execute()->fetch();
          
      $new_total = $section_row['WAIT_LISTED_TOTAL'] - 1;
      $section_poster = $this->poster_factory->newPoster(null, array('STUD_SECTION' => array($class_row['SECTION_ID'] => array('WAIT_LISTED_TOTAL' => $new_total))));
      $section_poster_affected = $section_poster->getResultForTable('update', 'STUD_SECTION')[$class_row['SECTION_ID']];
      if ($section_poster_affected) {
        $this->database->commit();
      } else {
        $this->database->rollback();
      }
    } else {
      $this->database->rollback();
      return false;
    }
  }
  
  public function calculateFTE($student_status_id) {
    
    // Get total credits
    $student_status = $this->database->select('STUD_STUDENT_STATUS', 'status')
      ->fields('status', array('TOTAL_CREDITS_ATTEMPTED', 'ORGANIZATION_TERM_ID', 'LEVEL', 'FTE', 'STATUS'))
      ->predicate('status.STUDENT_STATUS_ID', $student_status_id)
      ->execute()->fetch();
    
    // Determine FTE
    $fte = $this->database->select('STUD_SCHOOL_TERM_LEVEL_FTE', 'schooltermlevelFTE')
      ->fields('schooltermlevelFTE', array('FTE'))
      ->join('STUD_SCHOOL_TERM_LEVEL', 'schooltermlevel', null, 'schooltermlevel.SCHOOL_TERM_LEVEL_ID = schooltermlevelFTE.SCHOOL_TERM_LEVEL_ID')
      ->predicate('ORGANIZATION_TERM_ID', $student_status['ORGANIZATION_TERM_ID'])
      ->predicate('LEVEL', $student_status['LEVEL'])
      ->predicate('CREDIT_TOTAL', $student_status['TOTAL_CREDITS_ATTEMPTED'], '<=')
      ->order_by('CREDIT_TOTAL', 'DESC')
      ->execute()->fetch()['FTE'];
    
    if ($fte != $student_status['FTE'] AND $student_status['STATUS'] == '') {
      // Need to change FTE
      
      // Need to see if activity record already exists
      $student_activity_record = $this->database->select('STUD_STUDENT_ENROLLMENT_ACTIVITY', 'enrollment_activity')
        ->fields('enrollment_activity', array('ENROLLMENT_ACTIVITY_ID', 'ENROLLMENT_ID', 'EFFECTIVE_DATE'))
        ->join('STUD_STUDENT_ENROLLMENT', 'enrollment', null, 'enrollment.ENROLLMENT_ID = enrollment_activity.ENROLLMENT_ID')
        ->join('STUD_STUDENT_STATUS', 'status', null, 'status.STUDENT_STATUS_ID = enrollment.STUDENT_STATUS_ID')
        ->predicate('status.STUDENT_STATUS_ID', $student_status_id)
        ->predicate('enrollment.LEAVE_DATE', null)
        ->order_by('EFFECTIVE_DATE')
        ->execute()->fetch();
      
      // if effective date same as today
      if ($student_activity_record['EFFECTIVE_DATE'] == date('Y-m-d')) {
        
        // update existing record
        $enrollment_activity_poster = $this->poster_factory->newPoster(null, array('STUD_STUDENT_ENROLLMENT_ACTIVITY' => array($student_activity_record['ENROLLMENT_ACTIVITY_ID'] => array('FTE' => $fte))));
      } else {
        // create new record
        $enrollment_activity_poster = $this->poster_factory->newPoster(array('STUD_STUDENT_ENROLLMENT_ACTIVITY' => array('new' => array(
          'EFFECTIVE_DATE' => date('Y-m-d'),
          'ENROLLMENT_ID' => $student_activity_record['ENROLLMENT_ID'],
          'FTE' => $fte
        ))));
      }
      
      // update existing status
      $status_poster = $this->poster_factory->newPoster(null, array('STUD_STUDENT_STATUS' => array($student_status_id => array('FTE' => $fte))));
      return $status_poster->getResultForTable('update', 'STUD_STUDENT_STATUS')[$student_status_id];
      
    }
    
  }
  
  public function calculateTotalAttemptedCredits($student_status_id) {
    
    $predicate_or = new \Kula\Component\Database\Query\Predicate('OR');
    $predicate_or = $predicate_or->predicate('DROPPED', null)->predicate('DROPPED', 'N');
    
    $classes = $this->database->select('STUD_STUDENT_CLASSES', 'classes')
      ->expressions(array('SUM(CREDITS_ATTEMPTED)' => 'total_credits_attempted'))
      ->join('STUD_MARK_SCALE', 'markscale', null, "markscale.MARK_SCALE_ID = classes.MARK_SCALE_ID AND markscale.AUDIT = 'N'")
      ->predicate('STUDENT_STATUS_ID', $student_status_id)
      ->predicate($predicate_or)
      ->execute()->fetch();
    
    $total_credits_poster = $this->poster_factory->newPoster(null, array('STUD_STUDENT_STATUS' => array($student_status_id => array('TOTAL_CREDITS_ATTEMPTED' => $classes['total_credits_attempted']))));
    return $total_credits_poster->getResultForTable('update', 'STUD_STUDENT_STATUS')[$student_status_id];
  }
  
  public function calculateLatestDropDate($student_status_id) {
    
    $classes = $this->database->select('STUD_STUDENT_CLASSES', 'classes')
      ->fields('classes', array('DROP_DATE'))
      ->predicate('STUDENT_STATUS_ID', $student_status_id)
      ->predicate('DROPPED', 'Y')
      ->order_by('DROP_DATE', 'DESC')
      ->execute()->fetch();
    
    return $classes['DROP_DATE'];
  }
  
}