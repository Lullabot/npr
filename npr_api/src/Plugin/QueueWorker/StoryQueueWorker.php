<?php

namespace Drupal\npr_api\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\npr_pull\NprPullClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes story data from the NPR API and create story nodes.
 *
 * Queue items are added by cron processing.
 *
 * @QueueWorker(
 *   id = "npr_api.queue.story",
 *   title = @Translation("NPR API story processor"),
 *   cron = {"time" = 120}
 * )
 *
 * @see npr_pull_cron()
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see \Drupal\Core\Annotation\Translation
 */
class StoryQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * NPR API pull client.
   *
   * @var \Drupal\npr_pull\NprPullClient
   */
  private $nprPullClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerInterface $logger,
    NprPullClient $npr_pull_client
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->nprPullClient = $npr_pull_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.npr_api'),
      $container->get('npr_pull.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item): void {
    // TODO: Get this from config.
    $published = TRUE;
    $this->nprPullClient->addOrUpdateNode($item, $published);
  }

}
