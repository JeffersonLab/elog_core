<?php

namespace Drupal\Tests\elog_core\Kernel;

use Carbon\Carbon;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\elog_core\LogentryQuery;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use PHPUnit\Framework\TestCase;

/**
 *
 * @group elog_core
 * @group elog
 */
class LogentryQueryTest  extends KernelTestBase {

  use \Drupal\Tests\user\Traits\UserCreationTrait;
  use \Drupal\Tests\node\Traits\NodeCreationTrait;


  // Some logentries to use for testing query retrieval
  protected $entries = [];

  // Some logook terms for testing queries
  protected $logbooks = [];

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'taxonomy','comment','user',
                              'system','menu_ui','pathauto','path_alias','token',
                               'file','image','filefield_paths','field',
                                'text','filter','htmlawed','editor','link',
                               'elog_core'];


  /**
   * Initialization of the parameters required by the test methods.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installSchema('elog_core',['lognumber_sequence']);
    $this->installSchema('comment',['comment_entity_statistics']);
    $this->installConfig(['field', 'text','node', 'comment', 'user','elog_core','filefield_paths']);

    $this->createLogbooks();
    $this->createEntries();


  }

  // Setup
  public function testQueries(){
      $this->checkDefaultDates();
      $this->checkDateRangeQueries();
      $this->checkLogbookQueries();

  }

  public function checkDefaultDates() {
    $query = new LogentryQuery();

    // With no specific dates, the default range is defined by default_days
    $this->assertEquals(Carbon::tomorrow()->timestamp, $query->endDate);
    $this->assertEquals(Carbon::today()->subtract('days', $query->defaultDays)->timestamp, $query->startDate);

  }


  public function checkDateRangeQueries() {
    $query = new LogentryQuery();
    // With this end date and default of 30 preceding days should catch both example nodes.
    $query->setEndDate('2023-08-15');
    $this->assertCount(2, $query->resultNodes());

    // With this end date only the first node should be found
    $query->setEndDate('2023-08-03');
    $this->assertCount(1, $query->resultNodes());
    $result = current($query->resultNodes());
    $this->assertEquals('Entry 1', $result->getTitle());

    // With this end date which precedes both nodes, none should be found
    $query->setEndDate('2023-07-31');
    $this->assertCount(0, $query->resultNodes());

    // With this start date only the second nodes should be found
    $query->setStartDate('2023-08-02');
    $query->setEndDate('2023-08-15');
    $this->assertCount(1, $query->resultNodes());
    $result = current($query->resultNodes());
    $this->assertEquals('Entry 2', $result->getTitle());


  }

  public function checkLogbookQueries() {
    $query = new LogentryQuery();
    $query->setEndDate('2023-08-15');
    $query->setLogbook('Book1');
    // Both entries are assigned to Book1
    $this->assertCount(2, $query->resultNodes());

    // Only entry 2 is assigned to Book2
    $query->setLogbook('Book2');
    $this->assertCount(1, $query->resultNodes());
  }

  protected function createLogbooks() {
    foreach (['Book1', 'Book2'] as $name){
      $term = Term::create([
        'name' => $name,
        'vid' => 'logbooks',
      ]);
      $term->save();
      $this->logbooks[] = $term;
    }
  }

  protected function createEntries () {
    $user = $this->createUser();

    $node = $node = \Drupal\node\Entity\Node::create([
      'title' => 'Entry 1',
      'type' => 'logentry',
      'uid' => $user->id(),
      'created' => strtotime('2023-08-01 15:30'),
    ]);
    $node->field_logbook = [
      ['target_id' => $this->logbooks[0]->id()],
    ];
    $node->save();

    $this->entries[] = $node;

    $node = $node = \Drupal\node\Entity\Node::create([
      'title' => 'Entry 2',
      'type' => 'logentry',
      'uid' => $user->id(),
      'created' => strtotime('2023-08-04 10:20'),
    ]);
    $node->field_logbook = [
      ['target_id' => $this->logbooks[0]->id()],
      ['target_id' => $this->logbooks[1]->id()],
    ];
    $node->save();
    $this->entries[] = $node;
  }

}
