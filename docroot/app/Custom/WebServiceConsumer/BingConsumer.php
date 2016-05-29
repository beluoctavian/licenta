<?php

namespace App\Custom\WebServiceConsumer;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;

class BingConsumer extends AbstractWebSearchEngine
{
  private $url;
  private $account_key;
  private $customer_id;
  /**
   * Cache expire in minutes.
   * @var int
   */
  private $cache_expire = 28800;

  /**
   * BingConsumer constructor.
   * @param string $url
   */
  public function __construct($url = 'https://api.datamarket.azure.com/Bing/Search/v1/Web') {
    $this->url = $url;
    $this->account_key = env('BING_ACCOUNT_KEY');
    $this->customer_id = env('BING_CUSTOMER_ID');
  }

  private function buildSearchUrl($query, $start) {
    return $this->url . '?Query=%27' . urlencode($query) . '%27&$skip=' . $start . '&Adult=%27Strict%27&$format=json';
  }

  private function makeSearchRequest($query, $start) {
    $process = curl_init($this->buildSearchUrl($query, $start));
    curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($process, CURLOPT_USERPWD,  "{$this->customer_id}:{$this->account_key}");
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
    return curl_exec($process);
  }

  private function getWebsiteCacheKey($url) {
    return 'website_' . urlencode($url);
  }

  private function getCacheKey($query, $start) {
    return 'bing_' . urlencode($query) . '_' . $start;
  }

  /**
   * Returns Bing search results for a specific query.
   *
   * @param string $query
   *  The searched text.
   * @param int $limit
   *  The number of results returned.
   * @param bool $advanced
   *  If set to TRUE, the crawler will create a new request for every result to
   * get the full page content.
   * @return array
   *  An array of arrays containing:
   *    - url: Url to results webpage
   *    - displayUrl: Url display text.
   *    - title: Title of webpage
   *    - summary: Summary of webpage content
   *    - content: Parsed content of webpage if the $advanced parameter is set to
   * TRUE, NULL otherwise
   * @throws \Exception
   */
  public function search($query, $limit = 50, $advanced = FALSE) {
    if (!is_string($query)) {
      throw new \Exception('The query string is not valid.');
    }
    $items = [];
    $start = 0;
    while (count($items) < $limit) {
      $json = \Cache::get($this->getCacheKey($query, $start));
      if (empty($json)) {
        $response = $this->makeSearchRequest($query, $start);
        $json = json_decode($response);
        // 20 days
        $expiresAt = Carbon::now()->addMinutes($this->cache_expire);
        \Cache::put($this->getCacheKey($query, $start), $json, $expiresAt);
      }
      if (!empty($json->d) && !empty($json->d->results)) {
        foreach ($json->d->results as $result) {
          if (count($items) >= $limit) {
            break;
          }
          $item = [];
          $item['url'] = $result->Url;
          $item['displayUrl'] = $result->DisplayUrl;
          $item['title'] = $result->Title;
          $item['summary'] = $result->Description;
          $item['content'] = NULL;
          $items[] = $item;
        }
      }
      $start += 50;
    }
    if ($advanced === TRUE) {
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
    return $items;
  }
}
