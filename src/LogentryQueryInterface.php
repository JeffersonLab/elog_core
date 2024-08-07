<?php

namespace Drupal\elog_core;

use Symfony\Component\HttpFoundation\Request;

interface LogentryQueryInterface {

  /**
   * Use parameters from an HTTP Request to set query conditions.
   */
  public function applyRequest(Request $request): void;
  /**
   * Obtain query results as array of version and node ids [vid => nid]
   */
  public function resultIds() : array;

  /**
   * Obtain query results as array of logentry Nodes
   */
  public function resultNodes() : array;

}
