<?php declare(strict_types=1);

namespace Drupal\omnipedia_pwa\Service;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Service worker media service interface.
 */
interface MediaInterface {

  /**
   * Alter URLs to be cached by the service worker.
   *
   * @param string[] &$urls
   *   Zero or more URLs as strings to be cached by the service worker.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata &$cacheableMetadata
   *   The cacheable metadata instance for the service worker. Use this to add
   *   or alter the metadata - including cache tags and contexts - based on the
   *   URLs added.
   *
   * @see \hook_pwa_cache_urls_alter()
   */
  public function alterCacheUrls(
    array &$urls, CacheableMetadata &$cacheableMetadata
  ): void;

  /**
   * Alter asset URLs to be cached for a given page by the service worker.
   *
   * @param array &$urls
   *   Array of asset URLs for the page.
   *
   * @param string $pageUrl
   *   The URL of the page that $urls corresponds to.
   *
   * @param \DOMXPath $xpath
   *   XPath object for the page.
   *
   * @see \hook_pwa_cache_urls_assets_page_alter()
   */
  public function alterPageAssetCacheUrls(
    array &$urls, string $pageUrl, \DOMXPath $xpath
  ): void;

}
