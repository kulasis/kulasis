<?php

namespace Kula\Core\Bundle\FrameworkBundle\Exception;

class DisplayException extends \Exception {

  private $data;

	public function setData($data) {
    $this->data = $data;
  }

  public function getData() {
    return $this->data;
  }

}