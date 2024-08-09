<?php

namespace Drupal\elog_core;

use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

/**
 * Turn list of logentries into a Drupal Render Array table representation.
 *
 */
class LogentryTabulator {

  /**
   * The date shown in table view.
   */
  public string $tableDate = 'created';

  /**
   * Groups entries by (DAY, SHIFT, or NONE )
   */
  public $groupBy = 'NONE';

  /**
   * A map of which hours belong to which shift.
   * Useful when grouping by shifts.
   */
  protected $opsShifts = array(
    'OWL','OWL','OWL','OWL','OWL','OWL','OWL',
    'DAY','DAY','DAY','DAY','DAY','DAY','DAY','DAY',
    'SWING','SWING','SWING','SWING','SWING','SWING','SWING','SWING',
    'OWL');


  /**
   * Caption for the table
   */
  public $caption = "Log Entries";

  /**
   * The logentry node objects
   */
  public $entries = [];

  public function __construct(array $entries) {
    $this->entries = $entries;
  }

  /**
   * Get the table render array for the entries.
   *
   * @see https://www.drupal.org/forum/support/module-development-and-code-questions/2023-01-29/how-to-render-a-table
   *   TODO caching
   * @see https://www.drupal.org/docs/drupal-apis/render-api/cacheability-of-render-arrays
   * @see https://drupalize.me/tutorial/cache-api-overview?p=2723
   * TODO theming
   */
  public function table(): array {
    // Without grouping return render array of a single flat table
    if ($this->groupBy == 'NONE') {
      $output = $this->tableOfEntries($this->entries);
    }
    else {
      // When grouping is in effect return a render array containing multiple tables.
      foreach ($this->byShift($this->entries) as $heading => $entries) {
        $output[] = $this->tableOfEntries($entries, $heading);
      }
    }
    $output['pager'] = [
      '#type' => 'pager',
    ];
    return $output;
  }

  protected function tableOfEntries(array $entries, string $heading = ''): array {
    if (empty($entries)) {
      return $this->emptyElement();
    }
    $output = [];
    if ($heading) {
      $output['heading'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $heading,
      ];
    }
    $output['entries'] =
      [
        '#theme' => 'table',
        '#attributes' => ['class' => 'logbook-listing'],
        // TODO make the columns dynamic
        // TODO flags column (comments, needs attention, attachments)
        '#header' => [
          [
            'data' => 'Lognumber',
          ],
          [
            'data' => 'Flags',
          ],
          [
            'data' => 'Logbooks',
          ],
          [
            'data' => 'Date',
          ],
          [
            'data' => 'Author',
          ],
          [
            'data' => 'Title',
          ],
        ],
      ];
    $output['entries']['#rows'] = [];
    foreach ($entries as $entry) {
      $output['entries']['#rows'][]['data'] = [
        [
          'data' => [
            '#type' => 'link',
            '#title' => $entry->get('field_lognumber')->getString(),
            '#url' => $this->lognumberUrl($entry->id()),
            '#nid' => $entry->id(),
          ],
        ],
        [
          'data' => $this->attachmentCount($entry) . ',' . $this->commentCount($entry),
        ],
        [
          'data' => $this->formattedLogbooks($entry),
        ],
        [
          'data' => $this->formattedDate($entry),
        ],
        [
          'data' => [
            '#type' => 'link',
            '#title' => $entry->getOwner()->get('name')->getString(),
            '#url' => $this->authorUrl($entry->getOwner()->id()),
            '#uid' => $entry->getOwner()->id(),
          ],
        ],
        [
          'data' => [
            '#type' => 'link',
            '#title' => $entry->getTitle(),
            '#url' => $this->lognumberUrl($entry->id()),
            '#nid' => $entry->id(),
          ],
        ],
      ];
    }

    return $output;
  }

  protected function emptyElement(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => t('No entries'),
    ];
  }

  /**
   * The url to a logentry node using its canonical path.
   */
  protected function lognumberUrl($nid) {
    return Url::fromRoute('entity.node.canonical', [
      'node' => $nid,
    ]);
  }

  /**
   * The url for the logentry author column
   */
  protected function authorUrl($uid) {
    return Url::fromRoute('entity.user.canonical', [
      'user' => $uid,
    ]);
  }

  /**
   * The url for the logentry logbook column
   *
   */
  protected function logbookUrl($book) {
    return Url::fromRoute('elog_core.logbook', [
      'logbook' => $book,
    ]);
  }

  /**
   * The number of attachments (file & image)
   */
  protected function attachmentCount($entry): int {
    return $entry->get('field_attach')->count() + $entry->get('field_image')
        ->count();
  }

  /**
   * The number of attachments (file & image)
   */
  protected function commentCount($entry): int {
    // The field_logentry_comments seems to return an array of
    // statistics rather than the comments themselves which I guess
    // we'd have to load with some sort of query.  For current purposes
    // the comment_count from those statistics is sufficient.
    return $entry->field_logentry_comments->comment_count;
  }

  /**
   * Return logbooks formatted as a list render array containing logbook
   * link items.
   */
  protected function formattedLogbooks($entry): array {
    $elements = [
      '#theme' => 'item_list',
      '#type' => 'ul',
    ];
    foreach (array_values($entry->get('field_logbook')->getValue()) as $list) {
      foreach ($list as $item) {
        $term = Term::load($item);
        $elements['#items'][] = [
          '#type' => 'link',
          '#title' => $term->getName(),
          '#url' => $this->logbookUrl($term->getName()),
          '#tid' => $term->id(),
        ];
      }
    }
    return $elements;
  }

  /**
   * Get a date string formatted appropriately for the current grouping.
   *
   * When grouping by date or shift, the date column gets abbreviated to
   * display just the time.
   */
  protected function formattedDate($entry) {
    switch (strtoupper($this->groupBy)) {
      case 'NONE' :
        return date('Y-m-d H:i', $entry->get($this->tableDate)->getString());
      default:
        return date('H:i', $entry->get($this->tableDate)->getString());
    }
  }

  protected function byShift($entries) {
    $groupedItems = [];
    foreach ($entries as $entry) {
      $timestamp = $entry->get($this->tableDate)->getString();
      $hour = date('G', $timestamp);
      $shift = $this->opsShifts[$hour];
      if ($hour == 23) {
        $day = date("l (d-M-Y)", $timestamp + 3601);
      }
      else {
        $day = date("l (d-M-Y)", $timestamp + 3601);
      }
      $key = "$shift $day";
      $groupedItems[$key][] = $entry;
    }
    dpm($groupedItems);
    return $groupedItems;
  }
}
