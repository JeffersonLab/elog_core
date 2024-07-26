<?php

namespace Drupal\elog_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

class LogentryController extends ControllerBase {

  /**
   * Display the entries of a single logbook.
   * TODO honor query paramters in request
   * ?start_date=2024-06-22%2000%3A00&end_date=2024-07-27%2000%3A00&logbooks%5B0%5D=1&search_str=&group_by=SHIFT&listing_format=table&entries_per_page=100&form_build_id=form-fIF3BDzUclOdVjap7JR-2VrC1r9CbfrsxQfsxJeoKPA&form_token=Geq3SUv3NYwYE2ip4p29X9kU95ScmCocN46IO_wIys8&form_id=elog_form_advanced_filters&op=Submit
   */
  public function logbook(string $logbook, Request $request) {
    $term = $this->getLogbookTid($logbook);
    $entries = $this->getLogbookEntries($term->id());

    //@link https://www.drupal.org/forum/support/module-development-and-code-questions/2023-01-29/how-to-render-a-table The post.
    $output = [
      '#logbook' => [
        '#term' => $term
      ],
      'entries' => [
        '#theme' => 'table',
        '#caption' => $term->get('name')->getString(),
        '#attributes' => ['class' => 'logbook-listing'],
        '#header' => [
          [
            'data' => 'Lognumber',
          ],
          [
            'data' => 'Date',
          ],
          [
            'data' => 'Author',
          ],
          [
            'data' => 'Title',
          ],
        ],
      ],

    ];
    $output['entries']['#rows'] = [];
    foreach ($entries as $entry) {
      $output['entries']['#rows'][]['data'] = [
        [
          'data' => $entry->get('field_lognumber')->getString(),
          'nid' => $entry->id()
        ],
        ['data' => date('Y-m-d H:i', $entry->get('created')->getString())],
        ['data' => $entry->getOwner()->get('name')->getString()],
        ['data' => $entry->getTitle()],
      ];
    }
    return $output;
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
