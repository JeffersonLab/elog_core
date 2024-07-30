<?php

namespace Drupal\elog_core;

use Drupal\Core\Url;

/**
 * Return a list of logentries as a tabular Drupal Render Array.
 *
 *
 */
class LogentryTabulator {

  /**
   * The date shown in table view.
   */
  public string $table_date = 'created';

  /**
   * Groups entries by (DAY, SHIFT, or NONE )
   */
  public $group_by = 'SHIFT';

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
   */
  public function table(): array {
    $output = [
      'entries' => [
        '#theme' => 'table',
        '#caption' => $this->caption,
        '#attributes' => ['class' => 'logbook-listing'],
        // TODO make the columns dynamic
        '#header' => [
          [
            'data' => 'Lognumber',
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
            '#url' => $this->lognumber_url($entry->id()),
            'nid' => $entry->id(),
          ],
        ],
        [
          'data' => $this->formatted_date($entry),
        ],
        [
            'data' => [
              '#type' => 'link',
              '#title' => $entry->getOwner()->get('name')->getString(),
              '#url' => $this->user_url($entry->getOwner()->id()),
              'uid' => $entry->getOwner()->id(),
            ],
        ],
        [
          'data' => [
            '#type' => 'link',
            '#title' => $entry->getTitle(),
            '#url' => $this->lognumber_url($entry->id()),
            'nid' => $entry->id(),
          ],
        ],
      ];
    }
    return $output;
  }

  protected function lognumber_url($nid) {
    return Url::fromRoute('entity.node.canonical', [
      'node' => $nid,
    ]);
  }

  protected function user_url($uid) {
    return Url::fromRoute('entity.user.canonical', [
      'user' => $uid,
    ]);
  }

  protected function formatted_date($entry) {
    switch ($this->group_by){
      case 'None' :
        return date('Y-m-d H:i', $entry->get($this->table_date)->getString());
      default:
        return date('H:i', $entry->get($this->table_date)->getString());
    }
  }

}
