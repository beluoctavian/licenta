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
      $host_components = explode('.', $url_components['host']);
      $host = array_pop($host_components);
      $host = array_pop($host_components) . '.' . $host;
      if (empty($groups[$host])) {
        $groups[$host] = [
          'title' => $host,
          'children' => [],
          'weight' => 0,
        ];
      }
      $groups[$host]['children'][] = $result;
      $groups[$host]['weight']++;
    }
    return array_values($groups);
  }
}
