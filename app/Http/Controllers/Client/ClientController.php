<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;

class ClientController extends Controller
{
    public function allClients()
    {
        $clients = Client::all();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Clients retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $clients->toArray()
        );
    }
}
