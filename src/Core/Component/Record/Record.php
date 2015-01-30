<?php

namespace Kula\Core\Component\Record;

abstract class Record {
	
	protected $session;
	protected $focus;
	
	public function __construct($db, $session, $focus) {
    $this->db = $db;
    $this->session = $session;
    $this->focus = $focus;
	}
  
  public function db() {
    return $this->db;
  }
	
}