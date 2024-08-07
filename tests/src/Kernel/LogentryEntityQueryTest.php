<?php

namespace Drupal\Tests\elog_core\Kernel;

use Carbon\Carbon;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\elog_core\LogentryEntityQuery;
use Drupal\elog_core\LogentryQueryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use PHPUnit\Framework\TestCase;

/**
 *
 * @group elog_core
 * @group elog
 */
class LogentryEntityQueryTest  extends LogentryQueryTest {


  public function newLogentryQuery(): LogEntryQueryInterface {
    return new LogentryEntityQuery();
  }





  function checkExcludeLogbooks(): void{
    $query = $this->newLogentryQuery();
    // The EntityQuery includes entries belonging to the excluded
    // logbook so long as the also belong to at least one included
    // logbook.
    $query->excludeLogbook('Book1');
    $this->assertCount(2, $query->resultNodes());
  }


}
