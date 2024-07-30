<?php

namespace Drupal\elog_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\elog_core\LogentryTabulator;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

class LogentryController extends ControllerBase {

  /**
   * Display the entries of a single logbook.
   * TODO honor query paramters in request
   */
  public function logbook(string $logbook, Request $request) {
    dpm($request->query->all());
    $term = $this->getLogbookTid($logbook);
    $entries = $this->getLogbookEntries($term->id());
    $tabulator = new LogentryTabulator($entries);
    $tabulator->caption = $term->get('name')->getString();
    return $tabulator->table();

  }

  /**
   * Get the numeric id for given logbook name.
   *
   * @param string $logbook
   *
   * @return int
   */
  protected function getLogbookTid(string $logbook): Term {
    $vid = 'logbooks'; //name of your vocabulary
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->accessCheck(FALSE);  //
    $query->condition('vid', $vid);
    $query->condition('name', $logbook);
    $tids = $query->execute();
    if (empty($tids)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    $term = Term::load(current($tids));
    return $term;    // logbook names are unique so current == only
  }

  /**
   * Get the numeric id for given logbook name.
   *
   * @param string $logbook
   *
   * @return array of logentry nodes
   */
  protected function getLogbookEntries(int $tid): array {
    $vid = 'logbooks'; //name of your vocabulary
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'logentry');
    $query->accessCheck(FALSE);  //
    $nids = $query->execute();
    return Node::loadMultiple($nids);    // logbook names are unique so current == only
  }

}
