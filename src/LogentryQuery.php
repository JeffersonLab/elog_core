<?php

namespace Drupal\elog_core;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\Sql\Query;
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
  public int $entriesPerPage = 2000;

  /**
   * The tags to query
   */
  public array $tags = [];       // [tid => name]

  /**
   * The date column used for sorting and filtering.
   */
  public string $tableDate = 'created';

  /**
   * tags to exclude from query results
   */
  public array $excludeTags = [];

  /**
   * A list of logbooks to exclude from query results
   */
  public array $excludeBooks = [];

  /**
   * The state of the sticky flag
   */
  public bool $sticky = false;


  /**
   * Earliest entry date to include stored as unix timestamp
   */
  public int $startDate;

  /**
   * Latest entry date to include stored as unix timestamp
   */
  public int $endDate;


  /**
   * Subtracted from end_date to set default start_date.
   * TODO - move to module settings
   */
  public $defaultDays = 30;



  /**
 * Constructs a LogentryQuery object.
   */
  public function __construct() {
    $this->setDefaultDates();
  }

  public function setDefaultDates(){
    $this->endDate = $this->defaultEndDate();
    $this->startDate = $this->defaultStartDate();
  }

  public function defaultEndDate() {
    $d = getdate(); //current date/time
    return mktime(24,0,0,$d['mon'],$d['mday'],$d['year']);
  }

  public function defaultStartDate() {
    $d = getdate(); //current date/time
    return mktime(0,0,0,$d['mon'],$d['mday'] - $this->defaultDays,$d['year']);
  }

  /**
   * Instantiate from HTTP Request parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\elog_core\LogentryQuery
   */
  public static function fromRequest(Request $request): LogentryQuery {
      $query = new static();
      $query->applyRequest($request);
      return $query;
  }

  /**
   * Add a logbook to our filters
   */
  public function addLogbook(Term | int | string $book) {
    if (is_string($book)) {
      $term = Elog::logbookTerm($book);
    }else{
      $term = $this->getTerm($book);
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
  public function setLogbook(Term | int | string $book) {
    $this->logbooks = [];
    $this->addLogbook($book);
  }

  /**
   * Specify a single tag to query
   */
  public function setTag(Term | int | string $tag) {
    $this->tags = [];
    $this->addTag($tag);
  }

  /**
   * Add a tag to our filters
   */
  public function addTag(Term | int | string $tag) {
    if (is_string($tag)) {
      $term = Elog::tagTerm($tag);
    }else{
      $term = $this->getTerm($tag);
    }
    if ($term) {
      $this->tags[$term->id()] = $term->getName();
    }else{
      throw new \Exception('Tag term was not found');
    }
  }

  protected function getTerm(mixed $term) {
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
      ->condition($this->tableDate,[$this->startDate, $this->endDate], 'BETWEEN');


    $this->setPager();
    $this->applyLogbookConditions();
    $this->applyTagConditions();

//    dpm($this->query->__toString());
    return $this->query;
  }

  /**
   * Obtain query results as an array of numeric ids
   */
  public function resultIds() : array {
    return $this->query()->execute();
  }

  /**
   * Obtain query results as an array of node objects
   */
  public function resultNodes() : array {
    return Node::loadMultiple($this->query()->execute());
  }

  /**
   * Apply logbook filter conditions to the query object.
   *
   * @return void
   */
  protected function applyLogbookConditions() {
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
  protected function applyTagConditions() {
    if (! empty($this->tags)){
      $tids = array_keys($this->tags);
      $this->query->condition('field_tags.entity:taxonomy_term.tid', $tids, 'IN');
    }
  }

  /**
   * Sets the pagination limit
   */
  protected function setPager() {
    if ($this->entriesPerPage > 0){
      $this->query->pager($this->entriesPerPage);
    }
  }


  public function applyRequest(Request $request) {
    $this->setStartDate($request->get('start_date'));
    $this->setEndDate($request->get('end_date'));
    if ($request->get('logbooks')){
        $this->setLogbooks($request->get('logbooks'));
    }
    if ($request->get('tags')){
      $this->setTags($request->get('tags'));
    }
  }

  /**
   * Sets a start (min) date for query results
   * The date parameter is interpreted based on its data type
   *   int :  unix timestamp
   *   string: parsed by strtotime
   *   array: ['date'=>str, 'time'=>str]
   */
  public function setStartDate($date) {
    //TODO refactor this d7 code to use Carbon?
    if (is_numeric($date)){
      $this->startDate = $date;
    }elseif (is_string($date) && $date != ''){
      $this->startDate = strtotime($date);
    }else if (is_array($date)){
      if (array_key_exists('date', $date) && array_key_exists('time', $date)){
        if ($date['date']){
          if (! $date['time']){
            $date['time'] = '00:00';
          }
          $this->startDate = strtotime(sprintf("%s %s", $date['date'], $date['time']));
        }
      }
    }else{
      $this->setStartDate($this->autoStartDate($this->endDate));
    }

  }

  /**
   * Sets an end (max) date for query results
   * The date parameter is interpreted based on its data type
   *   int :  unix timestamp
   *   string: parsed by strtotime
   *   array: ['date'=>str, 'time'=>str]
 */
  public function setEndDate($date){
    //TODO refactor this d7 code to use Carbon?
    //TODO refactor out commonality with set_start_date
    if (is_numeric($date)){
      $this->endDate = $date;
    }elseif (is_string($date)){
      $this->endDate = strtotime($date);
    }elseif (is_array($date)){
      if (array_key_exists('date', $date) && array_key_exists('time', $date)){
        $this->endDate = strtotime(sprintf("%s %s", $date['date'], $date['time']));
      }
    }
    // Force the start date to be before end date.
    if ($this->endDate <= $this->startDate){
      $this->setStartDate($this->autoStartDate($this->endDate));
    }
  }

  public function setLogbooks(string | array $logbooks) {
    if (is_string($logbooks)){
      $this->setLogbook($logbooks);
    }else{
      $this->logbooks = [];
      foreach ($logbooks as $logbook) {
        $this->addLogbook($logbook);
      }
    }
  }

  public function setTags(string | array $tags) {
    if (is_string($tags)){
      $this->setTag($tags);
    }else{
      $this->tags = [];
      foreach ($tags as $tag) {
        $this->addTag($tag);
      }
    }
  }

  protected function autoStartDate($end_date){
    $n = $this->defaultDays;
    $start_exact = strtotime("-$n days", $end_date);
    $start_midnight = strtotime(date('Y-m-d 00:00', $start_exact));
    return $start_midnight;
  }

}
