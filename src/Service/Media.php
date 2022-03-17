<?php declare(strict_types=1);

namespace Drupal\omnipedia_pwa\Service;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\omnipedia_pwa\Service\MediaInterface;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\omnipedia_media\Utility\SrcSet;
use Drupal\omnipedia_media\Utility\Webp;

/**
 * Service worker media service.
 */
class Media implements MediaInterface {

  /**
   * The Drupal media entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * Service constructor; saves dependencies.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {

    $this->mediaStorage = $entityTypeManager->getStorage('media');

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
      // Vary by a user's permissions.
      'user.permissions',

    ])->addCacheTags([
      // Invalidate whenever the Permissions by Term access changes, which
      // occurs when a user's content permissions change.
      'permissions_by_term:access_result_cache',
    ]);

    /** @var \Drupal\Core\Entity\Query\QueryInterface The media entity query. */
    $query = ($this->mediaStorage->getQuery());

    /** @var string[] Zero or more media IDs (mids) keyed by their latest revision ID. */
    $results = $query->execute();

    foreach ($results as $rid => $mid) {

      /** @var \Drupal\Core\Url URL object for this media. */
      $url = Url::fromRoute('entity.media.canonical', ['media' => $mid]);

      // Check that the current user is allowed to access this media entity.
      // This is necessary as the permissions_by_entity module's access control
      // doesn't seem to be applied at the entity query level.
      if (!$url->access()) {
        continue;
      }

      /** @var \Drupal\Core\GeneratedUrl */
      $generatedUrl = $url->toString(true);

      // Add the media URL.
      $urls[] = $generatedUrl->getGeneratedUrl();

      // Merge in the metadata from the media generated URL. GeneratedUrl is a
      // metadata object (has CacheableMetadata as an ancestor).
      $cacheableMetadata->merge($generatedUrl);

    }

  }

  /**
   * {@inheritdoc}
   *
   * This replaces all image URLs provided to the service worker with WebP
   * versions so that the smallest file size is cached as opposed to enormous
   * PNG files.
   *
   * @see \omnipedia_pwa_pwa_cache_urls_assets_page_alter()
   *   Invoked by this.
   */
  public function alterPageAssetCacheUrls(
    array &$urls, string $pageUrl, \DOMXPath $xpath
  ): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $crawler = new Crawler($xpath->document);

    foreach ($crawler->filter(
      // This searches for <img> elements in <picture> elements that contain a
      // <source type="image/webp">. Note that the '~' combinator only matches
      // siblings after the <source> element in the document order.
      'picture source[type="image/webp"] ~ img'
    ) as $img) {

      /** @var string The default/fallback src. */
      $defaultSrc = $img->getAttribute('src');

      /** @var string|false The default/fallback src converted to a WebP URL or false on failure. */
      $webpSrc = Webp::imageUrlToWebp($defaultSrc);

      if (\is_string($webpSrc)) {

        /** @var int|false */
        $urlsKey = \array_search($defaultSrc, $urls);

        // If $defaultSrc exists in $urls, replace it with the WebP URL.
        if ($urlsKey !== false) {
          $urls[$urlsKey] = $webpSrc;
        }

      }

      /** @var \Symfony\Component\DomCrawler\Crawler */
      $sourceCrawler = (new Crawler($img->parentNode))->filter(
        'source[type="image/webp"]'
      );

      // Add all WebP image URLs from this srcset.
      foreach (SrcSet::parse($sourceCrawler->attr('srcset')) as $item) {

        if (empty($item['url'])) {
          continue;
        }

        $urls[] = $item['url'];

      }

    }

  }

}
