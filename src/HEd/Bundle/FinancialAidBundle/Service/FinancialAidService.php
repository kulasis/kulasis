<?php

namespace Kula\HEd\Bundle\FinancialAidBundle\Service;

class FinancialAidService {
  
  protected $database;
  
  protected $poster_factory;
  
  protected $record;
  
  protected $session;
  
  public function __construct(\Kula\Component\Database\Connection $db, 
                              \Kula\Component\Database\PosterFactory $poster_factory,
                              $record, 
                              $session) {
    $this->database = $db;
    $this->record = $record;
    $this->poster_factory = $poster_factory;
    $this->session = $session;
  }
  
  public function editAwardedTotal($award_id, $new_amount) {
    
    // Get existing award
    
    // Determine difference to post
    
    // Update award with new amount
    
    // Enter new transaction for batch
    
    
  }
  
}