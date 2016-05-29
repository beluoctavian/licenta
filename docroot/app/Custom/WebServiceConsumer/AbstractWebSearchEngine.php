<?php

namespace App\Custom\WebServiceConsumer;

use Illuminate\Http\Request;

use App\Http\Requests;

abstract class AbstractWebSearchEngine
{
  abstract public function search($query, $limit, $advanced);
}
