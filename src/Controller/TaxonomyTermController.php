<?php

namespace Drupal\elog_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\elog_core\LogentrySqlQuery;
use Drupal\elog_core\LogentryTabulator;
use Symfony\Component\HttpFoundation\Request;

class TaxonomyTermController extends ControllerBase{

  /**
   * Display the entries of a single logbook.
   */
  public function handle(Request $request) {

    // If the vocabulary isn't tags or logbooks, we'll just let our
    // parent class handle it.
    $term = $request->get('taxonomy_term');
    $vocabulary = $term->get('vid')->first()->target_id;
    if (! in_array($vocabulary, ['tags','logbooks'])) {
      $listing['content'] = [
        '#type' => 'markup',
        '#markup' => "<p>{$vocabulary->get('name')->value}</p>",
      ];

      return $listing;
    }

    dpm($request);
    dpm($request->get('taxonomy_term'));

    $query = LogentrySqlQuery::fromRequest($request);

    // Explicitly set the logbook or tag to match the taxonomy term
    // route being viewed.
    if ($vocabulary == 'logbooks') {
      $query->setLogbook($term->get('name')->value);
    }
    if ($vocabulary == 'tags') {
      $query->setTag($term->get('name')->value);
    }

    $entries = $query->resultNodes();
    dpm($query->__toString());
    $tabulator = new LogentryTabulator($entries);
    $tabulator->groupBy = $request->get('groupBy', 'SHIFT');
    return $tabulator->table();


  }
}
