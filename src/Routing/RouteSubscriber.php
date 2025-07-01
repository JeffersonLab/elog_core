<?php

namespace Drupal\elog_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
//    if ($route = $collection->get('entity.taxonomy_term.canonical')) {
//      $route->setDefault('_controller', '\Drupal\elog_core\Controller\TaxonomyTermController:handle');
//    }
  }

}
