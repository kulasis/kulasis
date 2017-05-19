<?php

namespace Kula\HEd\Bundle\GradingBundle\Service;

class TermTotalsService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;

  public function __construct(\Kula\Core\Component\DB\DB $db, 
                              \Kula\Core\Component\DB\PosterFactory $poster_factory) {
    $this->db = $db;
    $this->posterFactory = $poster_factory;
    $this->setGPARegardless = false;
  }
  
  public function calculateTermTotals($studentID) {
    
    $lastRow = array();
    $coursesInProgress = false;
    
    $terms = $this->db->db_select('STUD_STUDENT', 'student', array('nolog' => true))
      ->distinct()
      ->fields('student', array('STUDENT_ID'))
      ->join('CONS_CONSTITUENT', 'stucon', 'student.STUDENT_ID = stucon.CONSTITUENT_ID')
      ->fields('stucon', array('PERMANENT_NUMBER'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', 'coursehistory.STUDENT_ID = student.STUDENT_ID')
      ->fields('coursehistory', array('LEVEL', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'NON_ORGANIZATION_ID'))
      ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = coursehistory.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->leftJoin('CORE_NON_ORGANIZATION', 'nonorg', 'nonorg.NON_ORGANIZATION_ID = coursehistory.NON_ORGANIZATION_ID')
      ->fields('nonorg', array('NON_ORGANIZATION_NAME'))
      ->leftJoin('CORE_TERM', 'term', 'term.TERM_NAME = coursehistory.TERM')
      ->fields('term', array('FINANCIAL_AID_YEAR', 'END_DATE'))
      ->groupBy('coursehistory.LEVEL')
      ->groupBy('coursehistory.CALENDAR_YEAR')
      ->groupBy('coursehistory.CALENDAR_MONTH')
      ->groupBy('coursehistory.TERM')
      ->orderBy('coursehistory.LEVEL')
      ->orderBy('coursehistory.CALENDAR_YEAR')
      ->orderBy('coursehistory.CALENDAR_MONTH')
      ->orderBy('coursehistory.TERM');
    if ($studentID)
      $terms = $terms->condition('student.STUDENT_ID', $studentID);
    $terms = $terms->execute();
    while ($term = $terms->fetch()) {
      
      // If changing levels, reset all counts
      if (!isset($lastRow['LEVEL']) OR $lastRow['LEVEL'] != $term['LEVEL']) {
        $this->resetAllTotals();
      }
      
      if (!isset($lastRow['FINANCIAL_AID_YEAR']) OR $term['FINANCIAL_AID_YEAR'] != $lastRow['FINANCIAL_AID_YEAR']) {
        $this->resetYTDTotals();
      }
      
      // Changing calendar year or month or term, reset term count
      if (!isset($lastRow['CALENDAR_YEAR']) OR $lastRow['CALENDAR_YEAR'] != $term['CALENDAR_YEAR'] OR
          !isset($lastRow['CALENDAR_MONTH']) OR $lastRow['CALENDAR_MONTH'] != $term['CALENDAR_MONTH'] OR
          !isset($lastRow['TERM']) OR $lastRow['TERM'] != $term['TERM']) {
        $this->resetTermTotals();
      }
      
      $this->setGPARegardless = false;
      if (isset($term['END_DATE'])) {
        if (strtotime($term['END_DATE']) < time()) {
          $this->setGPARegardless = true;
        }
      }

      $totals = $this->calculateTermFromHistory($term['STUDENT_ID'], $term['LEVEL'], $term['CALENDAR_YEAR'], $term['CALENDAR_MONTH'], $term['TERM']);
      
      // Compute GPA
      $this->computeGPAs();

      $this->setGPARegardless = false;
      
      // Write to database
      $this->totals['HEd.Student.CourseHistory.Term.StudentID'] = $term['STUDENT_ID'];
      $this->totals['HEd.Student.CourseHistory.Term.Level'] = $term['LEVEL'];
      $this->totals['HEd.Student.CourseHistory.Term.CalendarYear'] = $term['CALENDAR_YEAR'];
      $this->totals['HEd.Student.CourseHistory.Term.CalendarMonth'] = $term['CALENDAR_MONTH'];
      $this->totals['HEd.Student.CourseHistory.Term.Term'] = $term['TERM'];
    
      $this->updateDatabase($this->totals);
      
    
      $this->resetTermTotals();
      
      // Compute Classes in Progress
      if ($term['FINANCIAL_AID_YEAR']) {
        $coursesInProgress = $this->calculateClassesInProgress($studentID, $term['FINANCIAL_AID_YEAR']);
        if ($coursesInProgress) {
          $this->updateDatabase($this->totals);
        }
  	  }
      
      $lastRow = $term;
    }
    $terms = null;
    unset($terms);
    
    if ($coursesInProgress === false) {
      // Compute Classes in Progress
      $this->resetTermTotals();

      $this->totals['HEd.Student.CourseHistory.Term.StudentStatusID'] = null;
      $coursesInProgressOut = $this->calculateClassesInProgress($studentID, $lastRow['FINANCIAL_AID_YEAR']);
      // Compute GPA
      $this->computeGPAs();
      if ($coursesInProgressOut) 
        $this->updateDatabase($this->totals);
    }
    
  }
  
  private function calculateClassesInProgress($studentID, $financialAidYear = null) {
    
    $coursesInProgress = false;
    $laststudentstatusid = null;
    $lastfinyear = null;

    // Check for schedule
    $classesInProgress = $this->db->db_select('STUD_STUDENT', 'student', array('nolog' => true))
      ->fields('student', array('STUDENT_ID'))
      ->join('STUD_STUDENT_STATUS', 'stustatus', 'stustatus.STUDENT_ID = student.STUDENT_ID')
      ->join('STUD_STUDENT_CLASSES', 'classes', 'classes.STUDENT_STATUS_ID = stustatus.STUDENT_STATUS_ID')
      ->fields('classes', array('STUDENT_STATUS_ID', 'LEVEL', 'CREDITS_ATTEMPTED'))
      ->join('STUD_SECTION', 'section', 'section.SECTION_ID = classes.SECTION_ID')
      ->join('CORE_ORGANIZATION_TERMS', 'orgterms', 'orgterms.ORGANIZATION_TERM_ID = section.ORGANIZATION_TERM_ID')
      ->join('CORE_TERM', 'term', 'term.TERM_ID = orgterms.TERM_ID')
      ->fields('term', array('TERM_NAME', 'END_DATE', 'FINANCIAL_AID_YEAR'))
      ->leftJoin('STUD_STUDENT_COURSE_HISTORY', 'stucoursehistory', 'stucoursehistory.STUDENT_CLASS_ID = classes.STUDENT_CLASS_ID')
      ->condition('stucoursehistory.COURSE_HISTORY_ID', null)
      ->condition('DROPPED', 0)
      ->condition('student.STUDENT_ID', $studentID);
    if ($this->totals['HEd.Student.CourseHistory.Term.StudentStatusID']) {
      $classesInProgress = $classesInProgress->condition('classes.STUDENT_STATUS_ID', $this->totals['HEd.Student.CourseHistory.Term.StudentStatusID']);
    }
    if ($this->totals['HEd.Student.CourseHistory.Term.Level'] != '') {
      $classesInProgress = $classesInProgress->condition('classes.LEVEL', $this->totals['HEd.Student.CourseHistory.Term.Level']);
    }

    $classesInProgress = $classesInProgress->execute();
    while ($class = $classesInProgress->fetch()) {

      if (isset($laststudentstatusid) AND $laststudentstatusid != $class['STUDENT_STATUS_ID']) { $this->resetTermTotals(); }

      if ($lastfinyear != $class['FINANCIAL_AID_YEAR'] AND $financialAidYear != $class['FINANCIAL_AID_YEAR']) { $this->resetYTDTotals(); }
      
      $this->totals['HEd.Student.CourseHistory.Term.TermCreditsAttempted'] += $class['CREDITS_ATTEMPTED'];
      $this->totals['HEd.Student.CourseHistory.Term.YTDCreditsAttempted'] += $class['CREDITS_ATTEMPTED'];
      $this->totals['HEd.Student.CourseHistory.Term.CumCreditsAttempted'] += $class['CREDITS_ATTEMPTED'];
      $this->totals['HEd.Student.CourseHistory.Term.TotalCreditsAttempted'] += $class['CREDITS_ATTEMPTED'];
      
      $this->totals['HEd.Student.CourseHistory.Term.Level'] = $class['LEVEL'];
      $this->totals['HEd.Student.CourseHistory.Term.CalendarYear'] = $class['END_DATE'] ? date('Y', strtotime($class['END_DATE'])) : null;
      $this->totals['HEd.Student.CourseHistory.Term.CalendarMonth'] = $class['END_DATE'] ? date('m', strtotime($class['END_DATE'])) : null;
      $this->totals['HEd.Student.CourseHistory.Term.Term'] = $class['TERM_NAME'];
      $this->totals['HEd.Student.CourseHistory.Term.StudentStatusID'] = $class['STUDENT_STATUS_ID'];
      
      $lastfinyear = $class['FINANCIAL_AID_YEAR'];
      $laststudentstatusid = $class['STUDENT_STATUS_ID'];
      $coursesInProgress = true;
    }
    
    return $coursesInProgress;
  }
  
  private function calculateTermFromHistory($studentID, $level, $calendarYear, $calendarMonth, $term) {
    
    $studentStatusID = null;
    
    $ch = $this->db->db_select('STUD_STUDENT_COURSE_HISTORY', 'coursehistory', array('nolog' => true))
      ->fields('coursehistory', array('ORGANIZATION_ID', 'LEVEL', 'CALC_CREDITS_ATTEMPTED', 'CALC_CREDITS_EARNED', 'GPA_VALUE', 'QUALITY_POINTS', 'CALENDAR_MONTH', 'CALENDAR_YEAR', 'TERM', 'NON_ORGANIZATION_ID', 'TRANSFER_CREDITS', 'INCLUDE_TERM_GPA', 'INCLUDE_CUM_GPA'))
      ->leftJoin('CORE_ORGANIZATION', 'org', 'org.ORGANIZATION_ID = coursehistory.ORGANIZATION_ID')
      ->fields('org', array('ORGANIZATION_NAME'))
      ->leftJoin('CORE_NON_ORGANIZATION', 'nonorg', 'nonorg.NON_ORGANIZATION_ID = coursehistory.NON_ORGANIZATION_ID')
      ->fields('nonorg', array('NON_ORGANIZATION_NAME'))
      ->leftJoin('STUD_STUDENT_CLASSES', 'class', 'class.STUDENT_CLASS_ID = coursehistory.STUDENT_CLASS_ID')
      ->fields('class', array('STUDENT_STATUS_ID'))
      ->condition('coursehistory.STUDENT_ID', $studentID)
      ->condition('coursehistory.LEVEL', $level)
      ->condition('coursehistory.CALENDAR_YEAR', $calendarYear)
      ->condition('coursehistory.CALENDAR_MONTH', $calendarMonth)
      ->condition('coursehistory.TERM', $term)
      ->execute();
    while ($row = $ch->fetch()) {

      $this->totals['HEd.Student.CourseHistory.Term.TermCreditsAttempted'] += $row['CALC_CREDITS_ATTEMPTED'];
      $this->totals['HEd.Student.CourseHistory.Term.TermCreditsEarned'] += $row['CALC_CREDITS_EARNED'];
      if ($row['INCLUDE_TERM_GPA'] == 1) {
        if ($row['GPA_VALUE'] != '' AND $row['TRANSFER_CREDITS'] == 0)
          $this->totals['HEd.Student.CourseHistory.Term.TermHours'] += $row['CALC_CREDITS_ATTEMPTED'];
        $this->totals['HEd.Student.CourseHistory.Term.TermPoints'] += $row['QUALITY_POINTS'];
      }

      $this->totals['HEd.Student.CourseHistory.Term.CumCreditsAttempted'] += $row['CALC_CREDITS_ATTEMPTED'];
      $this->totals['HEd.Student.CourseHistory.Term.CumCreditsEarned'] += $row['CALC_CREDITS_EARNED'];
      if ($row['INCLUDE_CUM_GPA'] == 1) {
        if ($row['GPA_VALUE'] != '' AND $row['TRANSFER_CREDITS'] == 0)
          $this->totals['HEd.Student.CourseHistory.Term.CumHours'] += $row['CALC_CREDITS_ATTEMPTED'];
        $this->totals['HEd.Student.CourseHistory.Term.CumPoints'] += $row['QUALITY_POINTS'];
      }

      $this->totals['HEd.Student.CourseHistory.Term.YTDCreditsAttempted'] += $row['CALC_CREDITS_ATTEMPTED'];
      $this->totals['HEd.Student.CourseHistory.Term.YTDCreditsEarned'] += $row['CALC_CREDITS_EARNED'];
      if ($row['INCLUDE_TERM_GPA'] == 1) {
        if ($row['GPA_VALUE'] != '' AND $row['TRANSFER_CREDITS'] == 0)
          $this->totals['HEd.Student.CourseHistory.Term.YTDHours'] += $row['CALC_CREDITS_ATTEMPTED'];
        $this->totals['HEd.Student.CourseHistory.Term.YTDPoints'] += $row['QUALITY_POINTS'];
      }
    
      if ($row['TRANSFER_CREDITS'] == 1) {
        $this->totals['HEd.Student.CourseHistory.Term.TrnsCreditsAttempted'] += $row['CALC_CREDITS_ATTEMPTED'];
        $this->totals['HEd.Student.CourseHistory.Term.TrnsCreditsEarned'] += $row['CALC_CREDITS_EARNED'];
      } elseif ($row['TRANSFER_CREDITS'] == 0) {
        $this->totals['HEd.Student.CourseHistory.Term.InstCreditsAttempted'] += $row['CALC_CREDITS_ATTEMPTED'];
        $this->totals['HEd.Student.CourseHistory.Term.InstCreditsEarned'] += $row['CALC_CREDITS_EARNED'];
        if ($row['INCLUDE_CUM_GPA'] == 1) {
          if ($row['GPA_VALUE'] != '') {
            $this->totals['HEd.Student.CourseHistory.Term.InstHours'] += $row['CALC_CREDITS_ATTEMPTED'];
            $this->totals['HEd.Student.CourseHistory.Term.InstPoints'] += $row['QUALITY_POINTS']; 
          }
        }
      }
      
      $this->totals['HEd.Student.CourseHistory.Term.TotalCreditsAttempted'] += $row['CALC_CREDITS_ATTEMPTED'];
      $this->totals['HEd.Student.CourseHistory.Term.TotalCreditsEarned'] += $row['CALC_CREDITS_EARNED'];
      if ($row['INCLUDE_TERM_GPA'] == 1) {
        if ($row['GPA_VALUE'] != '' AND $row['TRANSFER_CREDITS'] == 0)
          $this->totals['HEd.Student.CourseHistory.Term.TotalHours'] += $row['CALC_CREDITS_ATTEMPTED'];
        $this->totals['HEd.Student.CourseHistory.Term.TotalPoints'] += $row['QUALITY_POINTS'];
      }
      
      $studentStatusID = $row['STUDENT_STATUS_ID'];
    }
    
    $this->totals['HEd.Student.CourseHistory.Term.StudentStatusID'] = $studentStatusID;
    
    $ch = null;
    unset($ch);
    
  }
  
  private function computeGPAs() {
    // Compute GPAs
    if ($this->totals['HEd.Student.CourseHistory.Term.TermPoints'] > 0) {
      $this->totals['HEd.Student.CourseHistory.Term.TermGPA'] = 
        bcdiv($this->totals['HEd.Student.CourseHistory.Term.TermPoints'], $this->totals['HEd.Student.CourseHistory.Term.TermHours'], 3);
    } elseif ($this->setGPARegardless === true) {
      $this->totals['HEd.Student.CourseHistory.Term.TermGPA'] = 0.0;
    } else {
      $this->totals['HEd.Student.CourseHistory.Term.TermGPA'] = null;
    }
    
    if ($this->totals['HEd.Student.CourseHistory.Term.CumPoints'] > 0) {
      $this->totals['HEd.Student.CourseHistory.Term.CumGPA'] = 
        bcdiv($this->totals['HEd.Student.CourseHistory.Term.CumPoints'], $this->totals['HEd.Student.CourseHistory.Term.CumHours'], 3);
    } elseif ($this->setGPARegardless === true) {
      $this->totals['HEd.Student.CourseHistory.Term.CumGPA'] = 0.0;
    } else {
      $this->totals['HEd.Student.CourseHistory.Term.CumGPA'] = null;
    }
    
    if ($this->totals['HEd.Student.CourseHistory.Term.YTDPoints'] > 0) {
      $this->totals['HEd.Student.CourseHistory.Term.YTDGPA'] = 
        bcdiv($this->totals['HEd.Student.CourseHistory.Term.YTDPoints'], $this->totals['HEd.Student.CourseHistory.Term.YTDHours'], 3);
    } elseif ($this->setGPARegardless === true) {
      $this->totals['HEd.Student.CourseHistory.Term.YTDGPA'] = 0.0;
    } else {
      $this->totals['HEd.Student.CourseHistory.Term.YTDGPA'] = null;
    }
    
    if ($this->totals['HEd.Student.CourseHistory.Term.TrnsPoints'] > 0) {
      $this->totals['HEd.Student.CourseHistory.Term.TrnsGPA'] = 
        bcdiv($this->totals['HEd.Student.CourseHistory.Term.TrnsPoints'], $this->totals['HEd.Student.CourseHistory.Term.TrnsHours'], 3);
    } elseif ($this->setGPARegardless === true) {
      $this->totals['HEd.Student.CourseHistory.Term.TrnsGPA'] = 0.0;
    } else {
      $this->totals['HEd.Student.CourseHistory.Term.TrnsGPA'] = null;
    }
    
    if ($this->totals['HEd.Student.CourseHistory.Term.InstPoints'] > 0) {
      $this->totals['HEd.Student.CourseHistory.Term.InstGPA'] = 
        bcdiv($this->totals['HEd.Student.CourseHistory.Term.InstPoints'], $this->totals['HEd.Student.CourseHistory.Term.InstHours'], 3);
    } elseif ($this->setGPARegardless === true) {
      $this->totals['HEd.Student.CourseHistory.Term.InstGPA'] = 0.0;
    } else {
      $this->totals['HEd.Student.CourseHistory.Term.InstGPA'] = null;
    }
    
    if ($this->totals['HEd.Student.CourseHistory.Term.TotalPoints'] > 0) {
      $this->totals['HEd.Student.CourseHistory.Term.TotalGPA'] = 
        bcdiv($this->totals['HEd.Student.CourseHistory.Term.TotalPoints'], $this->totals['HEd.Student.CourseHistory.Term.TotalHours'], 3);
    } elseif ($this->setGPARegardless === true) {
      $this->totals['HEd.Student.CourseHistory.Term.TotalGPA'] = 0.0;
    } else {
      $this->totals['HEd.Student.CourseHistory.Term.TotalGPA'] = null;
    }
  }
  
  private function resetTermTotals() {
    $this->totals['HEd.Student.CourseHistory.Term.TermCreditsAttempted'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.TermCreditsEarned'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.TermHours'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.TermPoints'] = 0.0;
  }
  
  private function resetYTDTotals() {
    $this->totals['HEd.Student.CourseHistory.Term.YTDCreditsAttempted'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.YTDCreditsEarned'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.YTDHours'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.YTDPoints'] = 0.0;
  }
  
  private function resetAllTotals() {
    
    $this->resetTermTotals();
    $this->resetYTDTotals();
    
    $this->totals['HEd.Student.CourseHistory.Term.CumCreditsAttempted'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.CumCreditsEarned'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.CumHours'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.CumPoints'] = 0.0;
    
    $this->totals['HEd.Student.CourseHistory.Term.InstCreditsAttempted'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.InstCreditsEarned'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.InstHours'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.InstPoints'] = 0.0;
    
    $this->totals['HEd.Student.CourseHistory.Term.TrnsCreditsAttempted'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.TrnsCreditsEarned'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.TrnsHours'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.TrnsPoints'] = 0.0;
    
    $this->totals['HEd.Student.CourseHistory.Term.TotalCreditsAttempted'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.TotalCreditsEarned'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.TotalHours'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.TotalPoints'] = 0.0;
    
    $this->totals['HEd.Student.CourseHistory.Term.ConcentrationCreditsAttempted'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.ConcentrationCreditsEarned'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.ConcentrationHours'] = 0.0;
    $this->totals['HEd.Student.CourseHistory.Term.ConcentrationPoints'] = 0.0;
  }
  
  private function updateDatabase($data) {
    
    if ($data['HEd.Student.CourseHistory.Term.Level'] != '' OR $this->totals['HEd.Student.CourseHistory.Term.CalendarYear'] != '' OR $this->totals['HEd.Student.CourseHistory.Term.CalendarMonth'] != '' OR $this->totals['HEd.Student.CourseHistory.Term.CalendarMonth'] != '' OR  $this->totals['HEd.Student.CourseHistory.Term.Term'] != '' OR $this->totals['HEd.Student.CourseHistory.Term.StudentStatusID'] != '') {
    
      // check if already exists
      $exists = $this->db->db_select('STUD_STUDENT_COURSE_HISTORY_TERMS', 'chterms', array('nolog' => true))
        ->fields('chterms', array('STUDENT_COURSE_HISTORY_TERM_ID'))
        ->condition('chterms.STUDENT_ID', $data['HEd.Student.CourseHistory.Term.StudentID'])
        //->condition($this->db->db_or()->condition(
        //$this->db->db_and()
          ->condition('chterms.LEVEL', $data['HEd.Student.CourseHistory.Term.Level'])
          ->condition('chterms.CALENDAR_YEAR', $data['HEd.Student.CourseHistory.Term.CalendarYear'])
          ->condition('chterms.CALENDAR_MONTH', $data['HEd.Student.CourseHistory.Term.CalendarMonth'])
          ->condition('chterms.TERM', $data['HEd.Student.CourseHistory.Term.Term'])
          //  )
          //->condition('chterms.STUDENT_STATUS_ID', $data['HEd.Student.CourseHistory.Term.StudentStatusID'])
        //)
      ->execute()->fetch();
    
      if ($exists['STUDENT_COURSE_HISTORY_TERM_ID']) {
        // Update existing
        return $this->posterFactory->newPoster()->noLog()->edit('HEd.Student.CourseHistory.Term', $exists['STUDENT_COURSE_HISTORY_TERM_ID'], $data)->process()->getResult();
      } else {
        // Create new
        return $this->posterFactory->newPoster()->noLog()->add('HEd.Student.CourseHistory.Term', 0, $data)->process()->getResult();
      }
    
    }
    
  }
  
}