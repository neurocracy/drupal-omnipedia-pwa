<?php declare(strict_types=1);

namespace Drupal\omnipedia_pwa\Service;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Service worker content service interface.
 */
interface ContentInterface {

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
   * Alter URLs to be excluded from service worker caching.
   *
   * @param string[] &$urls
   *   Zero or more URLs as strings to never be cached by the service worker.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata &$cacheableMetadata
   *   The cacheable metadata instance for the service worker. Use this to add
   *   or alter the metadata - including cache tags and contexts - based on the
   *   URLs to be excluded.
   *
   * @see \hook_pwa_exclude_urls_alter()
   */
  public function alterExcludeUrls(
    array &$urls, CacheableMetadata &$cacheableMetadata
  ): void;

}
