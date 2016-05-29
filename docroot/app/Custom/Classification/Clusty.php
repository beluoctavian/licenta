<?php

namespace App\Custom\Classification;

class Clusty
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

  /**
   * @param string $word
   * @return bool
   */
  private static function isValidWord($word, $language = 'en') {
    $conditions = [
      strlen($word) <= 3,
      (preg_match('/\\d/', $word) > 0),
    ];
    foreach ($conditions as $condition) {
      if ($condition == TRUE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param string $text
   *  Text to be classified.
   * @param array $omit
   *  Array of words to omit.
   * @return string
   *  Text category.
   */
  public static function classifyText($text, $language = 'en', array $omit = [])
  {
    $stopwordsArr = array_map('str_getcsv', file(__DIR__ . "/stopwords/{$language}.csv"));
    $stopwords = [];
    foreach ($stopwordsArr as $row) {
      foreach ($row as $col => $val) {
        $stopwords[] = $val;
      }
    }
    $text = trim(preg_replace("/[^0-9a-z]+/i", " ", $text));
    $words = explode(' ', $text);
    $categories = [
      'other' => 0,
    ];
    foreach ($words as $word) {
      $word = strtolower((string) $word);
      if (self::isValidWord($word) && !in_array($word, $stopwords) && !in_array($word, $omit)) {
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
    $omit = explode(' ', $_GET['q']);
    foreach ($results as $result) {
      $text = !empty($result['content']) ? $result['content'] : $result['summary'];
      $category = self::classifyText($text, 'en', $omit);
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
    $clusters = array_values($clusters);
    $other_cluster = [];
    $min_children = 0;
    foreach ($clusters as $key => $cluster) {
      if (count($cluster['children']) == 1) {
        if (empty($other_cluster)) {
          $other_cluster = [
            'title' => 'other',
            'children' => [],
            'weight' => 0,
          ];
        }
        $other_cluster['children'][] = $cluster;
        $other_cluster['weight']++;
        unset($clusters[$key]);
      }
      else {
        if (empty($min_children) || $cluster['children'] < $min_children) {
          $min_children = $cluster['children'];
        }
      }
    }
    if (!empty($other_cluster)) {
      $other_cluster['weight'] = $min_children;
      $clusters[] = $other_cluster;
    }

    return array_values($clusters);
  }
}
