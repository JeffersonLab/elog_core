<?php

namespace Drupal\elog_core;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

/**
 *  Core functionality for logentry queries.
 */
abstract class LogentryBaseQuery implements LogentryQueryInterface {

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
  public array $excludeLogbooks = [];

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
   */
  public static function fromRequest(Request $request): LogentryQueryInterface {
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
   * Exclude a logbook from the results
   */
  public function excludeLogbook(Term | int | string $book) {
    if ($term = $this->getLogbookTerm($book)) {
      $this->excludeLogbooks[$term->id()] = $term->getName();
    }else{
      throw new \Exception('Logbook term was not found');
    }
  }

  /**
   * Exclude a tag from the results
   */
  public function excludeTag(Term | int | string $tag) {
    if ($term = $this->getTagTerm($tag)) {
      $this->excludeTags[$term->id()] = $term->getName();
    }else{
      throw new \Exception('Tag term was not found');
    }
  }


  protected function getLogbookTerm(Term | int | string $book) {
    if (is_string($book)) {
      return Elog::logbookTerm($book);
    }

    return $this->getTerm($book);
  }

  protected function getTagTerm(Term | int | string $tag) {
    if (is_string($tag)) {
      return Elog::tagTerm($tag);
    }

    return $this->getTerm($tag);
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
   * Use parameters from an HTTP Request to set query conditions.
   */
  public function applyRequest(Request $request): void {
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

  /**
   * Sets the pagination limit
   */
  protected function setPager() {
    if ($this->entriesPerPage > 0){
      $this->query->pager($this->entriesPerPage);
    }
  }

  /**
   * Obtain query results as array of version and node ids [vid => nid]
   */
  abstract function resultIds() : array;

  /**
   * Obtain query results as array of logentry Nodes
   */
  public function resultNodes() : array {
    return Node::loadMultiple($this->resultIds());
  }

}
