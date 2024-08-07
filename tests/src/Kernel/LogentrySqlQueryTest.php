<?php

namespace Drupal\Tests\elog_core\Kernel;

use Drupal\elog_core\LogentryEntityQuery;
use Drupal\elog_core\LogentryQueryInterface;
use Drupal\elog_core\LogentrySqlQuery;

class LogentrySqlQueryTest  extends LogentryQueryTest {

  public function newLogentryQuery(): LogEntryQueryInterface {
    return new LogentrySqlQuery();
  }

  function checkExcludeLogbooks(): void{
    $query = $this->newLogentryQuery();
    // The EntityQuery excludes entries belonging to the excluded
    // logbook even if they also belong to an included logbook.
    $query->excludeLogbook('Book1');
    $this->assertCount(1, $query->resultNodes());
  }

}
