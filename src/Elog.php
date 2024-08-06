<?php

namespace Drupal\elog_core;

use Drupal\taxonomy\Entity\Term;

/**
 * Core functions used throughout the module
 */
class Elog {

  /**
   * Get the logbooks taxonomy term for given logbook name.
   */
  public static function logbookTerm(string|int $logbook): Term|null {
    return self::getTerm($logbook, 'logbooks');
  }

  /**
   * Get the logbooks taxonomy term for given logbook name.
   */
  public static function tagTerm(string|int $tag): Term|null {
    return self::getTerm($tag, 'tags');
  }


  public static function getTerm(string|int $key, string $vocabulary): Term|null {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->accessCheck(FALSE);  //
    $query->condition('vid', $vocabulary);
    if (is_numeric($key)){
      $query->condition('tid', $key);
    }else{
      $query->condition('name', $key);
    }
    $tids = $query->execute();
    if (empty($tids)) {
      return NULL;
    }
    $term = Term::load(current($tids));
    return $term;    // term names are unique in a vocab so current == only
  }
}
