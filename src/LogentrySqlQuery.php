<?php

namespace Drupal\elog_core;

use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Class to create queries for logentries using Drupal SQL select API.
 */
class LogentrySqlQuery extends LogentryBaseQuery {

  protected SelectInterface $query;

  /**
   * Build a Drupal SQL Query object with appropriate conditions applied.
   * TODO string search
   * TODO needs attention search
   */
  public function query(){

    $this->query = $this->baseQuery();
    $this->query->join('node_field_revision','nfr','n.nid = nfr.nid and n.vid = nfr.vid');
    $this->query->addField('nfr','vid','vid');
    $this->query->condition('nfr.status', 0, '>');  //published

    if (! empty($this->users)){
      $this->query->condition('nfr.uid', array_keys($this->users), 'IN');
    }


    //    if (field_info_instance('node', 'field_downtime', 'logentry')){
    //      $this->query->leftJoin('field_data_field_downtime', 'dt', 'n.nid = dt.entity_id');
    //      $this->query->fields('dt', array('field_downtime_time_down'));
    //    }
    //    if (field_info_instance('node', 'field_extern_ref', 'logentry')) {
    //      $this->query->leftJoin('field_data_field_extern_ref', 'xr', "n.nid = xr.entity_id and field_extern_ref_ref_name = 'dtm'");
    //      $this->query->addExpression('COUNT(xr.field_extern_ref_ref_name)', 'dtm_refcount');
    //    }
    //    if (field_info_instance('node', 'field_opspr', 'logentry')) {
    //      //Legacy 6GeV OPS-PR field
    //      $this->query->leftJoin('field_data_field_opspr', 'pr6', 'n.nid = pr6.entity_id');
    //      $this->query->fields('pr6', array('field_opspr_component_id'));
    //    }

    //    // New 12GeV PR entity
    //    if (module_exists('elog_pr')) {
    //      $this->query->leftJoin('elog_pr', 'pr', 'n.nid = pr.prid');
    //      $this->query->fields('pr', array('needs_attention'));
    //    }
    //
    //    //Free form entry makers
    //    $this->query->leftJoin('field_data_field_entrymakers', 'e', 'n.nid = e.entity_id');
    //    $this->query->addExpression('GROUP_CONCAT(DISTINCT(e.field_entrymakers_value))', 'entrymakers');
    //



    //    $this->query->groupBy("l.field_lognumber_value");

    //
    //    // For memory/performance reasons, we don't join and return the
    //    // body unless the field is being searched or has explicitly
    //    // been resquested as output.
    //    if ($this->search_str || in_array('body', $this->fields)) {
    //      $this->query->leftJoin('field_data_body', 'bd', 'n.nid = bd.entity_id');
    //      if (in_array('body', $this->fields)) {
    //        $this->query->fields('bd', array('body_value', 'body_format'));
    //      }
    //    }
    //
    //
    //    if ($this->search_str) {
    //      //$this->query->leftJoin('field_data_body', 'bd', 'n.nid = bd.entity_id');
    //      $or = db_or();
    //
    //      // @todo is there some sort of test to detect if text index exists?
    //      //$or->condition('n.title', '%'.$this->search_str.'%', 'LIKE');
    //      //$or->condition('bd.body_value', '%'.$this->search_str.'%', 'LIKE');
    //
            // TODO ALTER TABLE node ADD FULLTEXT(title, body);
    //      $or->where("match(n.title) against (:str IN NATURAL LANGUAGE MODE)", array(':str' => $this->search_str));
    //      $or->where("match(bd.body_value) against (:str IN NATURAL LANGUAGE MODE)", array(':str' => $this->search_str));
    //
    //      // Wildcard against username
    //      $or->condition('u.name', '%' . db_like($this->search_str) . '%', 'LIKE');
    //
    //      // Wildcard against entrymakers
    //      $or->condition('e.field_entrymakers_value', '%' . db_like($this->search_str) . '%', 'LIKE');
    //
    //      // Wildcard against user fields
    //      $or->condition('u.name', '%' . db_like($this->search_str) . '%', 'LIKE');
    //
    //
    //      //Important!
    //      $this->query->condition($or);
    //    }
    //
    //    if (!empty($this->filters)) {
    //      $this->applyFilters($this->query);
    //    }

    if ($this->startDate) {
      if ($this->tableDate == 'created') {
        $this->query->condition('nfr.created', $this->startDate, '>=');
      }
      else {
        $this->query->condition('nfr.changed', $this->startDate, '>=');
      }
    }

    if ($this->endDate) {
      if ($this->tableDate == 'created') {
        $this->query->condition('nfr.created', $this->endDate, '<=');
      }
      else {
        $this->query->condition('nfr.changed', $this->endDate, '<=');
      }
    }



    // Does the requester want specific logbooks?
    if (! empty($this->logbooks)) {
      $this->query->leftJoin('node__field_logbook', 'bf', 'n.nid = bf.entity_id');
      $this->query->condition('bf.field_logbook_target_id', array_keys($this->logbooks), 'IN');
    }

    // Does the requester want to exclude specific logbooks?
    // Entries in multiple logbooks will be excluded if any one of their logbooks
    // was explicitly added the excludeLogbooks list.
    if (! empty($this->excludeLogbooks)) {
      $subquery = \Drupal::database()->select('node__field_logbook', 'f');
      $subquery->fields('f',['entity_id']);
      $subquery->condition('f.field_logbook_target_id', array_keys($this->excludeLogbooks), 'IN');
      $this->query->condition('n.nid', $subquery, 'NOT IN');
    }

    // Does the requester want specific tags?
    if (! empty($this->tags)) {
      $this->query->leftJoin('node__field_tags', 'tf', 'n.nid = tf.entity_id');
      $this->query->condition('tf.field_tags_target_id', array_keys($this->tags), 'IN');
    }

    // Does the requester want to exclude specific tags?
    // Entries with multiple tags will be excluded if any one of their tags
    // is excluded.
    if (! empty($this->excludeTags)) {
      $subquery = \Drupal::database()->select('node__field_tags', 'f');
      $subquery->fields('f',['entity_id']);
      $subquery->condition('f.field_tags_target_id', array_keys($this->excludeTags), 'IN');
      $this->query->condition('n.nid', $subquery, 'NOT IN');
    }

    //    $this->query->orderBy($this->sort_field, $this->sort_direction);

    return $this->query;
  }

  /**
   * Obtain query results as an array of numeric ids
   */
  public function resultIds() : array {
    $items = [];
    foreach ($this->query()->execute() as $item) {
      $items[$item->vid] = $item->nid;
    }
    return $items;
  }

  /**
   * Get the base query with or without a Pager Extender.
   */
  protected function baseQuery(): SelectInterface {
    $query = \Drupal::database()
      ->select('node', 'n')
      ->fields('n');
    if ($this->entriesPerPage > 0) {
      $query = $query->extend(PagerSelectExtender::class)
        ->limit($this->entriesPerPage);
    }
    return $query;
  }

  public function __toString(): string {
    return $this->query()->__toString();
  }
}
