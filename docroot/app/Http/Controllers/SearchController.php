<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
          $results = $bc->search($query, 50, TRUE);
        }
        break;
    }
    return Clusty::groupByCategory($results);
  }

  public function search(Request $request)
  {
    return view('search.home');
    $results = $this->getSearchResults($request);

    $perPage = 10;
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $collection = new Collection($results);
    $currentPageSearchResults = $collection->slice(($currentPage-1) * $perPage, $perPage)->all();
    $paginatedSearchResults = new LengthAwarePaginator($currentPageSearchResults, count($collection), $perPage, $currentPage, [
      'path'  => $request->url(),
      'query' => $request->query(),
    ]);

    return view('search.home')
      ->with('results', $paginatedSearchResults);
  }
}
