<?php
namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function getAllClientNotifications()
    {
        $user          = request()->user();
        $notifications = Notification::query()
            ->where('actor', 'client')
            ->where('actor_slug', (string) $user->client_slug)
            ->where('actor_id', (string) $user->id)
            ->orderByDesc('created_at')
            ->get();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Notifications retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $notifications?->load('actor')->toArray()
        );
    }
}
