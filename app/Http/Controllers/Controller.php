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
    const API_SUCCESS        = "000";
    const API_FAIL           = "001";
    const API_NOT_FOUND      = "002";
    const API_FOUND          = "003";
    const API_CREATED        = "004";
    const API_SENDER         = "005";
    const API_DEAL_DELETE    = "006";
    const API_MISMATCH       = "007";
    const API_ALREADY_EXISTS = "008";

    use AuthorizesRequests, ValidatesRequests, ApiTransformer, AppNotifications, Helpers;
}
