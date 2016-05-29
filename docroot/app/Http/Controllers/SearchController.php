<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Custom\WebServiceConsumer\GoogleCrawler;
use App\Custom\WebServiceConsumer\BingConsumer;
use App\Custom\Classification\Clusty;

class SearchController extends Controller
{
  public function getIndex()
  {
    return view('search.home');
  }

  /**
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Pagination\LengthAwarePaginator
   * @throws \Exception
   */
  public function getSearchResults(Request $request, $engine = 'bing')
  {
    $query = $request->get('q');
    $results = [];
    switch ($engine) {
      case 'google':
        $gc = new GoogleCrawler();
        if (!empty($query)) {
          $results = $gc->search($query, 20);
        }
        break;
      default:
        $bc = new BingConsumer();
        if (!empty($query)) {
          $results = $bc->search($query, 100, TRUE);
        }
        break;
    }
    return Clusty::groupByCategory($results);
  }

  public function search(Request $request)
  {
    $results = $this->getSearchResults($request);
    return view('search.home');
  }
}
