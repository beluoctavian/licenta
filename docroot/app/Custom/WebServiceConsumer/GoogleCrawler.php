<?php

namespace App\Custom\WebServiceConsumer;

use App\Http\Requests;
use Carbon\Carbon;

class GoogleCrawler extends AbstractWebSearchEngine
{
  private $url;
  private $resultsContainerId = 'res';

  /**
   * GoogleCrawler constructor.
   * @param string $url
   */
  public function __construct($url = 'https://www.google.com') {
    $this->url = $url;
  }

  private function buildSearchUrl($query, $start) {
    return $this->url . '/search?q=' . urlencode($query) . '&start=' . $start;
  }

  private function getCacheKey($query, $start) {
    return 'google_' . urlencode($query) . '_' . $start;
  }


  /**
   * Returns Google search results for a specific query.
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
    $start = 0 ;

    while (count($items) < $limit) {
      $contents = \Cache::get($this->getCacheKey($query, $start));
      if (empty($json)) {
        $url = $this->buildSearchUrl($query, $start);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $contents = curl_exec($ch);
        curl_close($ch);
        $redirect_url = NULL;
        if (preg_match('#Location: (.*)#', $contents, $r)) {
          $redirect_url = trim($r[1]);
        }
        $url = $redirect_url ?: $url;
        $contents = file_get_contents($url);
        $expiresAt = Carbon::now()->addMinutes($this->cache_expire);
        \Cache::put($this->getCacheKey($query, $start), $contents, $expiresAt);
      }
      $doc = new \DOMDocument();

      // Just because that: http://php.net/manual/ro/domdocument.loadhtml.php#95463
      libxml_use_internal_errors(TRUE);

      $doc->loadHTML($contents);
      $container = $doc->getElementById($this->resultsContainerId);

      foreach ($container->getElementsByTagName('div') as $div) {
        if (count($items) >= $limit) {
          break;
        }
        /** @var \DOMElement $div */
        if ($div->getAttribute('class') != 'g') {
          continue;
        }
        $item = [];
        /** @var \DOMElement $h3 */
        $h3 = $div->getElementsByTagName('h3')->item(0);
        $a = $h3->firstChild;
        $item['url'] = $item['title'] = $item['summary'] = $item['content'] = NULL;
        $item['url'] = $this->url . $a->getAttribute('href');
        $item['title'] = strip_tags($a->textContent);
        foreach ($div->getElementsByTagName('span') as $span) {
          /** @var \DOMElement $span */
          if ($span->getAttribute('class') != 'st') {
            continue;
          }
          $item['summary'] = trim(preg_replace('/\s\s+/', ' ', strip_tags($span->textContent)));
          break;
        }
        if (!empty($item['url']) && !empty($item['summary'])) {
          $items[] = $item;
        }
      }
      $start += 10;
    }
    if ($advanced === TRUE) {
      $this->createAdvancedSearch($items);
    }

    return $items;
  }
}
