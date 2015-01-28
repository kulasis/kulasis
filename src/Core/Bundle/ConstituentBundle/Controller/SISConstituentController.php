<?php

namespace Kula\Bundle\Core\ConstituentBundle\Controller;

use Kula\Bundle\Core\KulaFrameworkBundle\Controller\Controller;

class ConstituentController extends Controller {
	
	public function indexAction() {
		$this->authorize();
		$this->setRecordType('CONSTITUENT');
		
		return $this->render('KulaConstituentBundle:Constituent:index.html.twig');
	}
	
	public function combineAndDeleteAction() {
		$this->authorize();
		$this->setRecordType('CONSTITUENT');
		
		//$this->setSubmitMode($this->tpl, 'search');
		
		$constituents = array();
		$combine = $this->request->request->get('combine');
		
		if (isset($combine['CONS_CONSTITUENT']['CONSTITUENT_ID'])) {
			
			$this->db('write')->beginTransaction();
			
			// Student to keep
			$keep_student = $combine['CONS_CONSTITUENT']['CONSTITUENT_ID'];
			// Student to delete
			$delete_student = $this->record->getSelectedRecordID();
			
			// Reassign records in one-to-many tables
			// Delete records in one-to-one tables if record exists
			
			// get deleted CONV_NUMBER
			$deleted_conv_number = $this->db('write')->select('STUD_STUDENT')
				->fields(null, array('CONV_STUDENT_NUMBER'))
				->predicate('STUDENT_ID', $delete_student)
				->execute()->fetch()['CONV_STUDENT_NUMBER'];
			
			// Get CONS_CONSTITUENT.CONSTITUENT_ID FIELD ID
			$field_constituent_id = $this->db('write')->select('CORE_SCHEMA_FIELDS', 'fields')
				->fields('fields', array('SCHEMA_FIELD_ID'))
				->join('CORE_SCHEMA_TABLES', 'tables', null, 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
				->predicate('tables.SCHEMA_TABLE_NAME', 'CONS_CONSTITUENT')
				->predicate('fields.DB_FIELD_NAME', 'CONSTITUENT_ID')
				->execute()->fetch()['SCHEMA_FIELD_ID'];
			
			// Get all one-to-one tables
			$direct_onetoone_result = $this->db('write')->select('CORE_SCHEMA_FIELDS', 'fields')
				->fields('fields', array('SCHEMA_FIELD_ID', 'SCHEMA_TABLE_ID', 'DB_FIELD_NAME'))
				->join('CORE_SCHEMA_TABLES', 'tables', array('SCHEMA_TABLE_NAME'), 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
				->predicate('PARENT_SCHEMA_FIELD_ID', $field_constituent_id)
				->predicate('DB_FIELD_PRIMARY', 'Y')
				->execute();
			while ($direct_onetoone_row = $direct_onetoone_result->fetch()) {
				
				
				// Delete from one-to-one table
				// Check if exists in one-to-one table
				$check_if_exists_direct_onetoone = $this->db('write')->select($direct_onetoone_row['SCHEMA_TABLE_NAME'])
					->fields(null, array($direct_onetoone_row['DB_FIELD_NAME']))
					->predicate($direct_onetoone_row['DB_FIELD_NAME'], $keep_student)
					->execute()->fetch()[$direct_onetoone_row['DB_FIELD_NAME']];
				if ($check_if_exists_direct_onetoone == '') {
					
					// Get delete data
					$old_data = $this->db('write')->select($direct_onetoone_row['SCHEMA_TABLE_NAME'])
						->fields(null, array())
						->predicate($direct_onetoone_row['DB_FIELD_NAME'], $delete_student)
						->execute()->fetch();

					
					if ($old_data[$direct_onetoone_row['DB_FIELD_NAME']] != '') {
						$old_data[$direct_onetoone_row['DB_FIELD_NAME']] = $keep_student;
						// Insert into table
						$this->db('write')->insert($direct_onetoone_row['SCHEMA_TABLE_NAME'])
							->fields($old_data)
								->execute();
					}
				}
				
				// Get children of those tables
				$children_of_onetoone_result = $this->db('write')->select('CORE_SCHEMA_FIELDS', 'fields')
				->fields('fields', array('SCHEMA_FIELD_ID', 'SCHEMA_TABLE_ID', 'DB_FIELD_NAME'))
				->join('CORE_SCHEMA_TABLES', 'tables', array('SCHEMA_TABLE_NAME'), 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
				->predicate('PARENT_SCHEMA_FIELD_ID', $direct_onetoone_row['SCHEMA_FIELD_ID'])
				->execute();
				while ($children_of_onetoone_row = $children_of_onetoone_result->fetch()) {
					// Reassign ID#
					$this->db('write')->update($children_of_onetoone_row['SCHEMA_TABLE_NAME'])
						->fields(array($children_of_onetoone_row['DB_FIELD_NAME'] => $keep_student))
						->predicate($children_of_onetoone_row['DB_FIELD_NAME'], $delete_student)
						->execute();
				}
				// Delete from one-to-one table
				// Check if exists in one-to-one table
				$check_if_exists_direct_onetoone = $this->db('write')->select($direct_onetoone_row['SCHEMA_TABLE_NAME'])
					->fields(null, array($direct_onetoone_row['DB_FIELD_NAME']))
					->predicate($direct_onetoone_row['DB_FIELD_NAME'], $keep_student)
					->execute()->fetch()[$direct_onetoone_row['DB_FIELD_NAME']];
				if ($check_if_exists_direct_onetoone) {
					$this->db('write')->delete($direct_onetoone_row['SCHEMA_TABLE_NAME'])
						->predicate($direct_onetoone_row['DB_FIELD_NAME'], $delete_student)
					  ->execute();
				}
			
			}
			
			$query_conditions = new \Kula\Component\Database\Query\Predicate('OR');
			$query_conditions = $query_conditions->predicate('DB_FIELD_PRIMARY', 'N');
			$query_conditions = $query_conditions->predicate('DB_FIELD_PRIMARY', null);
				
			// Get direct children of one-to-one
			$children_of_cons_onetoone_result = $this->db('write')->select('CORE_SCHEMA_FIELDS', 'fields')
			->fields('fields', array('SCHEMA_FIELD_ID', 'SCHEMA_TABLE_ID', 'DB_FIELD_NAME'))
			->join('CORE_SCHEMA_TABLES', 'tables', array('SCHEMA_TABLE_NAME'), 'tables.SCHEMA_TABLE_ID = fields.SCHEMA_TABLE_ID')
			->predicate('PARENT_SCHEMA_FIELD_ID', $field_constituent_id)
			->predicate($query_conditions)
			->execute();
			while ($children_of_cons_onetoone_row = $children_of_cons_onetoone_result->fetch()) {
				// Reassign ID#
				$update = $this->db('write')->update($children_of_cons_onetoone_row['SCHEMA_TABLE_NAME'])
					->fields(array($children_of_cons_onetoone_row['DB_FIELD_NAME'] => $keep_student))
					->predicate($children_of_cons_onetoone_row['DB_FIELD_NAME'], $delete_student)
				  ->execute();
			}
			
			// Insert into conversion table
			$this->db('write')->insert('CONV_COMBINED')->fields(array(
				'DELETED_CONSTITUENT_ID' => $delete_student,
				'DELETED_CONV_NUMBER' => $deleted_conv_number,
				'MERGED_CONSTITUENT_ID' => $keep_student,
			))->execute();
			
			// Delete from CONS_CONSTITUENT
			$this->db('write')->delete('CONS_CONSTITUENT')
				->predicate('CONSTITUENT_ID', $delete_student)
				->execute();
			$this->flash->add('success', 'Deleted constituent. '.$delete_student.' -> '.$keep_student);
			
			$this->db('write')->commit();
			return $this->forward('sis_constituent_constituent', array('record_type' => 'CONSTITUENT', 'record_id' => ''), array('record_type' => 'CONSTITUENT', 'record_id' => ''));
		}
		
		if ($this->request->request->get('search')) {
			$query = \Kula\Component\Database\Searcher::prepareSearch($this->request->request->get('search'), 'CONS_CONSTITUENT', 'CONSTITUENT_ID');
			$query = $query->fields('CONS_CONSTITUENT', array('CONSTITUENT_ID', 'PERMANENT_NUMBER', 'LAST_NAME', 'FIRST_NAME', 'MIDDLE_NAME', 'BIRTH_DATE', 'GENDER', 'SOCIAL_SECURITY_NUMBER'));
			$query = $query->left_join('STUD_STUDENT', 'stu', null, 'stu.STUDENT_ID = CONS_CONSTITUENT.CONSTITUENT_ID');
			$query = $query->left_join('STUD_STUDENT_STATUS', 'status', array('ORGANIZATION_TERM_ID', 'STUDENT_STATUS_ID'), 'stu.STUDENT_ID = status.STUDENT_ID AND status.ORGANIZATION_TERM_ID IN (' . implode(', ', $this->focus->getOrganizationTermIDs()) . ')');
			$query = $query->order_by('LAST_NAME', 'ASC');
			$query = $query->order_by('FIRST_NAME', 'ASC');
			$query = $query->range(0, 100);
			$constituents = $query->execute()->fetchAll();
		}
		
		return $this->render('KulaConstituentBundle:Constituent:combineAndDelete.html.twig', array('constituents' => $constituents));
	}
	
}
