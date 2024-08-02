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
  public array $logbooks = [];   // [tid => name]

  /**
   * Pagination limit
   * TODO - move to module settings
   */
  public int $entries_per_page = 2000;

  /**
   * The tags to query
   */
  public array $tags = [];       // [tid => name]

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
   * Earliest entry date to include stored as unix timestamp
   */
  public int $start_date;

  /**
   * Latest entry date to include stored as unix timestamp
   */
  public int $end_date;


  /**
   * Subtracted from end_date to set default start_date.
   * TODO - move to module settings
   */
  public $default_days = 30;




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
    $this->set_default_dates();

  }

  public function set_default_dates(){
    $this->end_date = $this->default_end_date();
    $this->start_date = $this->default_start_date();
  }

  public function default_end_date() {
    $d = getdate(); //current date/time
    return mktime(24,0,0,$d['mon'],$d['mday'],$d['year']);
  }

  public function default_start_date() {
    $d = getdate(); //current date/time
    return mktime(0,0,0,$d['mon'],$d['mday'] - $this->default_days,$d['year']);
  }

  /**
   * Instantiate from HTTP Request parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\elog_core\LogentryQuery
   */
  public static function from_request(Request $request): LogentryQuery {
      $query = new static();
      $query->apply_request($request);
      return $query;
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
   * Specify a single logbook to query
   */
  public function set_logbook(Term | int | string $book) {
    $this->logbooks = [];
    $this->add_logbook($book);
  }

  /**
   * Specify a single tag to query
   */
  public function set_tag(Term | int | string $tag) {
    $this->tags = [];
    $this->add_tag($tag);
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
      ->sort('created', 'DESC')
      ->condition($this->table_date,[$this->start_date, $this->end_date], 'BETWEEN');


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


  public function apply_request(Request $request) {
    $this->set_start_date($request->get('start_date'));
    $this->set_end_date($request->get('end_date'));
    if ($request->get('logbooks')){
        $this->set_logbooks($request->get('logbooks'));
    }
    if ($request->get('tags')){
      $this->set_tags($request->get('tags'));
    }
  }

  /**
   * Sets a start (min) date for query results
   * The date parameter is interpreted based on its data type
   *   int :  unix timestamp
   *   string: parsed by strtotime
   *   array: ['date'=>str, 'time'=>str]
   */
  public function set_start_date($date) {
    //TODO refactor this d7 code to use Carbon?
    if (is_numeric($date)){
      $this->start_date = $date;
    }elseif (is_string($date) && $date != ''){
      $this->start_date = strtotime($date);
    }else if (is_array($date)){
      if (array_key_exists('date', $date) && array_key_exists('time', $date)){
        if ($date['date']){
          if (! $date['time']){
            $date['time'] = '00:00';
          }
          $this->start_date = strtotime(sprintf("%s %s", $date['date'], $date['time']));
        }
      }
    }else{
      $this->set_start_date($this->auto_start_date($this->end_date));
    }
    if ($this->start_date > $this->end_date){
      $this->logger->debug('Ignored invalid "From" start date after requested "To" end date!', 'error');
    }
  }

  /**
   * Sets an end (max) date for query results
   * The date parameter is interpreted based on its data type
   *   int :  unix timestamp
   *   string: parsed by strtotime
   *   array: ['date'=>str, 'time'=>str]
 */
  public function set_end_date($date){
    //TODO refactor this d7 code to use Carbon?
    //TODO refactor out commonality with set_start_date
    if (is_numeric($date)){
      $this->end_date = $date;
    }elseif (is_string($date)){
      $this->end_date = strtotime($date);
    }elseif (is_array($date)){
      if (array_key_exists('date', $date) && array_key_exists('time', $date)){
        $this->end_date = strtotime(sprintf("%s %s", $date['date'], $date['time']));
      }
    }
    // Force the start date to be before end date.
    if ($this->end_date <= $this->start_date){
      $this->set_start_date($this->auto_start_date($this->end_date));
    }
  }

  public function set_logbooks(string | array $logbooks) {
    if (is_string($logbooks)){
      $this->set_logbook($logbooks);
    }else{
      $this->logbooks = [];
      foreach ($logbooks as $logbook) {
        $this->add_logbook($logbook);
      }
    }
  }

  public function set_tags(string | array $tags) {
    if (is_string($tags)){
      $this->set_tag($tags);
    }else{
      $this->logbooks = [];
      foreach ($tags as $tag) {
        $this->add_tag($tag);
      }
    }
  }

  protected function auto_start_date($end_date){
    $n = $this->default_days;
    $start_exact = strtotime("-$n days", $end_date);
    $start_midnight = strtotime(date('Y-m-d 00:00', $start_exact));
    return $start_midnight;
  }

}
