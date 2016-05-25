<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

abstract class AbstractWebSearchEngine extends Controller
{
  abstract public function search($query, $limit, $advanced);
}
