<?php

namespace Kula\Bundle\Core\SchoolBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class SchoolSetupController extends Controller {
	
	public function generalAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('SCHOOL_TERM', null, 
		array('CORE_ORGANIZATION_TERMS' =>
			array('ORGANIZATION_ID' => $this->focus->getSchoolIDs(),
						'TERM_ID' => $this->focus->getTermID()
					 )
		     )
		);
		
		$schoolterm = array();
		
		if ($this->record->getSelectedRecordID()) {
			
			$schoolterm = $this->db()->select('STUD_SCHOOL_TERM', 'schoolterm')
				->predicate('SCHOOL_TERM_ID', $this->record->getSelectedRecordID())
				->execute()
				->fetch();
			
			if ($schoolterm['SCHOOL_TERM_ID'] == null) {
				// Create poster and record
				new \Kula\Component\Database\Poster(
					array('STUD_SCHOOL_TERM' => 
						array(0 => 
							array(
								'SCHOOL_TERM_ID' => $this->record->getSelectedRecordID()
							)
						)
					)
				);
			}
		}
		
		return $this->render('KulaSchoolBundle:SchoolSetup:general.html.twig', array('schoolterm' => $schoolterm));
		
	}
	
	public function levelsAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('SCHOOL_TERM', null, 
		array('CORE_ORGANIZATION_TERMS' =>
			array('ORGANIZATION_ID' => $this->focus->getSchoolIDs(),
						'TERM_ID' => $this->focus->getTermID()
					 )
		     )
		);

		$levels = array();
		if ($this->record->getSelectedRecordID()) {
			
			$levels = $this->db()->select('STUD_SCHOOL_TERM_LEVEL', 'school_term_level')
				->predicate('school_term_level.ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
				->order_by('level')
				->execute()->fetchAll();
			
		}
		
		return $this->render('KulaSchoolBundle:SchoolSetup:levels.html.twig', array('levels' => $levels));
		
	}
	
	public function gradelevelsAction() {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('SCHOOL_TERM', null, 
		array('CORE_ORGANIZATION_TERMS' =>
			array('ORGANIZATION_ID' => $this->focus->getSchoolIDs(),
						'TERM_ID' => $this->focus->getTermID()
					 )
		     )
		);
		
		$gradelevels = array();
		if ($this->record->getSelectedRecordID()) {
			
			$gradelevels = $this->db()->select('STUD_SCHOOL_TERM_GRADE_LEVEL', 'school_term_grade_level')
				->predicate('school_term_grade_level.ORGANIZATION_TERM_ID', $this->record->getSelectedRecordID())
				->order_by('LEVEL')
				->order_by('GRADE')
				->order_by('MAX_HOURS')
				->execute()->fetchAll();
			
		}
		
		return $this->render('KulaSchoolBundle:SchoolSetup:gradelevels.html.twig', array('gradelevels' => $gradelevels));
		
	}
	
	public function fteAction($level_id) {
		$this->authorize();
		$this->processForm();
		$this->setRecordType('SCHOOL_TERM', null, 
		array('CORE_ORGANIZATION_TERMS' =>
			array('ORGANIZATION_ID' => $this->focus->getSchoolIDs(),
						'TERM_ID' => $this->focus->getTermID()
					 )
		     )
		);
		$fte = array();
		
		$fte = $this->db()->select('STUD_SCHOOL_TERM_LEVEL_FTE', 'school_term_level_fte')
				->predicate('school_term_level_fte.SCHOOL_TERM_LEVEL_ID', $level_id)
				->order_by('FTE')
				->order_by('CREDIT_TOTAL')
				->execute()->fetchAll();
		
		return $this->render('KulaSchoolBundle:SchoolSetup:fte.html.twig', array('fte' => $fte, 'school_term_level_id' => $level_id));
	}
	
}