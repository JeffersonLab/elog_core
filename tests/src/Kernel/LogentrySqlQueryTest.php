<?php

namespace Drupal\Tests\elog_core\Kernel;

use Drupal\elog_core\LogentryEntityQuery;
use Drupal\elog_core\LogentryQueryInterface;
use Drupal\elog_core\LogentrySqlQuery;

class LogentrySqlQueryTest  extends LogentryQueryTestBase {

  public function newLogentryQuery(): LogEntryQueryInterface {
    return new LogentrySqlQuery();
  }

  function checkExcludeLogbooks(): void{
    $query = $this->newLogentryQuery();
    // Set date range that spans all test data
    $query->setStartDate('2023-08-01');
    $query->setEndDate('2023-09-15');
    // The EntityQuery excludes entries belonging to the excluded
    // logbook even if they also belong to an included logbook.
    $query->excludeLogbook('Book1');
    $this->assertCount(1, $query->resultNodes());
  }

  function checkExcludeTags(): void{
    $query = $this->newLogentryQuery();
    // Set date range that spans all test data
    $query->setStartDate('2023-08-01');
    $query->setEndDate('2023-09-15');
    // The EntityQuery includes entries belonging to the excluded
    // tag so long as the also belong to at least one included
    // tag.
    $query->excludeTag('Tag1');
    $this->assertCount(1, $query->resultNodes());
  }
}
