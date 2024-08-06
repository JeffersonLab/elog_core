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
    $query = LogentryQuery::fromRequest($request);
    $query->setLogbook($logbook);
    $entries = $query->resultNodes();
    $tabulator = new LogentryTabulator($entries);
    return $tabulator->table();
  }

  /**
   * Display entries based on request parameters.
   */
  public function entries(Request $request) {
    dpm($this->getLogger('elog'));
    $query = LogentryQuery::fromRequest($request);
    dpm($query->tags);
    dpm(date('Y-m-d',$query->startDate));
    dpm(date('Y-m-d',$query->endDate));
    $entries = $query->resultNodes();
    $tabulator = new LogentryTabulator($entries);
    return $tabulator->table();
  }



}
