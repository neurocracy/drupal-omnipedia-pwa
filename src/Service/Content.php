<?php declare(strict_types=1);

namespace Drupal\omnipedia_pwa\Service;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\omnipedia_pwa\Service\ContentInterface;

/**
 * Service worker content service.
 */
class Content implements ContentInterface {

  /**
   * The Drupal node entity storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Service constructor; saves dependencies.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {

    $this->nodeStorage = $entityTypeManager->getStorage('node');

  }

  /**
   * {@inheritdoc}
   *
   * @see \omnipedia_pwa_pwa_cache_urls_alter()
   *   Invoked by this.
   */
  public function alterCacheUrls(
    array &$urls, CacheableMetadata &$cacheableMetadata
  ): void {

    $cacheableMetadata->addCacheContexts([
      // Vary by a user's permissions and node grants.
      'user.permissions',
      'user.node_grants:view',

    ])->addCacheTags([
      // Invalidate whenever the Permissions by Term access changes, which
      // occurs when a user's content permissions change.
      'permissions_by_term:access_result_cache',
    ]);

    /** @var \Drupal\Core\Entity\Query\QueryInterface The node query; note that this obeys access checking for the current user by default. */
    $query = ($this->nodeStorage->getQuery());

    /** @var string[] Zero or more node IDs (nids) keyed by their latest revision ID. */
    $results = $query->execute();

    foreach ($results as $rid => $nid) {

      /** @var \Drupal\Core\Url URL object for this node. */
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nid]);

      // Check access on the URL just in case. Access should be checked by the
      // entity query so this is mainly a back up.
      if (!$url->access()) {
        continue;
      }

      /** @var \Drupal\Core\GeneratedUrl */
      $generatedUrl = $url->toString(true);

      // Add the node's URL.
      $urls[] = $generatedUrl->getGeneratedUrl();

      // Merge in the metadata from the node's generated URL. GeneratedUrl is a
      // metadata object (has CacheableMetadata as an ancestor).
      $cacheableMetadata->merge($generatedUrl);

    }

  }

  /**
   * {@inheritdoc}
   *
   * @see \omnipedia_pwa_pwa_exclude_urls_alter()
   *   Invoked by this.
   */
  public function alterExcludeUrls(
    array &$urls, CacheableMetadata &$cacheableMetadata
  ): void {

    foreach ([
      // Random page route is excluded from caching.
      ['route' => 'omnipedia_menu.random_page', 'parameters' => []],
    ] as $item) {

      /** @var \Drupal\Core\Url URL object for this route. */
      $url = Url::fromRoute($item['route'], $item['parameters']);

      /** @var \Drupal\Core\GeneratedUrl */
      $generatedUrl = $url->toString(true);

      // Add the route's URL to the excludes.
      $urls[] = $generatedUrl->getGeneratedUrl();

      // Merge in the metadata from the route's generated URL. GeneratedUrl is a
      // metadata object (has CacheableMetadata as an ancestor).
      $cacheableMetadata->merge($generatedUrl);

    }

  }

}
