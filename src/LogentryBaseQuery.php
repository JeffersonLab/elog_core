<?php

namespace Drupal\elog_core;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
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
   * The tags to query
   */
  public array $tags = [];       // [tid => name]

  /**
   * The users to query
   */
  public array $users = [];       // [uid => name]

  /**
   * Pagination limit
   * TODO - move to module settings
   */
  public int $entriesPerPage = 100;

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
  public bool $sticky = FALSE;

  /**
   * Earliest entry date to include stored as unix timestamp
   */
  public int $startDate;

  /**
   * Latest entry date to include stored as unix timestamp
   */
  public int $endDate;

  /**
   * String to use to search title, body, author name, etc.
   */
  public string $searchString = '';

  /**
   * Subtracted from end_date to set default start_date.
   * TODO - move to module settings
   */
  public int $defaultDays = 30;

  /**
   * What database column will be used for sorting
   */
  public string $sortField = 'date';

  /**
   * Should entries be sorted asc or desc
   */
  public string $sortDirection = 'desc';


  /**
   * Constructs a LogentryQuery object.
   */
  public function __construct() {
    $this->setDefaultDates();
  }

  public function setDefaultDates() {
    $this->endDate = $this->defaultEndDate();
    $this->startDate = $this->defaultStartDate();
  }

  public function defaultEndDate() {
    $d = getdate(); //current date/time
    return mktime(24, 0, 0, $d['mon'], $d['mday'], $d['year']);   // 24th hour of today
  }

  public function defaultStartDate() {
    $d = getdate(); //current date/time
    return mktime(0, 0, 0, $d['mon'], $d['mday'] - $this->defaultDays, $d['year']);
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
  public function addLogbook(Term|int|string $book) {
    if (is_string($book)) {
      $term = Elog::logbookTerm($book);
    }
    else {
      $term = $this->getTerm($book);
    }
    if ($term) {
      $this->logbooks[$term->id()] = $term->getName();
    }
    else {
      throw new \Exception('Logbook term was not found');
    }
  }

  /**
   * Exclude a logbook from the results
   */
  public function excludeLogbook(Term|int|string $book) {
    if ($term = $this->getLogbookTerm($book)) {
      $this->excludeLogbooks[$term->id()] = $term->getName();
    }
    else {
      throw new \Exception('Logbook term was not found');
    }
  }

  /**
   * Exclude a tag from the results
   */
  public function excludeTag(Term|int|string $tag) {
    if ($term = $this->getTagTerm($tag)) {
      $this->excludeTags[$term->id()] = $term->getName();
    }
    else {
      throw new \Exception('Tag term was not found');
    }
  }

  protected function getLogbookTerm(Term|int|string $book) {
    if (is_string($book)) {
      return Elog::logbookTerm($book);
    }

    return $this->getTerm($book);
  }

  protected function getTagTerm(Term|int|string $tag) {
    if (is_string($tag)) {
      return Elog::tagTerm($tag);
    }

    return $this->getTerm($tag);
  }

  /**
   * Specify a single user to filter the query
   */
  public function setUser(User|int|string $user): void {
    $this->users = [];
    $this->addUser($user);
  }

  /**
   * Specify a single logbook to query
   */
  public function setLogbook(Term|int|string $book): void {
    $this->logbooks = [];
    $this->addLogbook($book);
  }

  /**
   * Specify a single tag to query
   */
  public function setTag(Term|int|string $tag): void {
    $this->tags = [];
    $this->addTag($tag);
  }

  /**
   * Set the entries per page limit.
   */
  public function setLimit(int $limit): void {
    $this->entriesPerPage = $limit;
  }

  /**
   * Add a tag to our filters
   */
  public function addTag(Term|int|string $tag): void {
    if (is_string($tag)) {
      $term = Elog::tagTerm($tag);
    }
    else {
      $term = $this->getTerm($tag);
    }
    if ($term) {
      $this->tags[$term->id()] = $term->getName();
    }
    else {
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
    return NULL;
  }

  /**
   * Add a user to our filters.
   *
   * A string argument is assumed to be a username value.
   */
  public function addUser(User|int|string $key) {
    if (is_string($key) || is_int($key)) {
      $user = Elog::getUser($key);
    }
    else {
      $user = $key;
    }
    if ($user) {
      $this->users[$user->id()] = $user->getAccountName();
    }
    else {
      throw new \Exception('User not found');
    }
  }

  /**
   * Use parameters from an HTTP Request to set query conditions.
   *
   * The following parameters are recognized from the Drupal 7 logbooks filter
   * form:
   *
   *  start_date = string acceptable to strtotime function
   *  end_date = string acceptable to strtotime function
   *  logbooks[] = logbooks taxonomy term ids or strings
   *  tags[] = tags taxonomy term ids or strings
   *  search_str = string
   *  entries_per_page = integer
   *  sort = asc or desc
   *  order = database column to sort by
   *
   * Note that the logbooks and tags use the php square brackets convention to
   * accept an array of values.
   */
  public function applyRequest(Request $request): void {
    $this->setStartDate($request->get('start_date'));
    $this->setEndDate($request->get('end_date'));
    if ($request->get('logbooks')) {
      $this->setLogbooks($request->get('logbooks'));
    }
    if ($request->get('tags')) {
      $this->setTags($request->get('tags'));
    }
    if ($request->get('search_str')){
      $this->searchString = $request->get('search_str');
    }
    if ($request->get('entries_per_page')){
      $this->entriesPerPage = $request->get('entries_per_page');
    }
    if ($request->get('order')){
      $this->sortField = $request->get('order');
    }
    if ($request->get('sort')){
      $this->sortDirection = $request->get('sort');
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
    if (is_numeric($date)) {
      $this->startDate = $date;
    }
    elseif (is_string($date) && $date != '') {
      $this->startDate = strtotime($date);
    }
    else {
      if (is_array($date)) {
        if (array_key_exists('date', $date) && array_key_exists('time', $date)) {
          if ($date['date']) {
            if (!$date['time']) {
              $date['time'] = '00:00';
            }
            $this->startDate = strtotime(sprintf("%s %s", $date['date'], $date['time']));
          }
        }
      }
      else {
        $this->setStartDate($this->autoStartDate($this->endDate));
      }
    }
  }

  /**
   * Sets an end (max) date for query results
   * The date parameter is interpreted based on its data type
   *   int :  unix timestamp
   *   string: parsed by strtotime
   *   array: ['date'=>str, 'time'=>str]
   */
  public function setEndDate($date) {
    //TODO refactor this d7 code to use Carbon?
    //TODO refactor out commonality with set_start_date
    if (is_numeric($date)) {
      $this->endDate = $date;
    }
    elseif (is_string($date)) {
      $this->endDate = strtotime($date);
    }
    elseif (is_array($date)) {
      if (array_key_exists('date', $date) && array_key_exists('time', $date)) {
        $this->endDate = strtotime(sprintf("%s %s", $date['date'], $date['time']));
      }
    }
    // Force the start date to be before end date.
    if ($this->endDate <= $this->startDate) {
      $this->setStartDate($this->autoStartDate($this->endDate));
    }
  }

  public function setLogbooks(string|int|array $logbooks) {
    if (is_string($logbooks) || is_int($logbooks)) {
      $this->setLogbook($logbooks);
    }
    else {
      $this->logbooks = [];
      foreach ($logbooks as $logbook) {
        $this->addLogbook($logbook);
      }
    }
  }

  public function setTags(string|array $tags) {
    if (is_string($tags)) {
      $this->setTag($tags);
    }
    else {
      $this->tags = [];
      foreach ($tags as $tag) {
        $this->addTag($tag);
      }
    }
  }

  protected function autoStartDate($endDate) {
    $n = $this->defaultDays;
    $startExact = strtotime("-$n days", $endDate);
    $startMidnight = strtotime(date('Y-m-d 00:00', $startExact));
    return $startMidnight;
  }

  /**
   * Get database column to use for sorting.
   */
  protected function orderByColumn() {
    switch (strtolower($this->sortField)) {
      case 'title' : return 'title';
      case 'lognumber': return 'lognumber';
      case 'date':
      default: return $this->tableDate;
    }
  }

  /**
   * Obtain query results as array of version and node ids [vid => nid]
   */
  abstract function resultIds(): array;

  /**
   * Obtain query results as array of logentry Nodes
   */
  public function resultNodes(): array {
    return Node::loadMultiple($this->resultIds());
  }

}
