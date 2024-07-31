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
   * TODO honor query paramters in request
   */
  public function logbook(string $logbook, Request $request) {
    dpm($request->query->all());
    $query = new LogentryQuery();
    $query->add_logbook($logbook);
//    $query->add_tag('Autolog');
    $entries = $query->result_nodes();
    $tabulator = new LogentryTabulator($entries);
    return $tabulator->table();
  }




}
