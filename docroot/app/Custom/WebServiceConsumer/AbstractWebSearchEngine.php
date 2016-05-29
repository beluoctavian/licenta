<?php

namespace App\Custom\WebServiceConsumer;

use Carbon\Carbon;

use App\Http\Requests;

abstract class AbstractWebSearchEngine
{
  /**
   * Cache expire in minutes.
   * @var int
   */
  protected $cache_expire = 28800;

  abstract function search($query, $limit, $advanced);

  protected function getWebsiteCacheKey($url) {
    return 'website_' . urlencode($url);
  }

  protected function createAdvancedSearch(&$items) {
    foreach ($items as $key => $item) {
      try {
        $data = \Cache::get($this->getWebsiteCacheKey($item['url']));
        if (empty($data)) {
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $item['url']);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
          $data = curl_exec($ch);
          curl_close($ch);
          $expiresAt = Carbon::now()->addMinutes($this->cache_expire);
          \Cache::put($this->getWebsiteCacheKey($item['url']), $data, $expiresAt);
        }
        $items[$key]['content'] = $data;
      }
      catch (\Exception $e) {
        \Log::error("Could not get full content of website {$item['url']}.\nError: {$e->getMessage()}");
      }
    }
  }
}
