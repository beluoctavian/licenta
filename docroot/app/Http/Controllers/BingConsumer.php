<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;

class BingConsumer extends AbstractWebSearchEngine
{
  private $url;
  private $account_key;
  private $customer_id;

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
        dd('pula');
        $response = $this->makeSearchRequest($query, $start);
        $json = json_decode($response);
        // 20 days
        $expiresAt = Carbon::now()->addMinutes(28800);
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
    return $items;
  }
}
