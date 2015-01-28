<?php

namespace Kula\Bundle\HEd\OfferingBundle\Chooser;

class RoomsChooser extends \Kula\Component\Chooser\Chooser {
	
	public static function search($q) {
		
		$container = $GLOBALS['kernel']->getContainer();
		
		$query_conditions = new \Kula\Component\Database\Query\Predicate('AND');
		$query_conditions = $query_conditions->predicate('room.ORGANIZATION_TERM_ID', $container->get('kula.focus')->getOrganizationTermIDs());
		
		$query_conditions_or = new \Kula\Component\Database\Query\Predicate('OR');
		$query_conditions_or = $query_conditions_or->predicate('ROOM_NAME', $q.'%', 'LIKE');
		$query_conditions_or = $query_conditions_or->predicate('ROOM_NUMBER', $q.'%', 'LIKE');
		
		$query_conditions = $query_conditions->predicate($query_conditions_or);
		
		$data = array();
		
		$search = self::db()->select('STUD_ROOM', 'room')
			->fields('room', array('ROOM_ID', 'BUILDING', 'ROOM_NAME', 'ROOM_NUMBER'))
			->where($query_conditions)
			->order_by('ROOM_NUMBER', 'ASC');
		$search = $search	->execute();
		while ($row = $search->fetch()) {
			self::addToChooserMenu($row['ROOM_ID'], $row['BUILDING'].' '.$row['ROOM_NUMBER'].' '.$row['ROOM_NAME']);
		}
		
	}
	
	public static function choice($id) {
		$row = self::db()->select('STUD_ROOM', 'room')
			->fields('room', array('ROOM_ID', 'BUILDING', 'ROOM_NAME', 'ROOM_NUMBER'))
			->predicate('room.ROOM_ID', $id)
			->execute()
			->fetch();
		return self::currentValue($row['ROOM_ID'], $row['BUILDING'].' '.$row['ROOM_NUMBER'].' / '.$row['ROOM_NAME']);
	}
	
	public static function searchRoute() {
		return 'sis_offering_room_chooser';
	}
	
}