<?php

namespace Drupal\elog_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class TaxonomyTermController extends ControllerBase{

  /**
   * Display the entries of a single logbook.
   */
  public function handle(Request $request) {
    dpm($request->get('taxonomy_term'));

    $listing['content'] = [
      '#type' => 'markup',
      '#markup' => '<p>The taxonomy term code goes here.</p>',
    ];

    return $listing;
  }
}
