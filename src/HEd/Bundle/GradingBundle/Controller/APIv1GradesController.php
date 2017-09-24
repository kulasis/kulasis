<?php

namespace Kula\HEd\Bundle\GradingBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\APIController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kula\Core\Bundle\FrameworkBundle\Exception\DisplayException;

class APIv1GradesController extends APIController {

  public function getStudentGradesAction($student_id, $org, $term) {

    // Check for authorized access to constituent
    $this->authorizeConstituent($student_id);

    $data = array();

    return $this->JSONResponse($data);
  }

}