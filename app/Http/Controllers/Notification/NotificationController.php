<?php
namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getAllNotifications()
    {
        $user          = request()->user();
        // Avoid fragile morph configuration: notifications table already stores actor + actor_id + actor_slug.
        $notifications = \App\Models\Notification::query()
            ->where('actor', 'client')
            ->where('actor_id', (string) $user->id)
            ->orderByDesc('created_at')
            ->get();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Notifications retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $notifications->toArray()
        );
    }
}
