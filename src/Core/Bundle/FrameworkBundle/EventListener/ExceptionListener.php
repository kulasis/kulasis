<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kula\Core\Bundle\FrameworkBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Kula\Core\Bundle\FrameworkBundle\Exception\NotAuthorizedException;
use Kula\Core\Component\DB\PosterException;
use Kula\Core\Component\Database\IntegrityConstraintViolationException;
use Kula\Core\Component\Database\DatabaseExceptionWrapper;


/**
 * ExceptionListener.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExceptionListener implements EventSubscriberInterface
{
    public function __construct($container = null)
    {
        $this->container = $container;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
      $exception = $event->getException();
      $request = $event->getRequest();
      
      if ($exception instanceof PosterException) { // $exception->getFields()
        $response = new JsonResponse(array('type' => 'form_error', 'message' => $exception->getMessage(), 'fields' => null), 200, array('X-Status-Code' => 200));
      } elseif ($exception instanceof \PDOException OR $exception instanceof IntegrityConstraintViolationException OR $exception instanceof DatabaseExceptionWrapper) {
        $response = new JsonResponse(array('type' => 'form_error', 'message' => $exception->getMessage()), 200, array('X-Status-Code' => 200));
      } elseif ($exception instanceof NotAuthorizedException) {
        $response = new RedirectResponse('/login');
      } else {
        if (!$exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        // Error message to be displayed, logged, or mailed
        $error_message = "\nUNCAUGHT EXCEPTION:\nTEXT: ". $exception->getMessage() .
                           "\nLOCATION: ".$exception->getFile().", line " .
                           $exception->getLine() .", at " . date('F j, Y, g:i a') .
                           "\nShowing backtrace:\n".$exception->getTraceAsString()."\n\n" .
                           "\n" . print_r($_SESSION, true);
        // Email the error details, in case SEND_ERROR_MAIL is true
        if ($this->container->getParameter('exception_send_email') == true)
          error_log($error_message, 1, $this->container->getParameter('exception_to_email'), "From: " . $this->container->getParameter('exception_from_email') . "\r\nTo: " . $this->container->getParameter('exception_to_email'));
        }
      }
        
      
      if (isset($response))
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => array('onKernelException', -100),
        );
    }


}
