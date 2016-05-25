<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

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
      $results = $gc->search($query);
    }
    return view('search.home')
      ->with('results', $results);
  }
}
