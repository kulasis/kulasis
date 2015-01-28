<?php

namespace Kula\Bundle\HEd\CourseHistoryBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class GradingSetupMarkScaleController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->processForm();
		
		$mark_scales = array();
		
			// Get Mark Scales
			$mark_scales = $this->db()->select('STUD_MARK_SCALE')
				->fields(null, array('MARK_SCALE_NAME', 'MARK_SCALE_ID', 'INACTIVE_AFTER', 'AUDIT'))
				->order_by('MARK_SCALE_NAME', 'ASC')
				->execute()->fetchAll();
		
		return $this->render('KulaHEdCourseHistoryBundle:GradingSetupMarkScale:mark_scales.html.twig', array('mark_scales' => $mark_scales));
	}
	
	public function detailAction($mark_scale_id) {
		$this->authorize();
		$this->processForm();
		
		$mark_scale = array();
		
		// Get Mark Scales
		$mark_scale = $this->db()->select('STUD_MARK_SCALE')
			->fields(null, array('MARK_SCALE_NAME', 'MARK_SCALE_ID'))
			->predicate('MARK_SCALE_ID', $mark_scale_id)
			->execute()->fetch();
		
		$marks = array();
		
			// Get Marks
			$marks = $this->db()->select('STUD_MARK_SCALE_MARKS')
				->fields(null, array('MARK_SCALE_MARK_ID', 'SORT', 'MARK', 'GETS_CREDIT', 'GPA_VALUE', 'INACTIVE_AFTER', 'ALLOW_TEACHER', 'ALLOW_COMMENTS', 'REQUIRE_COMMENTS'))
				->predicate('MARK_SCALE_ID', $mark_scale_id)
				->order_by('SORT', 'ASC')
				->execute()->fetchAll();
		
		return $this->render('KulaHEdCourseHistoryBundle:GradingSetupMarkScale:mark_scale_detail.html.twig', array('marks' => $marks, 'mark_scale' => $mark_scale));
	}
}