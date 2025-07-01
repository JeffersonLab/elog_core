<?php

namespace Drupal\elog_core;

use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service for issuing log numbers
 *
 */
class LogNumberService {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a LogNumberService object.
   *
   * @param LoggerChannelFactory $logger
   *   A logger instance.
   */
  public function __construct(LoggerChannelFactory $loggerFactory) {
    $this->logger = $loggerFactory->get('elog');
  }

  //TODO ensure unique index in database to force lognumber uniqueness
  public function nextLogNumber() {
    $connection = Database::getConnection();
    // @todo review https://api.drupal.org/api/drupal/core%21includes%21database.inc/function/db_transaction/8.2.x
    $transaction = $connection->startTransaction();
    $this->logger->info('howdy from nextLogNumber');
    try {
      // Insert a temporary record to get a new unique serial value.
      $log_number = $connection->insert('lognumber_sequence')
        ->fields(['log_number' => NULL])
        ->execute();

      // If there's a reason why it's come back undefined, reset it.
      $log_number = $log_number ?? 0;

      // Delete the temporary record.
      $connection->delete('lognumber_sequence')
        ->condition('log_number', $log_number, '=')
        ->execute();
      // No transaction commit?  I guess it happens when transaction goes out of scope?

    }
    catch (\Exception $e) {
      $transaction->rollback();
      $this->logger->error($e);
      throw $e;
    }

    // Return the new unique serial value.
    return $log_number;
  }

  public static function create(ContainerInterface $container) {
    // TODO: Implement create() method.
  }

}
