<?php

namespace App\Http\Controllers;

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
   *    - title: Title of webpage
   *    - summary: Summary of webpage content
   *    - content: Parsed content of webpage if the $advanced parameter is set to
   * TRUE, NULL otherwise
   * @throws \Exception
   */
  public function search($query, $limit = 10, $advanced = FALSE) {
    if (!is_string($query)) {
      throw new \Exception('The query string is not valid.');
    }
    $items = [];
    $start = 0;
    $response = $this->makeSearchRequest($query, $start);
    $json = json_decode($response);
    return $items;
  }
}