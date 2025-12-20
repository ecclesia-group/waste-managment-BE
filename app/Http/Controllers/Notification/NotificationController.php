<?php
namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getAllNotifications()
    {
        $user          = request()->user();
        $notifications = $user->notifications()->get();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Notifications retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $notifications->toArray()
        );
    }
}
