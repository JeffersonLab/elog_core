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
  public static function logbook_term(string $logbook): Term|null {
    return self::get_term($logbook, 'logbooks');
  }

  /**
   * Get the logbooks taxonomy term for given logbook name.
   */
  public static function tag_term(string $tag): Term|null {
    return self::get_term($tag, 'tags');
  }


  public static function get_term(string $name, string $vocabulary): Term|null {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->accessCheck(FALSE);  //
    $query->condition('vid', $vocabulary);
    $query->condition('name', $name);
    $tids = $query->execute();
    if (empty($tids)) {
      return NULL;
    }
    $term = Term::load(current($tids));
    return $term;    // logbook names are unique so current == only
  }
}
