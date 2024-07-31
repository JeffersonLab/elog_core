<?php

namespace Drupal\elog_core;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

/**
 * Query the database to retrieve logentry nodes.
 */
class LogentryQuery implements LogentryQueryInterface {


  protected QueryInterface $query;

  /**
   * The logbooks to query
   *
   */
  public array $logbooks;   // [tid => name]

  /**
   * Pagination limit
   */
  public int $entries_per_page = 100;

  /**
   * The tags to query
   */
  public array $tags;       // [tid => name]

  /**
   * The date column used for sorting and filtering.
   */
  public string $table_date = 'created';

  /**
   * tags to exclude from query results
   */
  public array $exclude_tags = [];

  /**
   * A list of logbooks to exclude from query results
   */
  public array $exclude_books = [];

  /**
   * The state of the sticky flag
   */
  public bool $sticky = false;



  /**
   * A logger instance.
   */
  protected \Psr\Log\LoggerInterface $logger;


  /**
 * Constructs a LogentryQuery object.
   *
   * @param LoggerChannelFactory $logger
   *   A logger instance.
   */
  public function __construct() {
    $this->logger = \Drupal::logger('elog');
  }

  /**
   * Instantiate from HTTP Request parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\elog_core\LogentryQuery
   */
  public static function from_request(Request $request): LogentryQuery {

  }

  /**
   * Add a logbook to our filters
   */
  public function add_logbook(Term | int | string $book) {
    if (is_string($book)) {
      $term = Elog::logbook_term($book);
    }else{
      $term = $this->get_term($book);
    }
    if ($term) {
      $this->logbooks[$term->id()] = $term->getName();
    }else{
      throw new \Exception('Logbook term was not found');
    }
  }

  /**
   * Add a tag to our filters
   */
  public function add_tag(Term | int | string $tag) {
    if (is_string($tag)) {
      $term = Elog::tag_term($tag);
    }else{
      $term = $this->get_term($tag);
    }
    if ($term) {
      $this->tags[$term->id()] = $term->getName();
    }else{
      throw new \Exception('Tag term was not found');
    }
  }

  protected function get_term(mixed $term) {
    if (is_a(Term::class, $term)) {
      return $term;
    }
    if (is_numeric($term)) {    // assume tid
      return Term::load($term);
    }
    return null;
  }
  /**
   * Build the drupal entity query
   * @see https://www.drupaleasy.com/blogs/ultimike/2020/07/entityquery-examples-everybody
   */
  public function query() : QueryInterface {
    $this->query = \Drupal::entityQuery('node')
      ->condition('type', 'logentry')
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');

    $this->set_pager();
    $this->apply_logbook_conditions();
    $this->apply_tag_conditions();

    dpm($this->query->__toString());
    return $this->query;
  }

  /**
   * Obtain query results as an array of numeric ids
   */
  public function result_ids() : array {
    return $this->query()->execute();
  }

  /**
   * Obtain query results as an array of node objects
   */
  public function result_nodes() : array {
    return Node::loadMultiple($this->query()->execute());
  }

  /**
   * Apply logbook filter conditions to the query object.
   *
   * @return void
   */
  protected function apply_logbook_conditions() {
    if (! empty($this->logbooks)){
      $tids = array_keys($this->logbooks);
      $this->query->condition('field_logbook.entity:taxonomy_term.tid', $tids, 'IN');
    }
  }

  /**
   * Apply logbook filter conditions to the query object.
   *
   * @return void
   */
  protected function apply_tag_conditions() {
    if (! empty($this->tags)){
      $tids = array_keys($this->tags);
      $this->query->condition('field_tags.entity:taxonomy_term.tid', $tids, 'IN');
    }
  }

  /**
   * Sets the pagination limit
   */
  protected function set_pager() {
    if ($this->entries_per_page > 0){
      $this->query->pager($this->entries_per_page);
    }
  }


}
