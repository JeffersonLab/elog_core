<?php

namespace Drupal\elog_core;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Class to create queries for logentries using Drupal Entity API.
 */
class LogentryEntityQuery extends LogentryBaseQuery {

  protected QueryInterface $query;

  /**
   * Build the drupal entity query
   * @see https://www.drupaleasy.com/blogs/ultimike/2020/07/entityquery-examples-everybody
   */
  public function query() : QueryInterface {
    $this->query = \Drupal::entityQuery('node')
      ->condition('type', 'logentry')
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->condition($this->tableDate,[$this->startDate, $this->endDate], 'BETWEEN');


    $this->setPager();
    $this->applyLogbookConditions();
    $this->applyTagConditions();
    return $this->query;
  }

  /**
   * Obtain query results as an array of numeric ids
   */
  public function resultIds() : array {
    return $this->query()->execute();
  }

  /**
   * Apply logbook filter conditions to the query object.
   *
   * @return void
   */
  protected function applyLogbookConditions() {
    // The current behavior is different than D7 logbooks. It will return entries
    // that belong to an excluded logbooks so long as it also belongs to another that
    // is not excluded.
    if (! empty($this->logbooks)){
      $tids = array_keys(array_diff_key($this->logbooks, $this->excludeLogbooks));
      $this->query->condition('field_logbook.entity:taxonomy_term.tid', $tids, 'IN');
    }
    if (! empty($this->excludeLogbooks)){
      $tids = array_keys($this->excludeLogbooks);
      $this->query->condition('field_logbook.entity:taxonomy_term.tid', $tids, 'NOT IN');
    }
  }

  /**
   * Apply logbook filter conditions to the query object.
   *
   * @return void
   */
  protected function applyTagConditions() {
    if (! empty($this->tags)){
      $tids = array_keys($this->tags);
      $this->query->condition('field_tags.entity:taxonomy_term.tid', $tids, 'IN');
    }
  }

  public function __toString(): string {
    return $this->query()->__toString();
  }

}
