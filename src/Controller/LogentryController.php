<?php

namespace Drupal\elog_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\elog_core\LogentryQuery;
use Drupal\elog_core\LogentryTabulator;
use Drupal\node\Entity\Node;
use Drupal\elog_core\Elog;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

class LogentryController extends ControllerBase {

  /**
   * Display the entries of a single logbook.
   */
  public function logbook(string $logbook, Request $request) {
    $query = LogentryQuery::from_request($request);
    $query->set_logbook($logbook);
    $entries = $query->result_nodes();
    $tabulator = new LogentryTabulator($entries);
    return $tabulator->table();
  }

  /**
   * Display entries based on request parameters.
   */
  public function entries(Request $request) {
    $query = LogentryQuery::from_request($request);
    dpm($query->tags);
    dpm(date('Y-m-d',$query->start_date));
    dpm(date('Y-m-d',$query->end_date));
    $entries = $query->result_nodes();
    $tabulator = new LogentryTabulator($entries);
    return $tabulator->table();
  }



}
