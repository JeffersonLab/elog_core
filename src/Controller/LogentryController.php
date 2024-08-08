<?php

namespace Drupal\elog_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\elog_core\LogentryEntityQuery;
use Drupal\elog_core\LogentrySqlQuery;
use Drupal\elog_core\LogentryTabulator;
use Symfony\Component\HttpFoundation\Request;

class LogentryController extends ControllerBase {

  /**
   * Display the entries of a single logbook.
   */
  public function logbook(string $logbook, Request $request) {
    //$query = LogentryEntityQuery::fromRequest($request);
    $query = LogentrySqlQuery::fromRequest($request);
    $query->setLogbook($logbook);
    dpm($query->__toString());
    $entries = $query->resultNodes();
    $tabulator = new LogentryTabulator($entries);
    $tabulator->groupBy='SHIFT';
    return $tabulator->table();
  }

  /**
   * Display the entries of a single tag.
   */
  public function tag(string $tag, Request $request) {
    //$query = LogentryEntityQuery::fromRequest($request);
    $query = LogentrySqlQuery::fromRequest($request);
    $query->setTag($tag);
    dpm($query->__toString());
    $entries = $query->resultNodes();
    $tabulator = new LogentryTabulator($entries);
    $tabulator->groupBy='SHIFT';
    return $tabulator->table();
  }

  /**
   * Display entries based on request parameters.
   */
  public function entries(Request $request) {
    dpm($this->getLogger('elog'));
    $query = LogentryEntityQuery::fromRequest($request);

    dpm(date('Y-m-d',$query->startDate));
    dpm(date('Y-m-d',$query->endDate));
    $query->excludeLogbook('ELOG');

    dpm(count($query->resultIds()));
    $entries = $query->resultNodes();
    $tabulator = new LogentryTabulator($entries);
    return $tabulator->table();
  }



}
