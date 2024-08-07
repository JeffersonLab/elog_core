<?php

namespace Drupal\elog_core;

use Drupal\Core\Url;

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
  protected $opsShifts = array('OWL','OWL','OWL','OWL','OWL','OWL','OWL',
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
   * @see https://www.drupal.org/forum/support/module-development-and-code-questions/2023-01-29/how-to-render-a-table
   *   TODO caching
   * @see https://www.drupal.org/docs/drupal-apis/render-api/cacheability-of-render-arrays
   * @see https://drupalize.me/tutorial/cache-api-overview?p=2723
   * TODO theming
   */
  public function table(): array {
    $output = [
      'entries' => [
        '#theme' => 'table',
        '#caption' => $this->caption,
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
      ],

    ];
    $output['entries']['#rows'] = [];
    foreach ($this->entries as $entry) {
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
          'data' => $this->attachmentCount($entry) .','.$this->commentCount($entry),
        ],
        [
          'data' => $entry->get('field_logbook')->getString(),
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

  /**
   * The url for the logentry lognumber column
   *
   * Also used for the title column.
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
   * The number of attachments (file & image)
   */
  protected function attachmentCount ($entry): int {
    return  $entry->get('field_attach')->count() + $entry->get('field_image')->count();
  }

  /**
   * The number of attachments (file & image)
   */
  protected function commentCount ($entry): int {
    // The field_logentry_comments seems to return an array of
    // statistics rather than the comments themselves which I guess
    // we'd have to load with some sort of query.  For current purposes
    // the comment_count from those statistics is sufficient.
    return$entry->field_logentry_comments->comment_count;
  }


  /**
   * Get a date string formatted appropriately for the current grouping.
   *
   * When grouping by date or shift, the date column gets abbreviated to
   * display just the time.
   */
  protected function formattedDate($entry) {
    switch (strtoupper($this->groupBy)){
      case 'NONE' :
        return date('Y-m-d H:i', $entry->get($this->tableDate)->getString());
      default:
        return date('H:i', $entry->get($this->tableDate)->getString());
    }
  }

}
