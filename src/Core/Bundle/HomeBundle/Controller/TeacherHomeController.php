<?php

namespace Kula\Core\Bundle\HomeBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TeacherHomeController extends Controller {
  
  public function homeAction() {
    $this->authorize();
    
    return $this->render('KulaCoreHomeBundle:Home:home.html.twig', array()); 
  }
  
}