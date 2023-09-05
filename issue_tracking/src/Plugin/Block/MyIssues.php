<?php

namespace Drupal\issue_tracking\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'MyIssues' block.
 *
 * @Block(
 *   id = "MyIssues",
 *   admin_label = @Translation("My Issues"),
 *   category = @Translation("issue_tracking")
 * )
 */
class MyIssues extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Tne entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // get the latest 3 issues ids
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $query = $nodeStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', '1')
      ->condition('type', 'issue_tracking_issue')
      ->condition('field_assignee', $this->currentUser->id())
      ->sort('nid', 'DESC')->range(0, 3)
      ->execute();

    $results = [];
    // load the issues and add the data inside the results array
    $issues = $nodeStorage->loadMultiple($query);
    foreach ($issues as $issue) {
      $id = $issue->id();
      $title = $issue->title->value;
      $results[] = [
        'id' => $id,
        'title' => $title,
      ];
    }
    return [
      '#theme' => 'my_issues',
      '#issues' => $results,
    ];
  }

}
