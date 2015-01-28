<?php

namespace Kula\Bundle\HEd\OfferingBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class RequirementsController extends Controller {
	
	public function requirement_groupsAction($type, $id) {
		$this->authorize();
		$this->processForm();
		
		$requirement_groups = array();
		
			// Get Requirements
			$requirement_groups = $this->db()->select('STUD_DEGREE_REQ_GRP')
				->fields(null, array('DEGREE_REQ_GRP_ID', 'GROUP_NAME', 'START_TERM_ID', 'END_TERM_ID', 'CREDITS_REQUIRED', 'ELECTIVE', 'CONCENTRATION_ID'))
				->order_by('GROUP_NAME', 'ASC');
			if ($type == 'degree')
				$requirement_groups = $requirement_groups->predicate('DEGREE_ID', $id);
			elseif ($type == 'major')
				$requirement_groups = $requirement_groups->predicate('MAJOR_ID', $id);
			elseif ($type == 'minor')
				$requirement_groups = $requirement_groups->predicate('MINOR_ID', $id);
			elseif ($type == 'concentration')
				$requirement_groups = $requirement_groups->predicate('CONCENTRATION_ID', $id);
			
			$requirement_groups = $requirement_groups->execute()->fetchAll();
		
		return $this->render('KulaHEdOfferingBundle:Requirements:requirement_groups.html.twig', array('type' => $type, 'id' => $id, 'requirement_groups' => $requirement_groups));
	}
	
	public function requirement_group_coursesAction($id) {
		$this->authorize();
		$this->processForm();
		
		$requirement_groups = $this->db()->select('STUD_DEGREE_REQ_GRP')
			->fields(null, array('MINOR_ID', 'MAJOR_ID', 'DEGREE_ID', 'CONCENTRATION_ID'))
			->predicate('DEGREE_REQ_GRP_ID', $id)
			->order_by('GROUP_NAME', 'ASC')
			->execute()->fetch();
		
		if ($requirement_groups['DEGREE_ID']) {
			$type = 'degree';
			$degree_id = $requirement_groups['DEGREE_ID'];
	  } elseif ($requirement_groups['MAJOR_ID']) {
			$type = 'major';
			$degree_id = $requirement_groups['MAJOR_ID'];
	  } elseif ($requirement_groups['MINOR_ID']) {
			$type = 'minor';
			$degree_id = $requirement_groups['MINOR_ID'];
		} elseif ($requirement_groups['CONCENTRATION_ID']) {
			$type = 'concentration';
			$degree_id = $requirement_groups['CONCENTRATION_ID'];
		}
		$requirement_grp_courses = array();
		
		// Get Courses
		$requirement_grp_courses = $this->db()->select('STUD_DEGREE_REQ_GRP_CRS', 'reqgrpcrs')
				->fields('reqgrpcrs', array('DEGREE_REQ_GRP_CRS_ID', 'DEGREE_REQ_GRP_ID', 'COURSE_ID', 'REQUIRED', 'SHOW_AS_OPTION'))
				->join('STUD_COURSE', 'course', null, 'course.COURSE_ID = reqgrpcrs.COURSE_ID')
				->predicate('DEGREE_REQ_GRP_ID', $id)
				->order_by('COURSE_NUMBER', 'ASC')
				->order_by('COURSE_TITLE', 'ASC')
				->execute()->fetchAll();
		
		return $this->render('KulaHEdOfferingBundle:Requirements:requirement_groups_courses.html.twig', array('type' => $type, 'degree_id' => $degree_id, 'requirement_group_id' => $id, 'requirement_grp_courses' => $requirement_grp_courses));
	}
	
	public function requirement_group_courses_equivalentAction($id) {
		$this->authorize();
		$this->processForm();
		
		$requirement_group_courses_equivalents = array();
		
		$requirement_groups = $this->db()->select('STUD_DEGREE_REQ_GRP_CRS')
			->fields(null, array('DEGREE_REQ_GRP_ID'))
			->predicate('DEGREE_REQ_GRP_CRS_ID', $id)
			->execute()->fetch();
		
		// Get Courses
		$requirement_grp_courses_equivalents = $this->db()->select('STUD_DEGREE_REQ_GRP_CRS_EQUV', 'reqgrpcrsequv')
				->fields('reqgrpcrsequv', array('DEGREE_REQ_GRP_CRS_ID', 'COURSE_ID'))
				->join('STUD_COURSE', 'course', null, 'course.COURSE_ID = reqgrpcrsequv.COURSE_ID')
				->predicate('DEGREE_REQ_GRP_CRS_ID', $id)
				->order_by('COURSE_NUMBER', 'ASC')
				->order_by('COURSE_TITLE', 'ASC')
				->execute()->fetchAll();
		
		return $this->render('KulaHEdOfferingBundle:Requirements:requirement_groups_courses_equivalent.html.twig', array('requirement_group_course_id' => $requirement_groups['DEGREE_REQ_GRP_ID'], 'requirement_grp_courses_equivalents' => $requirement_grp_courses_equivalents));
	}
	
}