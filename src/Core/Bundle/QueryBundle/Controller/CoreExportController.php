<?php

namespace Kula\Core\Bundle\QueryBundle\Controller;

use Kula\Core\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CoreExportController extends Controller {

  public function savedAction() {  
    $this->authorize();
    $this->processForm();
    
    $saved = $this->db()->db_select('CORE_EXPORT_SQL_QUERY')
      ->fields('CORE_EXPORT_SQL_QUERY', array('SQL_QUERY_ID', 'SQL_QUERY_CATEGORY', 'SQL_QUERY_NAME'))
      ->orderBy('SQL_QUERY_CATEGORY', 'ASC')
      ->orderBy('SQL_QUERY_NAME', 'ASC')
      ->execute()->fetchAll();
    
    return $this->render('KulaCoreQueryBundle:CoreExport:saved.html.twig', array('saved' => $saved));
  }
  
  public function saved_detailAction($id) {
    $this->authorize();
    $this->processForm();
    
    $saved = $this->db()->db_select('CORE_EXPORT_SQL_QUERY')
      ->fields('CORE_EXPORT_SQL_QUERY', array('SQL_QUERY_ID', 'SQL_QUERY'))
      ->condition('SQL_QUERY_ID', $id)
      ->orderBy('SQL_QUERY_CATEGORY', 'ASC')
      ->orderBy('SQL_QUERY_NAME', 'ASC')
      ->execute()->fetch();
    
    return $this->render('KulaCoreQueryBundle:CoreExport:saved_detail.html.twig', array('query' => $saved));
  }
  
  public function queryAction() {
    $this->authorize();
    
    $non = $this->request->request->get('non');

    $column_names = array();
    $results = array();
    
    if (isset($non['query'])) {
      
      if (preg_match("/insert /i", $non['query']) OR preg_match("/update /i", $non['query']) || preg_match("/delete /i", $non['query'])) {
        throw new \PDOException('Cannot execute insert, update, or delete commands in export.');
      }
    
      //if (preg_match("/limit /i", $non['query'])) {
      //  throw new \PDOException('Cannot include limit clause in export.');
      //}
      $query_to_execute = $non['query']." LIMIT 25";
      
      $results = $this->db()->db_query($query_to_execute)->fetchAll();
      $column_names = array_keys($results[0]);
      $query = $non['query'];
    } else {
      $query = $this->db()->db_select('CORE_EXPORT_SQL_QUERY')
      ->fields('CORE_EXPORT_SQL_QUERY', array('SQL_QUERY_ID', 'SQL_QUERY'))
      ->condition('SQL_QUERY_ID', $this->request->query->get('id'))->execute()->fetch()['SQL_QUERY'];
    }
    
    return $this->render('KulaCoreQueryBundle:CoreExport:query.html.twig', array('query' => $query, 'results' => $results, 'column_names' => $column_names));  
  }
  
  public function query_downloadAction() {
    $this->authorize();
    $this->formNewWindow();
    
    $non = $this->request->request->get('non');
    
    if (isset($non['query'])) {
      $query = $non['query'];
    } else {
      $query = $this->db()->db_select('CORE_EXPORT_SQL_QUERY')
      ->fields('CORE_EXPORT_SQL_QUERY', array('SQL_QUERY_ID', 'SQL_QUERY'))
      ->condition('SQL_QUERY_ID', $this->request->query->get('id'))->execute()->fetch()['SQL_QUERY'];
    }
    
    $column_names = array();
    $results = array();
    
    return $this->render('KulaCoreQueryBundle:CoreExport:query.html.twig', array('query' => $query, 'results' => $results, 'column_names' => $column_names));  
  }
  
  public function query_download_exportAction() {
    $this->authorize();
    
    $non = $this->request->request->get('non');
    
    $output = '';
    
    $results = $this->db()->db_query($non['query'])->fetchAll();
    
    if ($results) {
      // Generate response
      $response = new Response();

      // Set headers
      $response->headers->set('Cache-Control', 'private');
      $response->headers->set('Pragma', 'private');
      $response->headers->set('Content-Type', 'text/csv');
      //$response->headers->set('Content-Type', 'application/octet-stream');
      $response->headers->set('Content-Disposition', 'attachment; filename=export'.date("YmdHis").'.csv;');
      $response->headers->set('Expires', '0');

      // Send headers before outputting anything
      $response->sendHeaders();
      
      $first_column = true;
      
      if ($columns = array_keys($results[0])) {
        foreach($columns as $column) {
          if ($first_column === true) {
            $output .= '"'.$column.'"';
            $first_column = false;
          } else
            $output .= ',"'.$column.'"';
        }
        $output .= "\r\n";
      }
    
      foreach($results as $row => $row_data) {
        
        foreach($row_data as $key => $value) {
          $row_data[$key] = '"'.$value.'"';
        }
        
        $output .= implode(',', $row_data)."\r\n";
      }
    
      $response->setContent($output);
      return $response;
      
    } else {
      $response = new Response();
      $response->setContent('No records found.');
      return $response;
    }
    
  } 
  
}