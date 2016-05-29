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
        $content = \Cache::get($this->getWebsiteCacheKey($item['url']));
        if (empty($content)) {
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $item['url']);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
          $data = curl_exec($ch);
          curl_close($ch);

          // Just because that: http://php.net/manual/ro/domdocument.loadhtml.php#95463
          libxml_use_internal_errors(TRUE);

          $d = new \DOMDocument;
          $mock = new \DOMDocument;
          $d->loadHTML($data);
          $body = $d->getElementsByTagName('body')->item(0);
          foreach ($body->childNodes as $child){
            $mock->appendChild($mock->importNode($child, true));
          }
          $content = trim(preg_replace("/[^0-9a-z]+/i", " ", $mock->textContent));
          $expiresAt = Carbon::now()->addMinutes($this->cache_expire);
          \Cache::put($this->getWebsiteCacheKey($item['url']), $content, $expiresAt);
        }
        $items[$key]['content'] = $content;
      }
      catch (\Exception $e) {
        \Log::error("Could not get full content of website {$item['url']}.\nError: {$e->getMessage()}");
      }
    }
  }
}
