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
  public function getSearchResults(Request $request)
  {
    $query = trim($request->get('q'));
    if (empty($query)) {
      return [];
    }
    $number = $request->get('n') ?: 10;
    $engine = $request->get('engine') ?: 'bing';
    $advanced = $request->get('advanced') ? TRUE : FALSE;

    $results = [];
    switch ($engine) {
      case 'google':
        $gc = new GoogleCrawler();
        if (!empty($query)) {
          $results = $gc->search($query, $number, $advanced);
        }
        break;
      default:
        $bc = new BingConsumer();
        if (!empty($query)) {
          $results = $bc->search($query, $number, $advanced);
        }
        break;
    }
    $clusty = new Clusty();
    $clusters = $clusty->groupByCategory($results);
    return response()->json($clusters);
  }

  public function search(Request $request)
  {
    $results = $this->getSearchResults($request);
    return view('search.home');
  }
}
