<?php
namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function getAllClientNotifications()
    {
        $user = request()->user();

        return $this->paginatedApiResponse(
            Notification::query()
                ->where('actor', 'client')
                ->where('actor_slug', (string) $user->client_slug)
                ->where('admin_slug', auth('admin')->user()->admin_slug ?? null)
                ->orderByDesc('created_at')
                ->paginate($this->perPage(request())),
            'Notifications retrieved successfully'
        );
    }
}
