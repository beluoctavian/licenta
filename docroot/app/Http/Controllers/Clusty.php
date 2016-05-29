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
  public static function groupByWebsite(array $results)
  {
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

  public static function classifyText($text)
  {
    $text = trim(preg_replace("/[^0-9a-z]+/i", " ", $text));
    $words = explode(' ', $text);
    $categories = [
      'other' => 0,
    ];
    foreach ($words as $word) {
      $word = strtolower((string) $word);
      if (strlen($word) > 3) {
        if (empty($categories[$word])) {
          $categories[$word] = 0;
        }
        $categories[$word] ++;
      }
    }
    asort($categories, SORT_NUMERIC);
    $categories = array_reverse($categories);
    return key($categories);
  }

  public static function groupByCategory(array $results) {
    $clusters = [];
    foreach ($results as $result) {
      $text = !empty($result['content']) ? $result['content'] : $result['summary'];
      $category = self::classifyText($text);
      if (empty($clusters[$category])) {
        $clusters[$category] = [
          'title' => $category,
          'children' => [],
          'weight' => 0,
        ];
      }
      $clusters[$category]['children'][] = $result;
      $clusters[$category]['weight']++;
    }
    return array_values($clusters);
  }
}
