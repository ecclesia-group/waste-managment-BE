<?php
namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\JsonResponse;

trait ApiTransformer
{
    protected static function apiResponse(bool $in_error, string $message, string $status_code, string $reason, ?array $data = []): JsonResponse
    {
        $code = 200;

        switch ($status_code) {
            case '000':
            case '200':
            case '005':
            case '002':
            case '003':
                $code = 200;
                break;
            case '001':
                $code = 201;
                break;
            case '997':
                $code = 200;
                break;
            case '999':
                $code = 500;
                break;
            case '004':
                $code = 200;
                break;
            default:
                break;
        }

        return response()->json([
            "data" => [
                "status_code"   => $status_code,
                "message"       => $message,
                "in_error"      => $in_error,
                "reason"        => $reason,
                "data"          => $data,
                "point_in_time" => now(),
            ],
        ], $code);
    }
}
