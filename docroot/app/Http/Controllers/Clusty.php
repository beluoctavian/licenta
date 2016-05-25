<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class Clusty extends Controller
{
  /**
   * @param array $results
   * @return array
   */
  public static function groupByWebsite(array $results) {
    $groups = [];
    foreach ($results as $result) {
      $url_components = parse_url($result['url']);
      if (empty($groups[$url_components['host']])) {
        $groups[$url_components['host']] = [
          'title' => $url_components['host'],
          'children' => [],
          'weight' => 0,
        ];
      }
      $groups[$url_components['host']]['children'][] = $result;
      $groups[$url_components['host']]['weight']++;
    }
    return array_values($groups);
  }
}
