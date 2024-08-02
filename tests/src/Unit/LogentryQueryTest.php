<?php

namespace Drupal\Tests\elog_core\Unit;

use Carbon\Carbon;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\elog_core\LogentryQuery;
use PHPUnit\Framework\TestCase;

/**
 *
 * @group elog_core
 * @group elog
 */
class LogentryQueryTest  extends TestCase {


  /**
   * Initialization of the parameters required by the test methods.
   */
  protected function setUp(): void {
    parent::setUp();
//
//    $loggerFactory = $this->getMockBuilder('Psr\Log\LoggerInterface')
//      ->disableOriginalConstructor()
//      ->getMock();
//    $container = new ContainerBuilder();
//    $container->set('logger.factory', $loggerFactory);
//    \Drupal::setContainer($container);
  }

  public function test_dates() {
    $query = new LogentryQuery();

    $this->assertEquals(Carbon::tomorrow()->timestamp, $query->end_date);
    $this->assertEquals(Carbon::today()->subtract('days', $query->default_days)->timestamp, $query->start_date);

  }

}
