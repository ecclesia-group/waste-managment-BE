<?php
namespace App\Http\Controllers;

use App\Traits\ApiTransformer;
use App\Traits\AppNotifications;
use App\Traits\Helpers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    const API_SUCCESS = 200;
    const API_FAIL = 401;
    const API_FOUND = 404;
    const API_CREATED = 201;

    use AuthorizesRequests, ValidatesRequests, ApiTransformer, AppNotifications, Helpers;
}
