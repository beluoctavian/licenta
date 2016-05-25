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

  public function search(Request $request)
  {
    $query = $request->get('q');
    $gc = new GoogleCrawler();
    $results = [];
    if (!empty($query)) {
      $results = $gc->search($query, 20);
    }
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
