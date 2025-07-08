<?php

namespace Drupal\elog_core\Controller;

use Drupal;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\elog_core\LogentrySqlQuery;
use Drupal\elog_core\LogentryTabulator;
use Drupal\views\Routing\ViewPageController;


class TaxonomyTermController extends ViewPageController{

  /**
   * Over-ride the display of taxonomy terms in the logbooks and tags vocabularies
   * while letting the default behavior persist for other vocabularies.
   *
   * The site below was helpful for figuring out how to accomplish the task:
   * @see https://wiki.cbeier.net/en/webworking/cms/drupal/drupal8/snippets/use_different_views_for_various_vocabularies
   */
  public function handle($view_id, $display_id, RouteMatchInterface $route_match) {

    // If the vocabulary isn't tags or logbooks, we'll just let our
    // parent class handle it.
    $term = $route_match->getParameter('taxonomy_term');
    $vocabulary = $term->get('vid')->first()->target_id;
    if (! in_array($vocabulary, ['tags','logbooks'])) {
      return parent::handle($view_id, $display_id, $route_match);
    }

    // We need the global request object so that we can extract additional
    // url parameters that might apply to our query.
    $request = Drupal::request();
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

    $tabulator = new LogentryTabulator($entries);
    $tabulator->groupBy = $request->get('groupBy', 'SHIFT');
    return $tabulator->table();


  }
}
